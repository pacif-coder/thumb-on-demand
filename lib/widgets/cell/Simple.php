<?php
namespace ThumbOnDemand\widgets\cell;

use yii\base\InvalidConfigException;

/**
 *
 */
class Simple extends \ThumbOnDemand\widgets\Cell
{
    public $nameAttr;

    public function init()
    {
        parent::init();

        if (empty($this->nameAttr)) {
            throw new InvalidConfigException('The "nameAttr" property must be set.');
        }
    }

    protected function getName($model, $key, $index)
    {
        $attr = $this->nameAttr;
        return $model->{$attr};
    }
}