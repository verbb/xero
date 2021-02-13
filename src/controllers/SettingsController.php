<?php

namespace thejoshsmith\commerce\xero\controllers;

use Craft;
use thejoshsmith\commerce\xero\Plugin;
use yii\web\Response;

class SettingsController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function init()
    {
        $this->requireAdmin();
        parent::init();
    }

    /**
     * Commerce Settings Form
     */
    public function actionEdit(): Response
    {
        $settings = Plugin::getInstance()->getSettings();

        $variables = [
            'settings' => $settings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/settings/_index', $variables);
    }

    /**
     * @return Response|null
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $params = Craft::$app->getRequest()->getBodyParams();
        $data = $params['settings'];

        $settings = Plugin::getInstance()->getSettings();
        $settings->xeroClientId = $data['xeroClientId'] ?? $settings->xeroClientId;
        $settings->xeroClientSecret = $data['xeroClientSecret']
            ?? $settings->xeroClientSecret;

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(
                Plugin::t('Couldn’t save settings.')
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/settings/_index', compact('settings')
            );
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(
            Plugin::getInstance(), $settings->toArray()
        );

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(
                Plugin::t('Couldn’t save settings.')
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/settings/_index', compact('settings')
            );
        }

        Craft::$app->getSession()->setNotice(
            Plugin::t('Settings saved.')
        );

        return $this->redirectToPostedUrl();
    }
}
