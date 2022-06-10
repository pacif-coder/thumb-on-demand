<?php
namespace ThumbOnDemand\widgets;

use Yii;
use yii\bootstrap\Html;
use yii\base\InvalidConfigException;

/**
 *
 */
abstract class Cell extends \yii\base\BaseObject
{
    public $matrixGrid;

    public $layout = "{tools}\n{image}\n{name}";

    public $tools = '{remove}';

    public $toolIconAlign = [
        'remove' => 'right',
    ];

    /**
     * @var array|Closure the HTML attributes for the thumb. This can be either an array
     * specifying the common HTML attributes for all thumbs, or an anonymous function that
     * returns an array of the HTML attributes. The anonymous function will be called once for every
     * data model returned by [[dataProvider]]. It should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $grid)
     * ```
     *
     * - `$model`: the current data model being rendered
     * - `$key`: the key value associated with the current data model
     * - `$index`: the zero-based index of the data model in the model array returned by [[dataProvider]]
     * - `$matrixGrid`: the MatrixGridView object
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $thumbOptions = ['class' => 'thumb', 'data-role' => 'thumb'];

    public $imgAttr;

    public $thumbPreset;

    public $modelClass;

    /**
     * @var string the name of the input checkbox input fields. This will be appended with `[]` to ensure it is an array.
     */
    public $name = 'selection';

    /**
     * @var array|\Closure the HTML attributes for checkboxes. This can either be an array of
     * attributes or an anonymous function ([[Closure]]) that returns such an array.
     * The signature of the function should be the following: `function ($model, $key, $index, $column)`.
     * Where `$model`, `$key`, and `$index` refer to the model, key and index of the row currently being rendered
     * and `$column` is a reference to the [[CheckboxColumn]] object.
     * A function may be used to assign different attributes to different rows based on the data in that row.
     * Specifically if you want to set a different value for the checkbox
     * you can use this option in the following way (in this example using the `name` attribute of the model):
     *
     * ```php
     * 'checkboxOptions' => function ($model, $key, $index, $cell) {
     *     return ['value' => $model->name];
     * }
     * ```
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $checkboxOptions = ['data-role' => 'selection'];

    /**
     * @var bool whether it is possible to select multiple rows. Defaults to `true`.
     */
    // public $multiple = true;

    /**
     * @var string the css class that will be used to find the checkboxes.
     */
    public $cssClass;

    public function init()
    {
        parent::init();

        if (empty($this->imgAttr)) {
            throw new InvalidConfigException('The "imgAttr" property must be set.');
        }

        if (empty($this->thumbPreset)) {
            throw new InvalidConfigException('The "thumbPreset" property must be set.');
        }

        if (substr_compare($this->name, '[]', -2, 2)) {
            $this->name .= '[]';
        }
    }

    public function render($model, $key, $index)
    {
        $str = '';
        $matchs = [];
        if (!preg_match_all('/{(\w+)}/', $this->layout, $matchs)) {
            return $str;
        }

        foreach ($matchs[1] as $part) {
            $method = "render{$part}";
            $str .= $this->{$method}($model, $key, $index);
        }

        if ($this->thumbOptions instanceof \Closure) {
            $options = call_user_func($this->thumbOptions, $model, $key, $index, $this->matrixGrid);
        } else {
            $options = $this->thumbOptions;
        }

        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;

        return Html::tag('div', $str, $options);
    }

    protected function renderTools($model, $key, $index)
    {
        $matchs = [];
        if (!preg_match_all('/{(\w+)}/', $this->tools, $matchs)) {
            return;
        }

        $align2icons = ['left' => '', '' => '', 'right' => ''];
        foreach ($matchs[1] as $part) {
            $align = 'left';
            if (isset($this->toolIconAlign[$part])) {
                $align = $this->toolIconAlign[$part];
            }

            $method = "renderTool{$part}";
            $align2icons[$align] .= $this->{$method}($model, $key, $index);
        }

        $str = '';
        foreach ($align2icons as $align => $icons) {
            if ($align) {
                $str .= Html::tag('div', $icons, ['class' => "pull-{$align}"]);
            } else {
                $str .= $icons;
            }
        }

        $attrs = ['class' => 'tools clearfix'];
        return Html::tag('div', $str, $attrs);
    }

    protected function renderToolCheckbox($model, $key, $index)
    {
        if ($this->checkboxOptions instanceof \Closure) {
            $options = call_user_func($this->checkboxOptions, $model, $key, $index, $this);
        } else {
            $options = $this->checkboxOptions;
        }

        if (!isset($options['value'])) {
            $options['value'] = is_array($key) ? Json::encode($key) : $key;
        }

        if ($this->cssClass !== null) {
            Html::addCssClass($options, $this->cssClass);
        }

        return Html::checkbox($this->name, !empty($options['checked']), $options);
    }

    protected function renderToolMove($model, $key, $index)
    {
        $attrs = ['data-role' => 'move', 'class' => 'text-primary'];
        return Html::icon('move', $attrs);
    }

    protected function renderToolRemove($model, $key, $index)
    {
        $attrs = ['data-role' => 'remove', 'class' => 'text-danger'];
        return Html::icon('remove', $attrs);
    }

    protected function renderImage($model, $key, $index)
    {
        if (is_object($model)) {
            $thumbUrl = $model->getThumbUrl($this->imgAttr, $this->thumbPreset);
            $originUrl = $model->getOriginUrl($this->imgAttr);
        } else {
            Yii::$app->thumb->modelClass = $this->modelClass;

            $thumbUrl = Yii::$app->thumb->getThumbUrl($model, $this->imgAttr,
                            $this->thumbPreset);
            $originUrl = Yii::$app->thumb->getOriginUrl($model, $this->imgAttr);
        }

        if ($thumbUrl) {
           $img = Html::img($thumbUrl);
           $img = Html::a($img, $originUrl, ['target' => '_new']);
        } else {
           $img = Html::tag('img');
        }

        return Html::tag('div', $img, ['class' => 'img']);
    }

    protected function renderName($model, $key, $index)
    {
        $name = $this->getName($model, $key, $index);

        $text = Html::tag('div', $name, ['class' => 'text']);
        return Html::tag('div', $text, ['class' => 'title']);
    }

    abstract protected function getName($model, $key, $index);
}