<?php
namespace ThumbOnDemand\widgets;

use Yii;
use ThumbOnDemand\helpers\Html;
use yii\widgets\ActiveForm;

use ThumbOnDemand\assets\UploadFileAsset;

/**
 *
 *
 */
class UploadFile extends \yii\widgets\InputWidget
{
    public $withDelete = true;

    public function init()
    {
        parent::init();

        $view = $this->getView();
        $view->registerAssetBundle(UploadFileAsset::class);
    }

    /**
     *
     */
    public function run()
    {
        // https://github.com/yiisoft/yii2/pull/795
        if ($this->field->inputOptions !== ['class' => 'form-control']) {
            $this->options = array_merge($this->field->inputOptions, $this->options);
        }

        // https://github.com/yiisoft/yii2/issues/8779
        if (!isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }

        $isBs3 = 3 == Html::getBootstrapVersion();
        $validationInput = $this->field->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT;
        if ($isBs3 && $validationInput) {
            $this->field->addErrorClassIfNeeded($this->options);
        }

        $link = $fileUpload = '';
        $dropInputAttrs = ['data-role' => 'drop', 'disabled' => 'disabled', 'id' => null, 'value' => ''];
        $fileInputAttrs = ['data-role' => 'file'];

        if (isset($this->options['class'])) {
            Html::addCssClass($fileInputAttrs, $this->options['class']);
        }

        if ($this->hasModel()) {
            $dropInput = Html::activeInput('hidden', $this->model, $this->attribute, $dropInputAttrs);
            $fileInput = Html::activeInput('file', $this->model, $this->attribute, $fileInputAttrs);
        } else {
            $dropInput = Html::input('hidden', $this->name, '', $dropInputAttrs);
            $fileInput = Html::input('file', $this->name, '', $fileInputAttrs);
        }

        $str = $dropInput;
        $trashButtonAttrs = ['data-role' => 'remove', 'data-confirm' => Yii::t('yii', 'Delete') . '?'];
        $value = Html::getAttributeValue($this->model, $this->attribute);
        if ($this->hasModel() && $value) {
            $link = Html::a($value, $this->model->getUrl($this->attribute), ['target' => '_new']);

            $trash = '';
            if ($this->withDelete) {
                $trashButtonAttrs['data-fill'] = 'true';
                $trash = Html::tag('span', Html::icon('trash'), $trashButtonAttrs);
            }

            // $str .= Html::tag('div', $link . $trash);
            $str .= $fileInput  . $link . $trash;
        } else {
            $trashButtonAttrs['class'] = 'hidden';
            $trash = Html::tag('span', Html::icon('trash'), $trashButtonAttrs);

            $str = $fileInput . $trash;
        }

        echo Html::tag('div', $str, ['data-role' => 'widget-file-input', 'class' => 'widget-file-input']);
    }
}