# Events
Xero provides a collection of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Xero’s behavior.

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
