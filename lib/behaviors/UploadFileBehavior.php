<?php
namespace ThumbOnDemand\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 *
 */
class UploadFileBehavior extends \yii\base\Behavior
{
    public $attr;

    public $nameAttr;

    public $filesizeAttr;

    public $fileextAttr;

    protected $files = [];

    protected $uploaded = [];

    protected $toDelete = [];

    public function beforeValidate(\yii\base\ModelEvent $event)
    {
        $this->uploaded = $this->toDelete = [];

        foreach ((array) $this->attr as $attr) {
            $old = $this->owner->getOldAttribute($attr);
            $new = $this->owner->getAttribute($attr);

            // if for some reason the attribute value is already an object of UploadedFile class - use it
            // it can be useful for manual assignment of an uploaded file
            if ($new && is_a($new, UploadedFile::class)) {
                $uploaded = $new;
            } else {
                $uploaded = UploadedFile::getInstance($this->owner, $attr);
            }

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

            $this->owner->setAttribute($attr, $uploaded);
            $this->uploaded[] = $attr;
        }
    }

    public function getUrl($attr)
    {
        return Yii::$app->file->getUrl($this->owner, $attr);
    }

    public function setOriginFile($attr, $path)
    {
        $old = $this->owner->getOldAttribute($attr);
        if ($old) {
            $this->remove($attr, $old);
        }

        $file = Yii::$app->file->getOriginFilename($path);
        $this->owner->{$attr} = $file;
        $this->files[$attr] = $path;

        $this->filePath2fileProps($attr, $path, $path, true);
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
            $this->path2name($uploaded->name);
        }

        if ($this->owner->hasErrors()) {
            $event->isValid = false;
        }
    }

    public function afterSave()
    {
        foreach ((array) $this->attr as $attr) {
            if (!in_array($attr, $this->uploaded)) {
                continue;
            }

            Yii::$app->file->saveUploaded($this->owner, $attr, $this->owner->{$attr});
        }
        $this->uploaded = [];

        foreach ($this->files as $attr => $path) {
            Yii::$app->file->saveOriginFile($this->owner, $attr, $path);
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
            Yii::$app->file->delete($this->owner, $attr, $file);
        }
        $this->toDelete = [];
    }

    protected function filePath2fileProps($attr, $path, $name, $withName = false)
    {
        if ($this->filesizeAttr) {
            $ownerAttr = $this->filesizeAttr;
            $this->owner->{$ownerAttr} = filesize($path);
        }

        if ($this->fileextAttr) {
            $ownerAttr = $this->fileextAttr;
            $this->owner->{$ownerAttr} = pathinfo($name, PATHINFO_EXTENSION);
        }

        if ($withName) {
            $this->path2name($path);
        }

        return true;
    }

    protected function path2name($path)
    {
        if (!$this->nameAttr) {
            return;
        }

        $ownerAttr = $this->nameAttr;
        $this->owner->{$ownerAttr} = pathinfo($path, PATHINFO_FILENAME);
    }

    protected function remove($attr, $file)
    {
        $this->toDelete[$attr] = $file;

        $this->owner->{$attr} = null;
        foreach (['filesizeAttr', 'fileextAttr', 'nameAttr'] as $tmpAttr) {
            if (!$this->{$tmpAttr}) {
                continue;
            }

            $ownerAttr = $this->{$tmpAttr};
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