<?php
namespace verbb\xero\base;

use verbb\xero\Xero;
use verbb\xero\services\Organisations;
use verbb\xero\services\Service;

use Craft;

use yii\log\Logger;

use verbb\auth\Auth;
use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static Xero $plugin;


    // Public Methods
    // =========================================================================

    public static function log(string $message, array $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('commerce-xero', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'commerce-xero');
    }

    public static function error(string $message, array $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('commerce-xero', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'commerce-xero');
    }


    // Public Methods
    // =========================================================================

    public function getOrganisations(): Organisations
    {
        return $this->get('organisations');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'organisations' => Organisations::class,
            'service' => Service::class,
        ]);

        Auth::registerModule();
        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('commerce-xero');
    }

}