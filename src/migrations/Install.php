<?php
namespace verbb\xero\migrations;

use verbb\xero\Xero;

use Craft;
use craft\db\Migration;

use verbb\auth\Auth;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Ensure that the Auth module kicks off setting up tables
        Auth::getInstance()->migrator->up();

        $this->createTables();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTables();

        // Delete all tokens for this plugin
        Auth::getInstance()->getTokens()->deleteTokensByOwner('commerce-xero');

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%xero_organisations}}');
        $this->createTable('{{%xero_organisations}}', [
            'id' => $this->primaryKey(),
            'enabled' => $this->boolean(),
            'createPayments' => $this->boolean(),
            'updateInventory' => $this->boolean(),
            'accountSales' => $this->string(),
            'accountReceivable' => $this->string(),
            'accountShipping' => $this->string(),
            'accountRounding' => $this->string(),
            'accountDiscounts' => $this->string(),
            'accountAdditionalFees' => $this->string(),
            'accountLineItemTax' => $this->string(),
            'accountInvoiceStatus' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%xero_organisations}}');
    }
}
