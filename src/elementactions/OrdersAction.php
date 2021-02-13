<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\commerce\xero\elementactions;

use thejoshsmith\commerce\xero\Plugin;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class OrdersAction extends ElementAction
{
    public $label;

    public function init()
    {
        if ($this->label === null) {
            $this->label = Plugin::t('Send to Xero');
        }
    }

    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        // Craft::$app->getView()->registerJs($js);
    }

}
