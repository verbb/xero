# Configuration
Create a `commerce-xero.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Xero, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Xero',
        'excludedGateways' => [],
    ]
];
```

## Configuration options
- `pluginName` - If you wish to customise the plugin name.
- `excludedGateways` - An array of Commerce payment gateway handles. Orders paid with these gateways will not be synced to Xero.


## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Xero.
