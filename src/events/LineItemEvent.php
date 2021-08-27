<?php

namespace thejoshsmith\commerce\xero\events;

use craft\commerce\models\LineItem as LineItemModel;
use XeroPHP\Models\Accounting\LineItem;
use thejoshsmith\commerce\xero\services\XeroAPI;
use yii\base\Event;

/**
 * An Invoice event
 */
class LineItemEvent extends Event
{
    /**
     * Contact Model
     *
     * @var LineItem
     */
    public $lineItem;

    /**
     * Order Element
     *
     * @var LineItemModel
     */
    public $orderItem;

    /**
     * XeroApi
     *
     * @var XeroAPI
     */
    public $xero;
}
