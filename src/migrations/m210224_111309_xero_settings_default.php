<?php

namespace thejoshsmith\commerce\xero\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210224_111309_xero_settings_default migration.
 */
class m210224_111309_xero_settings_default extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('xero_connections', 'settings', $this->longText()->null());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210224_111309_xero_settings_default cannot be reverted.\n";
        return false;
    }
}
