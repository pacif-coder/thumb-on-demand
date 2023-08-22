<?php
namespace ThumbOnDemand\assets;

use yii\jui\JuiAsset;

/**
 *
 */
class MatrixGridAsset extends BaseAsset
{
    public $js = [
        'js/matrix-grid.js',
    ];

    public $css = [
        'css/matrix-grid.css',
    ];

    public $depends = [
        JuiAsset::class,
        ThumbAsset::class,
    ];
}