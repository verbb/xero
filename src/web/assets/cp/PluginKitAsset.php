<?php
namespace verbb\xero\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PluginKitAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/xero/web/assets/cp/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'plugin-kit.css',
        ];

        $this->js = [
            ['plugin-kit.js', 'type' => 'module'],
        ];

        parent::init();
    }
}
