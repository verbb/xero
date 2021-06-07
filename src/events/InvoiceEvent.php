<?php

namespace thejoshsmith\commerce\xero\events;

use craft\commerce\elements\Order;
use thejoshsmith\commerce\xero\models\Invoice;
use thejoshsmith\commerce\xero\services\XeroAPI;
use yii\base\Event;

/**
 * An Invoice event
 */
class InvoiceEvent extends Event
{
    /**
     * Contact Model
     *
     * @var Invoice
     */
    public $invoice;

    /**
     * Order Element
     *
     * @var Order
     */
    public $order;

    /**
     * XeroApi
     *
     * @var XeroAPI
     */
    public $xero;
}
