<?php
namespace ThumbOnDemand\widgets;

use Yii;
use yii\widgets\ActiveForm;
use yii\db\ActiveRecord;

use ThumbOnDemand\assets\UploadImageAsset;
use ThumbOnDemand\helpers\Html;

/**
 *
 *
 */
class UploadImage extends \yii\widgets\InputWidget
{
    public $multiple = false;

    public $preset = 'thumb-1';

    public $altAttr = 'alt';

    public $thumbNoBorder;

    public $thumbBackgroundClass;

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

        $isBs3 = 3 == Html::getBootstrapVersion();
        $validationInput = $this->field->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT;
        if ($isBs3 && $validationInput) {
            $this->field->addErrorClassIfNeeded($this->options);
        }

        $id = Html::getInputId($this->model, $this->attribute);
        $name = Html::getInputName($this->model, $this->attribute);

        $attrs = ['disabled' => 'disabled', 'id' => self::getDeleteInputID($id)];
        $hidden = Html::hiddenInput($name, '', $attrs);
        $file = Html::fileInput($name, null, ['id' => $id]);

        $label = Yii::t('yii', 'Please upload a file.');
        $select = Html::tag('span', $label, ['class' => 'select-image']);
        $label = Html::tag('label', $select . $hidden . $file, ['for' => $this->getId()]);

        $attrs = ['class' => 'select-panel-content'];
        $content = Html::tag('div', $label, $attrs);

        $str = '';
        $str .= Html::tag('div', $content, ['class' => 'select-panel']);

        if (is_a($this->model, ActiveRecord::class) && $this->model->isNewRecord) {
            $originUrl = $thumbUrl = null;
        } else {
            $thumbUrl = $this->model->getThumbUrl($this->attribute, $this->preset);
            $originUrl = $this->model->getOriginUrl($this->attribute);
        }

        $thumb = $this->getThumb($originUrl, $thumbUrl);
        $str .= Html::tag('div', $thumb, ['class' => 'single-image']);

        $attrs = [
            'data-role' => 'thumb-on-demand-upload-image',
            'class' => 'thumb-on-demand thumb-on-demand-upload-image clearfix',
        ];

        if ($this->altAttr) {
            $id = Html::getInputId($this->model, $this->altAttr);
            $attrs['data-alt-attr-input'] = $id;
        }

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

        $attrs = [];
        if ($this->thumbNoBorder) {
            Html::addCssClass($attrs, 'no-border');
        }

        if ($this->thumbBackgroundClass) {
            Html::addCssClass($attrs, $this->thumbBackgroundClass);
        }

        if ($thumbUrl) {
           $img = Html::img($thumbUrl, $attrs);
           $img = Html::a($img, $originUrl, ['target' => '_new']);
        } else {
           $img = Html::tag('img', $attrs);
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

            $align = 5 == Html::getBootstrapVersion()? 'float-start' : 'pull-left';
            $icons .= Html::tag('div', $icon, ['class' => $align]);
        }

        $attrs = ['data-role' => 'remove', 'class' => 'text-danger'];
        $icon = Html::icon('remove', $attrs);

        $align = 5 == Html::getBootstrapVersion()? 'float-end' : 'pull-right';
        $icons .= Html::tag('div', $icon, ['class' => $align]);

        $attrs = ['class' => 'tools clearfix'];
        return Html::tag('div', $icons, $attrs);
    }

    public static function getDeleteInputID($id)
    {
        return "_thumb-on-demand-upload-image__delete-input_{$id}";
    }
}