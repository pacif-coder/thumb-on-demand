<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset;
use yii\jui\JuiAsset;

/**
 *
 */
class MatrixGridAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/matrix-grid';

    public $js = [
        'matrix-grid.js',
    ];

    public $css = [
        'matrix-grid.css',
    ];

    public $depends = [
        BootstrapAsset::class,
        JuiAsset::class,
        ThumbAsset::class,
    ];
}