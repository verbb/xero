<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;
use verbb\xero\models\Settings;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\Response;

class PluginController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        /* @var Settings $settings */
        $settings = Xero::$plugin->getSettings();

        return $this->renderTemplate('commerce-xero/settings', [
            'settings' => $settings,
        ]);
    }
}
