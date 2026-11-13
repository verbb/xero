# Console Commands

Xero includes console commands you can run from your Craft project’s directory.

## Orders

::: reference
### `commerce-xero/orders/send`

Sends a Commerce order to every enabled Xero organisation straight away, the same as clicking **Send to Xero** when editing the order. Use it to send an order again after fixing a sync error, once you’ve checked that Xero doesn’t already have an invoice for it. The order must be enabled, completed and paid, and its payment gateway mustn’t be excluded in the plugin settings. Otherwise the command reports that it can’t find an eligible order and exits with an error.

| Option | Description | Default |
| --- | --- | --- |
| `--order-id` | The ID of the order to send. Required. | |

```shell
php craft commerce-xero/orders/send --order-id=123
```
:::
