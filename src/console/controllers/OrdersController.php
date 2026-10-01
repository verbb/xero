<?php
namespace verbb\xero\console\controllers;

use verbb\xero\Xero;

use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

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

        $order = Xero::$plugin->getService()->getEligibleOrderById($this->orderId);

        if (!$order) {
            $this->stderr('Unable to find an eligible order for ID #' . $this->orderId . '.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!Xero::$plugin->getService()->sendOrder($order)) {
            $this->stderr('Unable to send order #' . $this->orderId . ' to Xero.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
