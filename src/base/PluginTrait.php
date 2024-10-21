<?php
namespace verbb\xero\base;

use verbb\xero\Xero;
use verbb\xero\services\Organisations;
use verbb\xero\services\Service;

use Craft;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static ?Xero $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;


    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('commerce-xero');

        return [
            'components' => [
                'organisations' => Organisations::class,
                'service' => Service::class,
            ],
        ];
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

}