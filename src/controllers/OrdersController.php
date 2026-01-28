<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;
use verbb\xero\models\Settings;
use verbb\xero\queue\jobs\SendToXero;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\Response;

class OrdersController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSend(): Response
    {
        $this->requirePostRequest();

        Craft::$app->getQueue()->push(new SendToXero([
            'orderId' => $this->request->getParam('orderId'),
        ]));

        return $this->asSuccess();
    }
}
