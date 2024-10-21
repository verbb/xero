# Usage
You'll need to sign up for a free Xero account and add a OAuth 2.0 Xero App. You can view Xero's [getting started guide](https://developer.xero.com/documentation/getting-started/getting-started-guide) for details on this.

Once your Xero app has been created, enter the Client ID and Secret from your Xero app in Xero's plugin settings.

## Connecting an Organisation
Navigate to **Organisations** to create an Organisation, and save it. Click the **Connect** button, and proceed to authorise with Xero, selecting the organisation you wish to link in Xero.

Create multiple organisations as required, every enabled organisation will be used to send data to.

## Organisation Settings
Once an organisation is connected the plugin allows you to map your Chart of Accounts (Account Codes) including:

- Sales Revenue
- Accounts Receivable
- Shipping/Delivery
- Rounding

By default, all fully paid orders will be pushed into the Craft Queue with a delay of 30 seconds, after which the invoice will be sent to Xero.

You can also send existing orders to Xero when editing an order, and clicking the **Send to Xero** button.
