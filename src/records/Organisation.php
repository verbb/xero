<?php
namespace verbb\xero\records;

use craft\db\ActiveRecord;

class Organisation extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%xero_organisations}}';
    }
}
