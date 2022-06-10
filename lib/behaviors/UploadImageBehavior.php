<?php
namespace ThumbOnDemand\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 *
 */
class UploadImageBehavior extends \yii\base\Behavior
{
    public $attr;

    public $altAttr;

    public $widthAttr;

    public $heightAttr;

    public $filesizeAttr;

    protected $files = [];

    protected $uploaded = [];

    protected $toDelete = [];

    public function getOriginUrl($attr)
    {
        return Yii::$app->thumb->getUrl($this->owner, $attr);
    }

    public function setOriginFile($attr, $path)
    {
        $old = $this->owner->getOldAttribute($attr);
        if ($old) {
            $this->remove($attr, $old);
        }

        $file = Yii::$app->thumb->getOriginFilename($path);
        $this->owner->{$attr} = $file;
        $this->files[$attr] = $path;

        $this->filePath2fileProps($attr, $path, $path, true);
    }

    public function getThumbUrl($attr, $preset)
    {
        return Yii::$app->thumb->getThumbUrl($this->owner, $attr, $preset);
    }

    public function getThumbPath($attr, $preset)
    {
        return Yii::$app->thumb->getThumbPath($this->owner, $attr, $preset);
    }

    public function beforeValidate(\yii\base\ModelEvent $event)
    {
        $this->uploaded = $this->toDelete = [];

        foreach ((array) $this->attr as $attr) {
            $old = $this->owner->getOldAttribute($attr);
            $new = $this->owner->getAttribute($attr);
            $uploaded = UploadedFile::getInstance($this->owner, $attr);

            if ($uploaded && $uploaded->error) {
                $error = Yii::t('yii', 'File upload failed.');
                $this->owner->addError($attr, $error);
                continue;
            }

            $isDeleted = '' === $new;
            if ($old && ($isDeleted || $uploaded)) {
                $this->remove($attr, $old);
            }

            if (!$uploaded) {
                continue;
            }

            $this->owner->{$attr} = $uploaded;
            $this->uploaded[] = $attr;
        }
    }

    public function beforeSave(\yii\base\ModelEvent $event)
    {
        foreach ((array) $this->attr as $attr) {
            if (!in_array($attr, $this->uploaded)) {
                continue;
            }

            if ($this->owner->hasErrors($attr)) {
                continue;
            }

            $uploaded = $this->owner->{$attr};

            $this->filePath2fileProps($attr, $uploaded->tempName, $uploaded->name, false);
            $this->path2alt($uploaded->name);
        }

        if ($this->owner->hasErrors()) {
            $event->isValid = false;
        }
    }

    public function afterSave()
    {
        foreach ($this->toDelete as $attr => $file) {
            Yii::$app->thumb->delete($this->owner, $attr, $file);
        }
        $this->toDelete = [];

        foreach ((array) $this->attr as $attr) {
            if (!in_array($attr, $this->uploaded)) {
                continue;
            }

            Yii::$app->thumb->saveUploaded($this->owner, $attr, $this->owner->{$attr});
        }
        $this->uploaded = [];

        foreach ($this->files as $attr => $path) {
            Yii::$app->thumb->saveOriginFile($this->owner, $attr, $path);
        }
        $this->files = [];
    }

    public function beforeDelete()
    {
        $this->toDelete = [];
        foreach ((array) $this->attr as $attr) {
            $this->toDelete[$attr] = $this->owner->getAttribute($attr);
        }
    }

    public function afterDelete()
    {
        foreach ($this->toDelete as $attr => $file) {
            Yii::$app->thumb->delete($this->owner, $attr, $file);
        }
        $this->toDelete = [];
    }

    protected function filePath2fileProps($attr, $path, $name, $withAlt = false)
    {
        $info = @getimagesize($path);
        if (false === $info) {
            $error = Yii::t('yii', 'The file "{file}" is not an image.',
                        ['file' => $name]);
            $this->owner->addError($attr, $error);
            return false;
        }

        if ($this->filesizeAttr) {
            $ownerAttr = $this->filesizeAttr;
            $this->owner->{$ownerAttr} = filesize($path);
        }

        if ($withAlt) {
            $this->path2alt($path);
        }

        if (!$this->widthAttr && !$this->heightAttr) {
            return true;
        }

        if ($this->widthAttr) {
            $ownerAttr = $this->widthAttr;
            $this->owner->{$ownerAttr} = $info[0];
        }

        if ($this->heightAttr) {
            $ownerAttr = $this->heightAttr;
            $this->owner->{$ownerAttr} = $info[1];
        }

        return true;
    }

    protected function path2alt($path)
    {
        if (!$this->altAttr) {
            return;
        }

        $ownerAttr = $this->altAttr;
        $this->owner->{$ownerAttr} = pathinfo($path, PATHINFO_FILENAME);
    }

    protected function remove($attr, $file)
    {
        $this->toDelete[$attr] = $file;

        $this->owner->{$attr} = null;
        foreach (['widthAttr', 'heightAttr', 'filesizeAttr', 'altAttr'] as $tmpAttr) {
            if (!$this->{$tmpAttr}) {
                continue;
            }

            $ownerAttr = $this->{$tmpAttr};
            if (!$this->owner->hasProperty($ownerAttr)) {
                continue;
            }

            $this->owner->{$ownerAttr} = null;
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',

            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',

            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',

            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }
}