<?php
namespace modules\xeroscreenshots\controllers;

use craft\web\Controller;
use modules\xeroscreenshots\ScreenshotOrganisation;
use yii\web\Response;

class OrganisationController extends Controller
{
    public function actionIndex(): Response
    {
        $organisation = new ScreenshotOrganisation([
            'id' => 999,
            'enabled' => true,
            'createPayments' => true,
            'updateInventory' => true,
            'accountSales' => '200',
            'accountReceivable' => '610',
            'accountShipping' => '425',
            'accountRounding' => '860',
            'accountDiscounts' => '405',
            'accountAdditionalFees' => '420',
            'accountLineItemTax' => 'Inclusive',
            'accountInvoiceStatus' => 'AUTHORISED',
        ]);

        return $this->renderTemplate('commerce-xero/organisations/_edit', [
            'title' => $organisation->getName(),
            'organisation' => $organisation,
        ]);
    }
}
