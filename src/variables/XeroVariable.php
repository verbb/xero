<?php
namespace verbb\xero\variables;

use verbb\xero\Xero;

class XeroVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): Xero
    {
        return Xero::$plugin;
    }

    public function getPluginName(): string
    {
        return Xero::$plugin->getPluginName();
    }
    
}