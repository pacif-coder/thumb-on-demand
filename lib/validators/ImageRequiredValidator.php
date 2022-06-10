<?php
namespace ThumbOnDemand\validators;

/**
 *
 */
class ImageRequiredValidator extends \yii\validators\ImageValidator
{
    use FileRequired;
}