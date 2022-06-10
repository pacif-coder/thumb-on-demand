<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class UploadFileAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/asset';

    public $css = [
        'css/upload-file.css',
    ];

    public $js = [
        'js/upload-file.js',
    ];

    public $depends = [
        BootstrapAsset::class,
    ];
}