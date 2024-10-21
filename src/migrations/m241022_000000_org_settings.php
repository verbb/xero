<?php
namespace verbb\xero\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use DateTime;

class m241022_000000_org_settings extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%xero_organisations}}', 'accountLineItemTax')) {
            $this->addColumn('{{%xero_organisations}}', 'accountLineItemTax', $this->string()->after('accountAdditionalFees'));
        }

        if (!$this->db->columnExists('{{%xero_organisations}}', 'accountInvoiceStatus')) {
            $this->addColumn('{{%xero_organisations}}', 'accountInvoiceStatus', $this->string()->after('accountLineItemTax'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m241022_000000_org_settings cannot be reverted.\n";
        return false;
    }
}
