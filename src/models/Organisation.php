<?php
namespace verbb\xero\models;

use verbb\xero\Xero;

use Craft;
use craft\base\SavableComponent;
use craft\base\SavableComponentInterface;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use Throwable;

use GuzzleHttp\Exception\RequestException;

use verbb\auth\Auth;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\base\OAuthProviderTrait;
use verbb\auth\clients\xero\provider\XeroResourceOwner;
use verbb\auth\models\Token;
use verbb\auth\providers\Xero as XeroProvider;

class Organisation extends SavableComponent implements OAuthProviderInterface, SavableComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return XeroProvider::class;
    }


    // Traits
    // =========================================================================

    use OAuthProviderTrait;


    // Properties
    // =========================================================================
    
    public ?bool $enabled = null;
    public ?bool $createPayments = null;
    public ?bool $updateInventory = null;
    public ?string $accountSales = null;
    public ?string $accountReceivable = null;
    public ?string $accountShipping = null;
    public ?string $accountRounding = null;
    public ?string $accountDiscounts = null;
    public ?string $accountAdditionalFees = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;

    private array $_accounts = [];
    private ?Tenant $_tenant = null;
    private ?Profile $_profile = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accountSales', 'accountReceivable', 'accountShipping', 'accountRounding'], 'required', 'when' => fn() => $this->enabled];
        $rules[] = [['id'], 'number', 'integerOnly' => true];

        return $rules;
    }

    public function getClientId(): ?string
    {
        return App::parseEnv(Xero::$plugin->getSettings()->clientId);
    }

    public function getClientSecret(): ?string
    {
        return App::parseEnv(Xero::$plugin->getSettings()->clientSecret);
    }

    public function getRedirectUri(): ?string
    {
        return Xero::$plugin->getSettings()->getRedirectUri();
    }

    public function isConfigured(): bool
    {
        return $this->getClientId() && $this->getClientSecret();
    }

    public function isConnected(): bool
    {
        return (bool)$this->getToken();
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("xero/organisations/$this->id");
    }

    public function getToken(): ?Token
    {
        if ($this->id) {
            return Auth::$plugin->getTokens()->getTokenByOwnerReference('commerce-xero', $this->id);
        }

        return null;
    }

    public function tenantRequest(string $method = 'GET', string $uri = '', array $options = [])
    {
        $options['headers']['Xero-Tenant-Id'] = $this->getTenantId();

        return $this->request($method, $uri, $options);
    }

    public function disconnect(): void
    {
        try {
            $token = $this->getToken()?->getToken() ?? null;

            if ($token && $tenant = $this->getTenant()) {
                $this->getOAuthProvider()->disconnect($token, $tenant->id);
            }
        } catch (Throwable $e) {
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

    public function getTenant(): ?Tenant
    {
        if ($this->_tenant) {
            return $this->_tenant;
        }

        $token = $this->getToken()?->getToken() ?? null;

        if ($token) {
            $tenantData = $token->getValues()['tenant'] ?? [];

            $this->_tenant = new Tenant($tenantData);
        }

        return $this->_tenant;
    }

    public function getTenantId(): ?string
    {
        return $this->getTenant()?->tenantId ?? null;
    }

    public function getName(): ?string
    {
        return $this->getTenant()?->tenantName ?? null;
    }

    public function getProfile(): ?Profile
    {
        if ($this->_profile) {
            return $this->_profile;
        }

        $token = $this->getToken()?->getToken() ?? null;

        if ($token) {
            $resourceData = $this->getOAuthProvider()->getResourceOwner($token) ?? [];

            $this->_profile = new Profile($resourceData);
        }

        return $this->_profile;
    }

    public function getAccounts(): array
    {
        if ($this->_accounts) {
            return $this->_accounts;
        }

        try {
            $response = $this->tenantRequest('GET', 'api.xro/2.0/Accounts');

            $accounts = $response['Accounts'] ?? [];
            $this->_accounts = [];

            foreach ($accounts as $account) {
                $this->_accounts[] = new Account([
                    'id' => $account['AccountID'],
                    'code' => $account['Code'],
                    'name' => $account['Name'],
                    'status' => $account['Status'],
                    'type' => $account['Type'],
                ]);
            }
        } catch (Throwable $e) {
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

        return $this->_accounts;
    }

    public function getAccountOptions(): array
    {
        $placeholder = [
            ['label' => Craft::t('commerce-xero', 'Select an option'), 'value' => ''],
        ];

        $options = [];

        foreach ($this->getAccounts() as $account) {
            $label = "$account->name - $account->code - $account->type";
            
            $options[] = ['label' => $label, 'value' => $account->code];
        }

        // Sort the options array alphabetically by the 'label' key
        usort($options, fn($a, $b) => strcmp($a['label'], $b['label']));

        return array_merge($placeholder, $options);
    }

    public function getAccountByCode(string $code): ?Account
    {
        foreach ($this->getAccounts() as $account) {
            if ($account->code === $code) {
                return $account;
            }
        }

        return null;
    }

    public function afterFetchAccessToken(Token $token): void
    {
        $accessToken = $token?->getToken() ?? null;

        // Store the tenant alongside the access token data for later
        if ($accessToken) {
            $values = $token['values'];

            if ($tenants = $this->getOAuthProvider()->getTenants($accessToken)) {
                $values['tenant'] = (array)($tenants[0] ?? []);
            }

            $token['values'] = $values;
        }
    }

    public function getAuthorizationUrlOptions(): array
    {
        return [
            'scope' => [
                'openid',
                'email',
                'profile',
                'offline_access',
                'accounting.transactions',
                'accounting.settings',
                'accounting.contacts',
            ],
        ];
    }
}