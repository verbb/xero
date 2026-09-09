<?php
namespace verbb\xero\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Xero';
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public array $excludedGateways = [];


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

    public function getExcludedGatewayOptions(): array
    {
        $options = [];

        foreach (Commerce::getInstance()->getGateways()->getAllGateways() as $gateway) {
            $options[] = [
                'label' => $gateway->name,
                'value' => $gateway->handle,
            ];
        }

        return $options;
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['pluginName'], 'trim'];
        $rules[] = [['pluginName'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['clientId', 'clientSecret'], 'required'];

        return $rules;
    }

}

