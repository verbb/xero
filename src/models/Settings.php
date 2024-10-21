<?php
namespace verbb\xero\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Xero';
    public ?string $clientId = null;
    public ?string $clientSecret = null;
   

    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Handle legacy settings
        if ($oldClientId = ArrayHelper::remove($config, 'xeroClientId')) {
            $config['clientId'] = $oldClientId;
        }
        
        // Handle legacy settings
        if ($oldClientSecret = ArrayHelper::remove($config, 'xeroClientSecret')) {
            $config['clientSecret'] = $oldClientSecret;
        }

        parent::__construct($config);
    }

    public function getRedirectUri(): ?string
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $siteId = Craft::$app->getSites()->getCurrentSite()->id ?? Craft::$app->getSites()->getPrimarySite()->id;

        // Check for Headless Mode and use the Action URL
        if ($generalConfig->headlessMode) {
            // Don't use the `cpUrl` or `actionUrl` helpers, which include the `cpTrigger`, and that won't work when
            // trying to login via the CP. Instead, use the action endpoint, but manually constructed.
            return rtrim(UrlHelper::baseCpUrl(), '/') . '/' . rtrim($generalConfig->actionTrigger, '/') . '/xero/auth/callback';
        }

        return UrlHelper::siteUrl('xero/auth/callback', null, null, $siteId);
    }

    public function isConfigured(): bool
    {
        return $this->clientId && $this->clientSecret;
    }
}
