<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class ThumbAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/asset';

    public $css = [
        'css/thumb.css',
    ];

    public $depends = [
        BootstrapAsset::class,
    ];
}