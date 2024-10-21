<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;
use verbb\xero\models\Organisation;
use verbb\xero\models\Settings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrganisationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Xero::$plugin->getSettings();

        $organisations = Xero::$plugin->getOrganisations()->getAllOrganisations();

        return $this->renderTemplate('commerce-xero/organisations', [
            'organisations' => $organisations,
            'settings' => $settings,
        ]);
    }

    public function actionEdit(?int $id = null, Organisation $organisation = null): Response
    {
        $organisationsService = Xero::$plugin->getOrganisations();

        if ($organisation === null) {
            if ($id !== null) {
                $organisation = $organisationsService->getOrganisationById($id);

                if ($organisation === null) {
                    throw new NotFoundHttpException('Organisation not found');
                }
            } else {
                $organisation = new Organisation();
            }
        }

        if ($id && $organisationsService->getOrganisationById($id)) {
            $title = $organisation->getName() ?? Craft::t('commerce-xero', 'Edit Organisation');
        } else {
            $title = Craft::t('commerce-xero', 'Create a new organisation');
        }

        return $this->renderTemplate('commerce-xero/organisations/_edit', [
            'title' => $title,
            'organisation' => $organisation,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $organisationsService = Xero::$plugin->getOrganisations();
        $organisationId = $this->request->getParam('organisationId') ?: null;

        if ($organisationId) {
            $oldOrganisation = $organisationsService->getOrganisationById($organisationId);
            
            if (!$oldOrganisation) {
                throw new BadRequestHttpException("Invalid organisation ID: $organisationId");
            }
        }

        $organisation = new Organisation([
            'id' => $organisationId,
            'enabled' => (bool)$this->request->getParam('enabled'),
            'createPayments' => (bool)$this->request->getParam('createPayments'),
            'updateInventory' => (bool)$this->request->getParam('updateInventory'),
            'accountSales' => $this->request->getParam('accountSales'),
            'accountReceivable' => $this->request->getParam('accountReceivable'),
            'accountShipping' => $this->request->getParam('accountShipping'),
            'accountRounding' => $this->request->getParam('accountRounding'),
            'accountDiscounts' => $this->request->getParam('accountDiscounts'),
            'accountAdditionalFees' => $this->request->getParam('accountAdditionalFees'),
        ]);

        if (!$organisationsService->saveOrganisation($organisation)) {
            return $this->asModelFailure($organisation, Craft::t('commerce-xero', 'Couldn’t save organisation.'), 'organisation');
        }

        return $this->asModelSuccess($organisation, Craft::t('commerce-xero', 'Organisation saved.'), 'organisation');
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $organisationIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Xero::$plugin->getOrganisations()->reorderOrganisations($organisationIds);

        return $this->asSuccess();
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $organisationId = $this->request->getRequiredBodyParam('id');

        Xero::$plugin->getOrganisations()->deleteOrganisationById($organisationId);

        return $this->asSuccess();
    }

    public function actionRefreshSettings(): Response
    {
        $this->requireAcceptsJson();

        $organisationsService = Xero::$plugin->getOrganisations();

        $organisationHandle = $this->request->getRequiredBodyParam('organisation');
        $setting = $this->request->getRequiredBodyParam('setting');

        $organisation = $organisationsService->getOrganisationByHandle($organisationHandle);
        
        if (!$organisation) {
            throw new BadRequestHttpException("Invalid organisation: $organisationHandle");
        }

        return $this->asJson($organisation->getOrganisationSettings($setting, false));
    }
}
