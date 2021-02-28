<?php

namespace thejoshsmith\commerce\xero\factories;

use thejoshsmith\commerce\xero\Plugin;
use thejoshsmith\commerce\xero\models\XeroClient as XeroClientModel;

use XeroPHP\Application as XeroApplication;

use yii\base\Exception;

/**
 * Factory class for building a configured Xero Client
 *
 * @author Josh Smith <by@joshthe.dev>
 * @since  1.0.0
 */
class XeroClient
{
    /**
     * Creates an authenticated Xero client
     *
     * @param integer $siteId Defaults to the current site ID
     * @param string  $status Defaults to the connection enabled status
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @throws Exception
     * @return void
     */
    public static function build(
        int $siteId = null,
        array $where = []
    ): XeroClientModel {

        // Fetch the connection record, eager loading required access info
        $connection = Plugin::getInstance()
            ->getXeroConnections()
            ->getCurrentConnection(['credential', 'resourceOwner', 'tenant']);

        if (empty($connection)) {
            throw new Exception('No connection record found');
        }

        $tenant = $connection->tenant;
        $credential = $connection->credential;
        $resourceOwner = $connection->resourceOwner;

        $application = self::buildApplication($credential->accessToken, $tenant->tenantId);

        return new XeroClientModel(
            $application,
            $connection,
            $credential,
            $resourceOwner,
            $tenant
        );
    }

    public static function buildApplication(
        string $accessToken = '',
        string $tenantId = ''
    ): XeroApplication {
        return new XeroApplication(
            $accessToken,
            $tenantId
        );
    }
}
