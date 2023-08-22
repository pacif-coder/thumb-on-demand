<?php
namespace ThumbOnDemand\assets;

/**
 *
 */
class UploadImageAsset extends BaseAsset
{
    public $css = [
        'css/upload-image.css',
    ];

    public $js = [
        'js/upload-image.js',
    ];

    public $depends = [
        ThumbAsset::class,
    ];
}