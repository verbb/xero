# Events
Xero provides a collection of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Xero’s behavior.

## Order Events

### The `beforeSendOrder` event
The event that is triggered before an order is sent to Xero. Event handlers can prevent the order from being sent by setting `$event->isValid` to `false`.

```php
use verbb\xero\events\OrderEvent;
use verbb\xero\services\Service;

use yii\base\Event;

Event::on(Service::class, Service::EVENT_BEFORE_SEND_ORDER, function(OrderEvent $event) {
    $order = $event->order;

    // Prevent this order from being sent to Xero
    $event->isValid = false;
});
```

### The `afterSendOrder` event
The event that is triggered after an order has been successfully sent to Xero.

```php
use verbb\xero\events\OrderEvent;
use verbb\xero\services\Service;

use yii\base\Event;

Event::on(Service::class, Service::EVENT_AFTER_SEND_ORDER, function(OrderEvent $event) {
    $order = $event->order;
    // ...
});
```

## Organisation Events

### The `beforeSaveOrganisation` event
The event that is triggered before a organisation is saved.

```php
use verbb\xero\events\OrganisationEvent;
use verbb\xero\services\Organisations;

use yii\base\Event;

Event::on(Organisations::class, Organisations::EVENT_BEFORE_SAVE_ORGANISATION, function(OrganisationEvent $event) {
    $organisation = $event->organisation;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveOrganisation` event
The event that is triggered after a organisation is saved.

```php
use verbb\xero\events\OrganisationEvent;
use verbb\xero\services\Organisations;

use yii\base\Event;

Event::on(Organisations::class, Organisations::EVENT_AFTER_SAVE_ORGANISATION, function(OrganisationEvent $event) {
    $organisation = $event->organisation;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteOrganisation` event
The event that is triggered before a organisation is deleted.

```php
use verbb\xero\events\OrganisationEvent;
use verbb\xero\services\Organisations;

use yii\base\Event;

Event::on(Organisations::class, Organisations::EVENT_BEFORE_DELETE_ORGANISATION, function(OrganisationEvent $event) {
    $organisation = $event->organisation;
    // ...
});
```

### The `afterDeleteOrganisation` event
The event that is triggered after a organisation is deleted.

```php
use verbb\xero\events\OrganisationEvent;
use verbb\xero\services\Organisations;

use yii\base\Event;

Event::on(Organisations::class, Organisations::EVENT_AFTER_DELETE_ORGANISATION, function(OrganisationEvent $event) {
    $organisation = $event->organisation;
    // ...
});
```
