<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class UploadImageAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/upload-image';

    public $css = [
        'upload-image.css',
    ];

    public $js = [
        'upload-image.js',
    ];

    public $depends = [
        BootstrapAsset::class,
    ];
}