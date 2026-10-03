<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;
use verbb\xero\services\Organisations;

use Craft;
use craft\elements\User;
use craft\web\Controller;

use yii\web\Response;

use verbb\auth\Auth;
use verbb\auth\helpers\Session;

use Throwable;

class AuthController extends Controller
{
    // Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = ['callback'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // Don't require CSRF validation for callback requests
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionConnect(): ?Response
    {
        $this->requirePermission('accessPlugin-commerce-xero');
        $this->requirePostRequest();

        $organisationId = $this->request->getRequiredParam('organisation');

        try {
            if (!($organisation = Xero::$plugin->getOrganisations()->getOrganisationById($organisationId))) {
                return $this->asFailure(Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]));
            }

            $context = [
                'organisationId' => $organisationId,
            ];

            if ($this->request->getIsCpRequest()) {
                if ($redirect = $this->request->getValidatedBodyParam('redirect')) {
                    $context['redirect'] = $this->getView()->renderObjectTemplate($redirect, $organisation);
                }
            }

            return Auth::getInstance()->getOAuth()->connect('commerce-xero', $organisation, $organisation->id, $context);
        } catch (Throwable $e) {
            Xero::error('Unable to authorize connect “{organisation}”: “{message}” {file}:{line}', [
                'organisation' => $organisationId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->asFailure(Craft::t('commerce-xero', 'Unable to authorize connect “{organisation}”.', ['organisation' => $organisationId]));
        }
    }

    public function actionCallback(): ?Response
    {
        $oauth = Auth::getInstance()->getOAuth();

        if ($response = $oauth->prepareCallback('commerce-xero')) {
            return $response;
        }

        $oauth->claimAuthorizedCallback('commerce-xero', fn(User $user): bool => $user->can('accessPlugin-commerce-xero'));

        // Get both the origin (failure) and redirect (success) URLs
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');

        // Get the organisation we're current authorizing
        if (!($organisationId = Session::get('organisationId'))) {
            Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to find organisation.'), true);

            return $this->redirect($origin);
        }

        $organisationsService = Xero::$plugin->getOrganisations();
        $mutex = Craft::$app->getMutex();
        $lockName = $organisationsService->getConnectionLockName((int)$organisationId);

        if (!$mutex->acquire($lockName, Organisations::CONNECTION_LOCK_TIMEOUT)) {
            Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to connect organisation “{organisation}”. Please try again.', ['organisation' => $organisationId]), true);
            return $this->redirect($origin);
        }

        try {
            if (!($organisation = $organisationsService->getOrganisationById($organisationId))) {
                Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]), true);

                return $this->redirect($origin);
            }

            // Fetch the access token and create a Token for us to use
            $token = $oauth->callback('commerce-xero', $organisation, $organisation->id);

            if (!$token) {
                Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to fetch token.'), true);

                return $this->redirect($origin);
            }

            // Save the token to the Auth plugin, with a reference to this plugin
            $token->reference = $organisation->id;
            Auth::getInstance()->getTokens()->upsertToken($token);
        } catch (Throwable $e) {
            $error = Craft::t('commerce-xero', 'Unable to process callback for Xero: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Xero::error($error);

            // Show the error detail in the CP
            Craft::$app->getSession()->setFlash('xero:callback-error', $error);

            return $this->redirect($origin);
        } finally {
            $mutex->release($lockName);
        }

        Session::setNotice('commerce-xero', Craft::t('commerce-xero', 'Xero connected.'), true);

        return $this->redirect($redirect);
    }

    public function actionDisconnect(): ?Response
    {
        $this->requirePermission('accessPlugin-commerce-xero');
        $this->requirePostRequest();

        $organisationId = $this->request->getRequiredParam('organisation');
        $organisationsService = Xero::$plugin->getOrganisations();
        $mutex = Craft::$app->getMutex();
        $lockName = $organisationsService->getConnectionLockName((int)$organisationId);

        if (!$mutex->acquire($lockName, Organisations::CONNECTION_LOCK_TIMEOUT)) {
            return $this->asFailure(Craft::t('commerce-xero', 'Unable to disconnect organisation “{organisation}”. Please try again.', ['organisation' => $organisationId]));
        }

        try {
            if (!($organisation = $organisationsService->getOrganisationById($organisationId))) {
                return $this->asFailure(Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]));
            }

            // Keep the local credentials until Xero confirms the connection is revoked.
            if (!$organisation->revokeConnection()) {
                $organisation->addError('id', Craft::t('commerce-xero', 'Couldn’t disconnect this organisation from Xero. Please try again.'));
                return $this->asModelFailure($organisation, Craft::t('commerce-xero', 'Couldn’t disconnect from Xero.'), 'organisation');
            }

            $transaction = Craft::$app->getDb()->beginTransaction();

            try {
                Auth::getInstance()->getTokens()->deleteTokenByOwnerReference('commerce-xero', (string)$organisation->id);
                $transaction->commit();
            } catch (Throwable $e) {
                if ($transaction->getIsActive()) {
                    $transaction->rollBack();
                }

                throw $e;
            }

            return $this->asModelSuccess($organisation, Craft::t('commerce-xero', 'Xero disconnected.'), 'organisation');
        } finally {
            $mutex->release($lockName);
        }
    }

}
