<?php
namespace ThumbOnDemand;

use Yii;

/**
 */
class CreateThumbAction extends \yii\base\Action
{
    public function run()
    {
        $path = Yii::$app->thumb->createThumbByUrl();
        Yii::$app->response->sendFile($path, null, ['inline' => true])->send();
    }
}