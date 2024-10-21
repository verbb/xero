<?php
namespace verbb\xero\models;

use Craft;
use craft\base\Model;

use DateTime;

class Tenant extends Model
{
    // Properties
    // =========================================================================

    public ?string $id = null;
    public ?string $authEventId = null;
    public ?string $tenantId = null;
    public ?string $tenantType = null;
    public ?string $tenantName = null;
    public ?DateTime $createdDateUtc = null;
    public ?DateTime $updatedDateUtc = null;

}
