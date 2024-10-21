<?php
namespace verbb\xero\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

use verbb\auth\clients\xero\provider\XeroResourceOwner;

class Profile extends Model
{
    // Properties
    // =========================================================================

    public ?string $xeroUserId = null;
    public ?string $preferredUsername = null;
    public ?string $email = null;
    public ?string $givenName = null;
    public ?string $familyName = null;   


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Convert `XeroResourceOwner` to our model
        if ($config instanceof XeroResourceOwner) {
            $config = (array)$config;

            ArrayHelper::rename($config, 'xero_userid', 'xeroUserId');
            ArrayHelper::rename($config, 'preferred_username', 'preferredUsername');
            ArrayHelper::rename($config, 'email', 'email');
            ArrayHelper::rename($config, 'given_name', 'givenName');
            ArrayHelper::rename($config, 'family_name', 'familyName');
        }

        parent::__construct($config);
    }

}
