<?php
namespace verbb\xero\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use DateTime;

class m241021_000000_verbb extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%xero_connections}}')) {
            $connections = (new Query())
                ->from('{{%xero_connections}}')
                ->all();

            foreach ($connections as $connection) {
                $credential = (new Query())
                    ->from('{{%xero_credentials}}')
                    ->where(['id' => $connection['credentialId']])
                    ->one();

                $resourceOwner = (new Query())
                    ->from('{{%xero_resource_owners}}')
                    ->where(['id' => $connection['resourceOwnerId']])
                    ->one();

                $tenant = (new Query())
                    ->from('{{%xero_tenants}}')
                    ->where(['id' => $connection['tenantId']])
                    ->one();

                $organisation = Json::decode($connection['settings']);
                $organisation['enabled'] = $connection['enabled'];
                $organisation['dateCreated'] = Db::prepareDateForDb(new DateTime());
                $organisation['dateUpdated'] = Db::prepareDateForDb(new DateTime());
                $organisation['uid'] = StringHelper::UUID();

                $this->db->createCommand()
                    ->insert('{{%xero_organisations}}', $organisation)
                    ->execute();

                $organisationId = $this->db->getLastInsertID('{{%xero_organisations}}');

                $tokenValues = [
                    'tenant' => [
                        'id' => $connection['connectionId'],
                        'tenantId' => $tenant['tenantId'],
                        'tenantType' => $tenant['tenantType'],
                        'tenantName' => $tenant['tenantName'],
                    ],
                ];

                $token = [
                    'ownerHandle' => 'commerce-xero',
                    'providerType' => 'verbb\\xero\\models\\Organisation',
                    'tokenType' => 'oauth2',
                    'reference' => $organisationId,
                    'accessToken' => $credential['accessToken'],
                    'refreshToken' => $credential['refreshToken'],
                    'values' => Json::encode($tokenValues),
                    'dateCreated' => Db::prepareDateForDb(new DateTime()),
                    'dateUpdated' => Db::prepareDateForDb(new DateTime()),
                    'uid' => StringHelper::UUID(),
                ];

                $this->db->createCommand()
                    ->insert('{{%auth_oauth_tokens}}', $token)
                    ->execute();
            }
        }   

        return true;
    }

    public function safeDown(): bool
    {
        echo "m241021_000000_verbb cannot be reverted.\n";
        return false;
    }
}
