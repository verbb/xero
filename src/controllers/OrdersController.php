<?php
namespace verbb\xero\controllers;

use verbb\xero\Xero;
use verbb\xero\queue\jobs\SendToXero;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\Response;

class OrdersController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requirePermission('accessPlugin-commerce-xero');
        $this->requirePermission('commerce-manageOrders');

        return true;
    }

    public function actionSend(): Response
    {
        $this->requirePostRequest();

        $orderId = filter_var($this->request->getRequiredBodyParam('orderId'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $order = $orderId ? Xero::$plugin->getService()->getEligibleOrderById($orderId) : null;

        if (!$order) {
            throw new BadRequestHttpException(Craft::t('commerce-xero', 'The order is not eligible to be sent to Xero.'));
        }

        Craft::$app->getQueue()->push(new SendToXero([
            'orderId' => $order->id,
        ]));

        return $this->asSuccess();
    }
}
