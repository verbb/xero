<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\commerce\xero\controllers;

use thejoshsmith\commerce\xero\Plugin;

use craft\commerce\Plugin as Commerce;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;

class BaseController extends Controller
{
    /**
     * Initialises the base controller
     *
     * @return void
     */
    public function init()
    {
        $this->requireLogin();
        $this->requirePermission('accessPlugin-xero');
        parent::init();
    }

    public function actionSendOrderToXero()
    {
        $orderId = Craft::$app->request->getParam('orderId');
        if ($orderId) {
            try {
                $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);
                Plugin::getInstance()->getXeroApi()->sendOrder($order);
            } catch (\Throwable $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }
        return false;
    }

}
