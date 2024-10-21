<?php
namespace verbb\xero\services;

use verbb\xero\Xero;
use verbb\xero\models\Account;
use verbb\xero\models\Organisation;

use Craft;
use craft\base\Component;

use craft\commerce\elements\Order;

use GuzzleHttp\Exception\RequestException;

use DateTime;
use Exception;
use Throwable;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function sendOrder(Order $order): bool
    {
        // Trigger for all enabled organisations
        foreach (Xero::$plugin->getOrganisations()->getAllEnabledOrganisations() as $organisation) {
            $contact = $this->findOrCreateContact($organisation, $order);

            if ($contact) {
                $invoice = $this->createInvoice($organisation, $contact, $order);
                
                // Only continue to payment if a payment has been made and payments are enabled
                if ($invoice && $order->isPaid && $organisation->createPayments) {
                    // Before we can make the payment we need to get the Account
                    $account = $organisation->getAccountByCode($organisation->accountReceivable);
                    
                    if ($account) {
                        $payment = $this->createPayment($organisation, $invoice, $account, $order);
                    }
                    
                    return true;
                }
            }
        }

        return false;
    }

    public function findOrCreateContact(Organisation $organisation, Order $order): array
    {
        try {
            $user = $order->getUser();

            $contactEmail = $user ? $user->email : $order->getEmail();
            $contactName = $user ? $user->getName() : $order->getEmail();
            $contactFirstName = $user->firstName ?? null;
            $contactLastName = $user->lastName ?? null;

            $response = $organisation->tenantRequest('GET', 'api.xro/2.0/Contacts', [
                'query' => [
                    'where' => 'EmailAddress=="' . $contactEmail . '"',
                ],
            ]);

            $contact = $response['Contacts'][0] ?? [];

            if (!$contact) {
                $response = $organisation->tenantRequest('POST', 'api.xro/2.0/Contacts', [
                    'json' => [
                        'Name' => $contactName,
                        'FirstName' => $contactFirstName,
                        'LastName' => $contactLastName,
                        'EmailAddress' => $contactEmail,
                    ],
                ]);

                $contact = $response['Contacts'][0] ?? [];
            }

            return $contact;
        } catch (Throwable $e) {
            $this->_handleException($e);
        }

        return [];
    }

    public function createInvoice(Organisation $organisation, array $contact, Order $order): array
    {
        try {
            $invoice = [
                'Status' => $organisation->accountInvoiceStatus,
                'Type' => 'ACCREC',
                'Contact' => [
                    'ContactID' => $contact['ContactID'],
                ],
                'LineAmountType' => $organisation->accountLineItemTax,
                'CurrencyCode' => $order->getPaymentCurrency(),
                'InvoiceNumber' => $order->reference,
                'SentToContact' => true,
                'DueDate' => (new DateTime())->format('Y-m-d'),
                'LineItems' => [],
            ];

            foreach ($order->getLineItems() as $orderItem) {
                $lineItem = [
                    'AccountCode' => $organisation->accountSales,
                    'Description' => $orderItem->description,
                    'Quantity' => $orderItem->qty,
                ];

                if ($orderItem->discount > 0) {
                    $discountPercentage = (($orderItem->discount / $orderItem->subtotal) * -100);

                    $lineItem['DiscountRate'] = $this->_format($discountPercentage);
                }

                if ($orderItem->salePrice > 0) {
                    $lineItem['UnitAmount'] = $this->_format($orderItem->salePrice);
                } else {
                    $lineItem['UnitAmount'] = $this->_format($orderItem->price);
                }

                if ($organisation->updateInventory) {
                    $lineItem['ItemCode'] = $orderItem->sku;
                }

                $invoice['LineItems'][] = $lineItem;
            }

            foreach ($order->getOrderAdjustments() as $adjustment) {
                if ($adjustment->type == 'shipping') {
                    $invoice['LineItems'][] = [
                        'AccountCode' => $organisation->accountShipping,
                        'Description' => $adjustment->name,
                        'Quantity' => 1,
                        'UnitAmount' => $this->_format($order->getTotalShippingCost()),
                    ];
                } else if ($adjustment->type == 'discount') {
                    $invoice['LineItems'][] = [
                        'AccountCode' => $organisation->accountDiscounts,
                        'Description' => $adjustment->name,
                        'Quantity' => 1,
                        'UnitAmount' => $this->_format($adjustment->amount),
                    ];
                } else if ($adjustment->type !== 'tax') {
                    $invoice['LineItems'][] = [
                        'AccountCode' => $organisation->accountAdditionalFees,
                        'Description' => $adjustment->name,
                        'Quantity' => 1,
                        'UnitAmount' => $this->_format($adjustment->amount),
                    ];
                }
            }

            $response = $organisation->tenantRequest('POST', 'api.xro/2.0/Invoices', [
                'json' => [
                    'Invoices' => [$invoice],
                ],
            ]);

            return $response['Invoices'][0] ?? [];
        } catch (Throwable $e) {
            $this->_handleException($e);
        }

        return [];
    }

    public function createPayment(Organisation $organisation, array $invoice, Account $account, Order $order): array
    {
        try {
            $payment = [
                'Invoice' => $invoice,
                'Account' => $account,
                'Reference' => $order->getLastTransaction()->reference,
                'Amount' => $this->_format($order->getTotalPaid()),
                'Date' => $order->datePaid->format('Y-m-d'),
            ];

            $response = $organisation->tenantRequest('POST', 'api.xro/2.0/Payments', [
                'json' => [
                    'Payments' => [$payment],
                ],
            ]);

            return $response['Payments'][0] ?? [];
        } catch (Throwable $e) {
            $this->_handleException($e);
        }

        return [];
    }

    
    // Public Methods
    // =========================================================================

    private function _format(float $number, int $precision = 2)
    {
        return number_format($number, $precision, '.', '');
    }

    private function _handleException(Exception $e): void
    {
        $messageText = $e->getMessage();

        // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
        if ($e instanceof RequestException && $e->getResponse()) {
            $messageText = (string)$e->getResponse()->getBody()->getContents();
        }

        Xero::error(Craft::t('commerce-xero', 'API error: “{message}” {file}:{line}', [
            'message' => $messageText,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]));
    }

}
