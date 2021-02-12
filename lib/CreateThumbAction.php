<?php
namespace ThumbOnDemand;

use Yii;

/**
 */
class CreateThumbAction extends \yii\base\Action
{
    public function run()
    {
        $path = Yii::$app->thumb->createThumb();
        Yii::$app->response->sendFile($path, null, ['inline' => true])->send();
    }
}