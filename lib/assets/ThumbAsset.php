<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class ThumbAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/thumb';

    public $css = [
        'thumb.css',
    ];

    public $depends = [
        BootstrapAsset::class,
    ];
}