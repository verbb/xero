<?php
namespace verbb\xero\events;

use craft\commerce\elements\Order;
use craft\events\CancelableEvent;

class OrderEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public Order $order;

}
