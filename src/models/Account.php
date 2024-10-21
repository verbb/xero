<?php
namespace verbb\xero\models;

use Craft;
use craft\base\Model;

class Account extends Model
{
    // Properties
    // =========================================================================

    public ?string $id = null;
    public ?string $code = null;
    public ?string $name = null;
    public ?string $status = null;
    public ?string $type = null;   

}
