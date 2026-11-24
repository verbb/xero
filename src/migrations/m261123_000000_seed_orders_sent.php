<?php
namespace verbb\xero\migrations;

use verbb\xero\services\Service;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Sequence;

use verbb\auth\Auth;

/**
 * Orders sent before the orders-sent counter existed weren't counted. A site with a connected organisation whose
 * accounts are mapped has been sending orders, so it starts the counter at one to keep the landing page's setup
 * checklist from coming back after the update.
 */
class m261123_000000_seed_orders_sent extends Migration
{
    public function safeUp(): bool
    {
        if ((int)Sequence::current(Service::ORDERS_SENT_SEQUENCE) > 0) {
            return true;
        }

        $organisations = (new Query())
            ->select(['id', 'accountSales', 'accountReceivable', 'accountShipping', 'accountRounding'])
            ->from('{{%xero_organisations}}')
            ->all();

        $tokens = Auth::getInstance()->getTokens();

        foreach ($organisations as $organisation) {
            if (!$organisation['accountSales'] || !$organisation['accountReceivable'] || !$organisation['accountShipping'] || !$organisation['accountRounding']) {
                continue;
            }

            if ($tokens->getTokenByOwnerReference('commerce-xero', (string)$organisation['id'])) {
                Sequence::next(Service::ORDERS_SENT_SEQUENCE);

                break;
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        // The counter is only read by the setup checklist, so it can stay where it is.
        return true;
    }
}
