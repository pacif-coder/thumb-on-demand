<?php
namespace ThumbOnDemand\widgets;

use yii\helpers\Html;

use Yii;
use yii\db\ActiveQueryInterface;
use yii\helpers\Json;

use ThumbOnDemand\widgets\cell\Simple;
use ThumbOnDemand\assets\MatrixGridAsset;

/**
 */
class MatrixGridView extends \yii\widgets\BaseListView
{
    public $cell = Simple::class;

    public $options = [
        'class' => 'matrix-grid-view',
        'data-role' => 'matrix-grid-view',
        'data-is-grid' => 1,
    ];

    public $bodyAttrs = [
        'class' => 'thumb-on-demand thumb-on-demand-images-grid clearfix',
        'data-role' => 'matrix-grid-view-body',
    ];

    protected static $noJsOptions = [
        'id', 'class', 'data-role', 'tag',
    ];

    public function renderItems(): string
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        $items = '';
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            $items .= $this->cell->render($model, $key, $index) . "\n";
        }

        return Html::tag('div', $items, $this->bodyAttrs);
    }

    public function run()
    {
        $view = $this->getView();
        MatrixGridAsset::register($view);

        $this->registerJs();
        $this->initCell();

        parent::run();
    }

    protected function initCell()
    {
        /* @var $cell Cell */
        $this->cell = Yii::createObject($this->cell);
        $this->cell->grid = $this;

        if (!$this->cell->modelClass && $this->dataProvider->query instanceof ActiveQueryInterface) {
            $this->cell->modelClass = $this->dataProvider->query->modelClass;
        }
    }

    protected function registerJs()
    {
        $view = $this->getView();

        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view->registerJs("jQuery('#$id').matrixGridView($options);");
    }

    protected function getClientOptions()
    {
        $options = [];
        foreach ($this->options as $key => $value) {
            if (!in_array($key, static::$noJsOptions)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}