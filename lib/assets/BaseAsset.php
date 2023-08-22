<?php
namespace ThumbOnDemand\assets;

use yii\bootstrap\BootstrapAsset as Bootstrap3Asset;
use yii\bootstrap5\BootstrapAsset as Bootstrap5Asset;

use ThumbOnDemand\helpers\Html;

/**
 *
 */
class BaseAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/asset';

    public function init()
    {
        parent::init();

        if (5 == Html::getBootstrapVersion()) {
            $this->depends[] = Bootstrap5Asset::class;
        } else {
            $this->depends[] = Bootstrap3Asset::class;
        }
    }
}