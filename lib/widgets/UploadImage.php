<?php
namespace ThumbOnDemand\widgets;

use yii\bootstrap\Html;
use yii\widgets\ActiveForm;

use ThumbOnDemand\assets\UploadImageAsset;

/**
 *
 *
 */
class UploadImage extends \yii\bootstrap\InputWidget
{
    public $multiple = false;

    public $preset = 'thumb-1';

    public $altAttr = 'alt';

    public function init()
    {
        parent::init();

        $view = $this->getView();
        $view->registerAssetBundle(UploadImageAsset::class);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // https://github.com/yiisoft/yii2/issues/8779
        if (!isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }

        if ($this->field->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->field->addErrorClassIfNeeded($this->options);
        }

        $name = Html::getInputName($this->model, $this->attribute);
        $hidden = Html::hiddenInput($name, '', ['disabled' => 'disabled']);
        $file = Html::fileInput($name);

        $select = Html::tag('span', 'Выберите файл', ['class' => 'select-image']);
        $label = Html::tag('label', $select . $hidden . $file, ['for' => $this->getId()]);

        if ($this->altAttr) {
            $altAttr = $this->altAttr;
            $name = Html::getInputName($this->model, $this->altAttr);
            $alt = $this->model->{$altAttr};
        } else {
            $alt = '';
            $name = null;
        }
        $textarea = Html::textarea($name, $alt, ['data-role' => 'alt']);

        $attrs = ['class' => 'select-panel-content'];
        $content = Html::tag('div', $label . $textarea, $attrs);

        $str = '';
        $str .= Html::tag('div', $content, ['class' => 'select-panel']);

        $thumbUrl = $this->model->getThumbUrl($this->attribute, $this->preset);
        $originUrl = $this->model->getOriginUrl($this->attribute);
        $thumb = $this->getThumb($originUrl, $thumbUrl);
        $str .= Html::tag('div', $thumb, ['class' => 'single-image']);

        $attrs = ['data-role' => 'thumb-on-demand-upload-image',
            'class' => 'thumb-on-demand-upload-image clearfix'];

        if ($this->multiple) {
            Html::addCssClass($attrs, 'multiple');
        } else {
            Html::addCssClass($attrs, 'single');
        }

        echo Html::tag('div', $str, $attrs);
    }

    protected function getThumb($originUrl, $thumbUrl, $template = false)
    {
        $body = $this->getTools();

        if ($thumbUrl) {
           $img = Html::img($thumbUrl);
           $img = Html::a($img, $originUrl, ['target' => '_new']);
        } else {
           $img = Html::tag('img');
        }

        $attrs = ['class' => 'img'];
        $body .= Html::tag('div', $img, $attrs);

        $attrs = ['class' => 'thumb'];
        if (!$thumbUrl) {
            Html::addCssClass($attrs, 'disabled');
        }

        return Html::tag('div', $body, $attrs);
    }

    protected function getTools()
    {
        $icons = '';
        if ($this->multiple) {
            $icon = Html::icon('move', ['class' => 'text-primary']);
            $icons .= Html::tag('div', $icon, ['class' => 'pull-left']);
        }

        $attrs = ['data-role' => 'remove', 'class' => 'text-danger'];
        $icon = Html::icon('remove', $attrs);
        $icons .= Html::tag('div', $icon, ['class' => 'pull-right']);

        $attrs = ['class' => 'tools clearfix'];
        return Html::tag('div', $icons, $attrs);
    }
}