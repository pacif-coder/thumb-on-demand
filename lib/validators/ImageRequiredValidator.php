<?php
namespace ThumbOnDemand\validators;

use Yii;

/**
 *
 */
class ImageRequiredValidator extends \yii\validators\ImageValidator
{
    public function validateAttributes($model, $attributes = null)
    {
        $attributes = $this->getValidationAttributes($attributes);

        foreach ($attributes as $attribute) {
            if ($this->skipOnError && $model->hasErrors($attribute)) {
                continue;
            }

            $val = $model->$attribute;
            $this->skipOnEmpty = $model->getOldAttribute($attribute) && null !== $val && '' !== $val;
            if ($this->skipOnEmpty && $this->isEmpty($model->$attribute)) {
                continue;
            }

            if ($this->when === null || call_user_func($this->when, $model, $attribute)) {
                $this->validateAttribute($model, $attribute);
            }
        }
    }
}