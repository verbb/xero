<?php
namespace modules\xeroscreenshots;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

class Module extends \yii\base\Module
{
    public function init(): void
    {
        parent::init();
        $this->controllerNamespace = 'modules\\xeroscreenshots\\controllers';

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
            $event->rules['screenshot-xero/organisation'] = 'xeroScreenshots/organisation/index';
        });
    }
}
