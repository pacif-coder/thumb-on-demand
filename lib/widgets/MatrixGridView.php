<?php
namespace ThumbOnDemand\widgets;

use yii\bootstrap\Html;

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
    ];

    protected static $noJsOptions = [
        'id', 'class', 'data-role', 'tag',
    ];

    public function renderItems(): string
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        /*@var $cell Cell */
        if (is_array($this->cell) && !isset($this->cell['class'])) {
            $this->cell['class'] = Simple::class;
        }
        $this->cell = Yii::createObject($this->cell);

        $this->cell->matrixGrid = $this;

        $this->cell->modelClass = null;
        if ($this->dataProvider->query instanceof ActiveQueryInterface) {
            $this->cell->modelClass = $this->dataProvider->query->modelClass;
        }

        $items = '';
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            $items .= $this->cell->render($model, $key, $index) . "\n";
        }

        return Html::tag('div', $items,
                ['class' => 'thumb-on-demand thumb-on-demand-images-grid']);
    }

    public function run()
    {
        $view = $this->getView();
        MatrixGridAsset::register($view);

        $this->registerJs();
        parent::run();
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