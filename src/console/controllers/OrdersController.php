<?php
namespace verbb\xero\console\controllers;

use verbb\xero\Xero;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Db;

use yii\console\ExitCode;
use yii\web\Response;

use craft\commerce\Plugin as Commerce;

/**
 * Manages Commerce Orders for Xero.
 */
class OrdersController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var int 
     */
    public ?int $orderId = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'send':
                $options[] = 'orderId';
                break;
        }

        return $options;
    }

    /**
     * Sends a given Commerce Order to Xero.
     */
    public function actionSend(): int
    {
        if (!$this->orderId) {
            $this->stderr('You must provide a --order-id option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);

        if (!$order) {
            $this->stderr('Unable to find order for ID #' . $this->orderId . '.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Xero::$plugin->getService()->sendOrder($order);

        return ExitCode::OK;
    }
}
