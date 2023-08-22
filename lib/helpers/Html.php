<?php
namespace ThumbOnDemand\helpers;

use Yii;
use yii\bootstrap\Html as Bootstrap3Html;

/**
 *
 */
class Html extends \yii\helpers\Html
{
    protected static $bootstrapIcon3to5Map = [
        'remove' => 'x-lg',
        'move' => 'arrows-move',
    ];

    public static function icon($icon, $classOrAttrs = '')
    {
        $attrs = [];
        if (is_array($classOrAttrs)) {
            $attrs = $classOrAttrs;
        }

        if (5 == self::getBootstrapVersion()) {
            $icon = self::$bootstrapIcon3to5Map[$icon] ?? $icon;
            $iconClass = "bi bi-{$icon}";

            self::addCssClass($attrs, $iconClass);

            return Html::tag('i', '', $attrs);
        }

        return Bootstrap3Html::icon($icon, $attrs);
    }

    public static function getBootstrapVersion()
    {
        if (isset(Yii::$app->extensions['yiisoft/yii2-bootstrap5'])) {
            return 5;
        }

        return 3;
    }
}