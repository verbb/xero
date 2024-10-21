<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;

use Craft;
use craft\web\Controller;

use yii\web\Response;

use verbb\auth\Auth;
use verbb\auth\helpers\Session;

use Throwable;

class AuthController extends Controller
{
    // Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = ['connect', 'callback'];


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
        $organisationId = $this->request->getRequiredParam('organisation');

        try {
            if (!($organisation = Xero::$plugin->getOrganisations()->getOrganisationById($organisationId))) {
                return $this->asFailure(Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]));
            }

            // Keep track of which organisation instance is for, so we can fetch it in the callback
            Session::set('organisationId', $organisationId);

            return Auth::$plugin->getOAuth()->connect('commerce-xero', $organisation);
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
        // Restore the session data that we saved before authorization redirection from the cache back to session
        Session::restoreSession($this->request->getParam('state'));

        // Get both the origin (failure) and redirect (success) URLs
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');

        // Get the organisation we're current authorizing
        if (!($organisationId = Session::get('organisationId'))) {
            Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to find organisation.'), true);

            return $this->redirect($origin);
        }

        if (!($organisation = Xero::$plugin->getOrganisations()->getOrganisationById($organisationId))) {
            Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]), true);

            return $this->redirect($origin);
        }

        try {
            // Fetch the access token and create a Token for us to use
            $token = Auth::$plugin->getOAuth()->callback('commerce-xero', $organisation);

            if (!$token) {
                Session::setError('commerce-xero', Craft::t('commerce-xero', 'Unable to fetch token.'), true);

                return $this->redirect($origin);
            }

            // Save the token to the Auth plugin, with a reference to this plugin
            $token->reference = $organisation->id;
            Auth::$plugin->getTokens()->upsertToken($token);
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
        }

        Session::setNotice('commerce-xero', Craft::t('commerce-xero', 'Xero connected.'), true);

        return $this->redirect($redirect);
    }

    public function actionDisconnect(): ?Response
    {
        $organisationId = $this->request->getRequiredParam('organisation');

        if (!($organisation = Xero::$plugin->getOrganisations()->getOrganisationById($organisationId))) {
            return $this->asFailure(Craft::t('commerce-xero', 'Unable to find organisation “{organisation}”.', ['organisation' => $organisationId]));
        }

        // Disconnect in Xero first
        $organisation->disconnect();

        // Delete all tokens for this client
        Auth::$plugin->getTokens()->deleteTokenByOwnerReference('commerce-xero', $organisation->id);

        return $this->asModelSuccess($organisation, Craft::t('commerce-xero', 'Xero disconnected.'), 'organisation');
    }

}
