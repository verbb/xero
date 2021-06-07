<?php

namespace thejoshsmith\commerce\xero\events;

use craft\commerce\elements\Order;
use thejoshsmith\commerce\xero\services\XeroAPI;
use XeroPHP\Models\Accounting\Contact;
use yii\base\Event;

/**
 * A Contact event
 */
class ContactEvent extends Event
{
    /**
     * Contact Model
     *
     * @var Contact
     */
    public $contact;

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
