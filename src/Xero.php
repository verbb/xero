<?php
namespace verbb\xero;

use verbb\xero\base\PluginTrait;
use verbb\xero\models\Settings;
use verbb\xero\queue\jobs\SendToXero;
use verbb\xero\variables\XeroVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use yii\base\Event;

use craft\commerce\elements\Order;

class Xero extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.3.1';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariables();
        $this->_registerCraftEventListeners();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_registerSiteRoutes();
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('commerce-xero', $this->getSettings()->pluginName);
    }

    public function getCpNavItem(): array
    {
        $nav = parent::getCpNavItem();

        $nav['label'] = $this->getPluginName();
        $nav['url'] = 'xero';

        if (Craft::$app->getUser()->checkPermission('accessPlugin-commerce-xero')) {
            $nav['subnav']['organisations'] = [
                'label' => Craft::t('commerce-xero', 'Organisations'),
                'url' => 'xero/organisations',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('commerce-xero', 'Settings'),
                'url' => 'xero/settings',
            ];
        }

        return $nav;
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('xero/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['xero'] = ['template' => 'commerce-xero/index'];
            $event->rules['xero/organisations'] = 'commerce-xero/organisations/index';
            $event->rules['xero/organisations/new'] = 'commerce-xero/organisations/edit';
            $event->rules['xero/organisations/<id:\d+>'] = 'commerce-xero/organisations/edit';
            $event->rules['xero/settings'] = 'commerce-xero/plugin/settings';

            if (Craft::$app->getConfig()->getGeneral()->headlessMode || !Craft::$app->getConfig()->getGeneral()->cpTrigger) {
                $event->rules['xero/auth/callback'] = 'commerce-xero/auth/callback';
            }
        });
    }

    private function _registerSiteRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['xero/auth/callback'] = 'commerce-xero/auth/callback';
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('xero', XeroVariable::class);
        });
    }

    private function _registerCraftEventListeners(): void
    {
        // Send completed and paid orders off to Xero (30 second delay)
        Event::on(Order::class, Order::EVENT_AFTER_ORDER_PAID, function(Event $event) {
            Craft::$app->queue->delay(30)->push(new SendToXero([
                'orderId' => $event->sender->id,
            ]));
        });
    }
}
