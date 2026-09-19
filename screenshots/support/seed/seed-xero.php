/** Seed a completed zero-balance order so the real Xero order action is available. */

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\Json;
use verbb\xero\Xero;

$site = Craft::$app->getSites()->getPrimarySite();
$store = Commerce::getInstance()->getStores()->getPrimaryStore();
$order = Order::find()->email('accounts@example.com')->isCompleted(true)->one();

if (!$order) {
    $order = new Order([
        'orderSiteId' => $site->id,
        'storeId' => $store->id,
        'currency' => $store->getCurrency(),
        'paymentCurrency' => $store->getCurrency(),
    ]);
    $order->setEmail('accounts@example.com');

    if (!Craft::$app->getElements()->saveElement($order, false)) {
        throw new RuntimeException('Unable to save the Xero screenshot order: ' . Json::encode($order->getErrors()));
    }

    if (!$order->markAsComplete()) {
        throw new RuntimeException('Unable to complete the Xero screenshot order.');
    }
}

Craft::$app->getPlugins()->savePluginSettings(Xero::$plugin, [
    'clientId' => 'screenshot-client-id',
    'clientSecret' => 'screenshot-client-secret',
]);

echo Json::encode([
    'organisationRoute' => '/admin/screenshot-xero/organisation',
    'orderRoute' => '/admin/commerce/orders/' . $order->id,
], JSON_THROW_ON_ERROR);
