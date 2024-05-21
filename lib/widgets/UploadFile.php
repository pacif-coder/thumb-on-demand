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
    public $nameAttr;

    public $withDelete = true;

    public $url;

    public $containerDataRole = 'widget-upload-file';

    public $containerClass = 'input-group widget-upload-file';

    protected $dropInputAttrs = [
        'data-role' => 'drop',
        'disabled' => 'disabled',
        'id' => null,
        'value' => '',
    ];

    protected $fileInputAttrs = [
        'data-role' => 'file',
    ];

    protected $trashButtonAttrs = [
        'data-role' => 'remove',
    ];

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

        $fileInputAttrs = $this->fileInputAttrs;
        if (isset($this->options['class'])) {
            Html::addCssClass($fileInputAttrs, $this->options['class']);
        }

        $dropInput = $fileUpload = '';
        if ($this->hasModel()) {
            $dropInput = Html::activeInput('hidden', $this->model, $this->attribute, $this->dropInputAttrs);
            $fileInput = Html::activeInput('file', $this->model, $this->attribute, $fileInputAttrs);
        } else {
            $dropInput = Html::input('hidden', $this->name, '', $this->dropInputAttrs);
            $fileInput = Html::input('file', $this->name, '', $fileInputAttrs);
        }

        $str = $fileInput . $dropInput . $this->getLinkTag() . $this->getTrashTag();

        $attrs = ['data-role' => $this->containerDataRole, 'class' => $this->containerClass];
        if ($this->nameAttr && $this->model) {
            $attrs['data-name-attr-id'] = Html::getInputId($this->model, $this->nameAttr);
        }

        return Html::tag('div', $str, $attrs);
    }

    protected function getLinkTag()
    {
        if ($this->hasModel()) {
            $value = Html::getAttributeValue($this->model, $this->attribute);
        } else {
            $value = $this->value;
        }

        if (!$value) {
            return '';
        }

        $link = Html::a($value, $this->model->getUrl($this->attribute), ['target' => '_new']);
        $link = Html::tag('span', $link, ['class' => 'text-truncate']);
        return Html::tag('span', $link, ['class' => 'input-group-text w-50']);
    }

    protected function getTrashTag()
    {
        if (!$this->withDelete) {
            return '';
        }

        if ($this->hasModel()) {
            $value = Html::getAttributeValue($this->model, $this->attribute);
        } else {
            $value = $this->value;
        }

        if (!$value) {
            return '';
        }

        $trashButtonAttrs = $this->trashButtonAttrs;
        $trashButtonAttrs['data-confirm'] = Yii::t('yii', 'Delete') . '?';

        $trash = Html::tag('span', Html::icon('trash'), $trashButtonAttrs);

        return Html::tag('span', $trash, ['class' => 'input-group-text border-start-0']);
    }
}