<?php
namespace verbb\xero\queue\jobs;

use verbb\xero\Xero;

use Craft;
use craft\queue\BaseJob;

use craft\commerce\Plugin as Commerce;

class SendToXero extends BaseJob
{
    // Properties
    // =========================================================================

    public int $orderId;


    // Public Methods
    // =========================================================================

    public function getDescription(): ?string
    {
        return Craft::t('commerce-xero', 'Send Order to Xero');
    }

    public function execute($queue): void
    {
        $this->setProgress($queue, 0);

        $order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);

        if ($order) {
            Xero::$plugin->getService()->sendOrder($order);
        }

        $this->setProgress($queue, 1);
    }
}
