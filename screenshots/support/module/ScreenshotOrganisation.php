<?php
namespace modules\xeroscreenshots;

use verbb\xero\models\Account;
use verbb\xero\models\Organisation;

class ScreenshotOrganisation extends Organisation
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function getName(): ?string
    {
        return 'Verbb Studio Pty Ltd';
    }

    public function getAccounts(): array
    {
        return [
            new Account(['id' => '1', 'code' => '200', 'name' => 'Sales', 'status' => 'ACTIVE', 'type' => 'REVENUE']),
            new Account(['id' => '2', 'code' => '610', 'name' => 'Accounts Receivable', 'status' => 'ACTIVE', 'type' => 'CURRENT']),
            new Account(['id' => '3', 'code' => '425', 'name' => 'Freight & Delivery', 'status' => 'ACTIVE', 'type' => 'EXPENSE']),
            new Account(['id' => '4', 'code' => '860', 'name' => 'Rounding', 'status' => 'ACTIVE', 'type' => 'EXPENSE']),
            new Account(['id' => '5', 'code' => '405', 'name' => 'Promotional Discounts', 'status' => 'ACTIVE', 'type' => 'EXPENSE']),
            new Account(['id' => '6', 'code' => '420', 'name' => 'Merchant Fees', 'status' => 'ACTIVE', 'type' => 'EXPENSE']),
        ];
    }
}
