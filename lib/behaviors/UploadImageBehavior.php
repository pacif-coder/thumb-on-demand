<?php
namespace ThumbOnDemand\behaviors;

use Yii;
use yii\base\InvalidConfigException;
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

    public $fileSizeAttr;

    public $forceModelClass = false;

    public $componentName = 'thumb';

    /**
     * @var \ThumbOnDemand\Thumb The Thumb application component
     */
    protected static $component;

    protected $files = [];

    protected $uploaded = [];

    protected $deleted = [];

    public function init()
    {
        parent::init();

        // XXX add check on single attr with
        if (!self::$component && $this->componentName) {
            self::$component = Yii::$app->get($this->componentName);
        } elseif (!$this->componentName) {
            throw new InvalidConfigException("'componentName' mast set");
        }
    }

    public function getOriginUrl($attr)
    {
        return self::$component->getUrl($this->owner, $attr, $this->forceModelClass);
    }

    public function setOriginFile($attr, $path)
    {
        $old = $this->owner->getOldAttribute($attr);
        if ($old) {
            $this->remove($attr, $old);
        }

        $file = self::$component->getOriginFilename($path);
        $this->owner->{$attr} = $file;
        $this->files[$attr] = $path;

        $this->filePath2fileProps($attr, $path, $path, true);
    }

    public function getThumbUrl($attr, $preset)
    {
        return self::$component->getThumbUrl($this->owner, $attr, $preset, $this->forceModelClass);
    }

    public function getThumbPath($attr, $preset)
    {
        return self::$component->getThumbPath($this->owner, $attr, $preset, $this->forceModelClass);
    }

    public function beforeValidate(\yii\base\ModelEvent $event)
    {
        $this->uploaded = $this->deleted = [];

        foreach ((array) $this->attr as $attr) {
            if (!$this->owner->hasProperty($attr)) {
                $class = get_class($this->owner);
                throw new InvalidConfigException("Attribute '{$attr}' does not exist in the class '{$class}'");
            }

            $old = $this->owner->getOldAttribute($attr);
            $new = $this->owner->getAttribute($attr);
            $uploaded = UploadedFile::getInstance($this->owner, $attr);

            if ($uploaded && $uploaded->error) {
                $params = [
                    'file' => $uploaded->name,
                ];

                switch ($uploaded->error) {
                    case UPLOAD_ERR_FORM_SIZE:
                    case UPLOAD_ERR_INI_SIZE:
                        $message = 'The file "{file}" is too big. Its size cannot exceed {formattedLimit}.';

                        $validator = new FileValidator();
                        $limit = $validator->getSizeLimit();
                        $params['limit'] = $limit;
                        $params['formattedLimit'] = Yii::$app->formatter->asShortSize($limit);
                        break;

                    default:
                        $message = 'File upload failed.';
                        break;
                }

                $error = Yii::t('yii', $message, $params);
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
        $fc = $this->forceModelClass;

        foreach ($this->deleted as $attr => $file) {
            self::$component->delete($this->owner, $attr, $file, $fc);
        }
        $this->deleted = [];

        foreach ((array) $this->attr as $attr) {
            if (!in_array($attr, $this->uploaded)) {
                continue;
            }

            $val = $this->owner->{$attr};
            self::$component->saveUploaded($this->owner, $attr, $val, $fc);
        }
        $this->uploaded = [];

        foreach ($this->files as $attr => $path) {
            self::$component->saveOriginFile($this->owner, $attr, $path, $fc);
        }
        $this->files = [];
    }

    public function beforeDelete()
    {
        $this->deleted = [];
        foreach ((array) $this->attr as $attr) {
            $this->deleted[$attr] = $this->owner->getAttribute($attr);
        }
    }

    public function afterDelete()
    {
        foreach ($this->deleted as $attr => $file) {
            self::$component->delete($this->owner, $attr, $file, $this->forceModelClass);
        }
        $this->deleted = [];
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

        if ($this->fileSizeAttr) {
            $ownerAttr = $this->fileSizeAttr;
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
        $this->deleted[$attr] = $file;

        $this->owner->{$attr} = null;
        foreach (['widthAttr', 'heightAttr', 'fileSizeAttr', 'altAttr'] as $tmpAttr) {
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