<?php

namespace thejoshsmith\commerce\xero\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use craft\web\User;

/**
 * Active Record class for saving Xero Connections
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Connection extends ActiveRecord
{
    const STATUS_CONNECTED = 'connected';
    const STATUS_DISCONNECTED = 'disconnected';

    /**
     * Define Scenarios
     */
    const SCENARIO_PATCH = 'patch';

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_connections}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_PATCH] = ['selected', 'enabled', 'status'];
        $scenarios[self::SCENARIO_DEFAULT] = ['connectionId', 'credentialId', 'resourceOwnerId', 'tenantId', 'userId', 'siteId', 'selected', 'enabled', 'status'];
        return $scenarios;
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['connectionId', 'credentialId', 'resourceOwnerId', 'tenantId', 'userId', 'siteId'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['id'], 'required', 'on' => self::SCENARIO_PATCH],
            [['credentialId', 'resourceOwnerId', 'tenantId', 'userId', 'siteId'], 'integer'],
            [['selected', 'enabled'], 'boolean'],
            [['connectionId', 'status'], 'string'],
        ];
    }

    public function getCredential()
    {
        return $this->hasOne(Credential::class, ['id' => 'credentialId']);
    }

    public function getResourceOwner()
    {
        return $this->hasOne(ResourceOwner::class, ['id' => 'resourceOwnerId']);
    }

    public function getTenant()
    {
        return $this->hasOne(Tenant::class, ['id' => 'tenantId']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
