<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class UploadImageAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/asset';

    public $css = [
        'css/upload-image.css',
    ];

    public $js = [
        'js/upload-image.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        ThumbAsset::class,
    ];
}