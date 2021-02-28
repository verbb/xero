<?php
/**
 * Organisation Controller
 *
 * Handles routing of tenant requests
 *
 * PHP version 7.4
 *
 * @category  Controllers
 * @package   CraftCommerceXero
 * @author    Josh Smith <by@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\commerce\xero\controllers;

use thejoshsmith\commerce\xero\Plugin;
use thejoshsmith\commerce\xero\controllers\BaseController;
use thejoshsmith\commerce\xero\models\OrganisationSettings as OrganisationSettingsModel;

use Craft;
use Throwable;

use yii\web\Response;
use yii\web\NotFoundHttpException;

/**
 * Organisation Controller
 */
class OrganisationController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Index of tenants
     *
     * @return Response
     */
    public function actionIndex(OrganisationSettingsModel $orgSettings = null)
    {
        $pluginSettings = Plugin::getInstance()->getSettings();
        $xeroConnections = Plugin::getInstance()->getXeroConnections();

        $connection = $xeroConnections->getCurrentConnection();
        $connections = $xeroConnections->getAllConnections();

        // Check whether meta instruction tooltips are supported
        $supportsMetaInstructions = version_compare(
            Craft::$app->version,
            '3.5.17',
            '>='
        );

        $accounts = null;

        // Create a new settings model
        if (empty($orgSettings) && $connection) {
            $orgSettings
                = OrganisationSettingsModel::fromConnection($connection);
        }

        if ($connection) {
            try {
                $accounts = Plugin::getInstance()->getXeroApi()->getAccounts();
            } catch(Throwable $e) {
                Craft::$app->getSession()->setError($e->getMessage());
            }
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/organisation/_index', compact(
                'pluginSettings',
                'orgSettings',
                'connection',
                'connections',
                'accounts',
                'supportsMetaInstructions'
            )
        );
    }

    /**
     * Saves organisation settings
     *
     * @return void
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $data = $this->request->getBodyParams();

        // Connection ID is a required parameter
        if (empty($data['connectionId']) ) {
            $this->setFailFlash(Plugin::t('Couldn\'t find the organisation\'s connection.'));
            return null;
        }

        $orgSettings = new OrganisationSettingsModel();
        $orgSettings->attributes = $data;

        if (! $orgSettings->validate()) {
            $this->setFailFlash(Plugin::t('Couldn’t save organisation settings.'));

            Craft::$app
                ->getUrlManager()
                ->setRouteParams(['orgSettings' => $orgSettings]);

            return null;
        }

        $xeroConnections = Plugin::getInstance()->getXeroConnections();
        $connection = $xeroConnections->getConnectionById($data['connectionId']);

        if (empty($connection)) {
            throw new NotFoundHttpException('Connection not found');
        }

        // Todo, move this to a service
        $connection->enabled = empty($data['enabled']) ? false : (int) $data['enabled'];
        $connection->settings = $orgSettings->attributes;
        $connection->save();

        $this->setSuccessFlash(Plugin::t('Organisation Settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
