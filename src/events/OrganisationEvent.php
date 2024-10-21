<?php
namespace verbb\xero\events;

use verbb\xero\models\Organisation;

use yii\base\Event;

class OrganisationEvent extends Event
{
    // Properties
    // =========================================================================

    public Organisation $organisation;
    public bool $isNew = false;

}
