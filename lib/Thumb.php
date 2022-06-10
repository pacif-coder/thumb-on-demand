<?php
namespace ThumbOnDemand;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;

use yii\imagine\Image;

use Imagine\Image\ManipulatorInterface;

use stdClass;

/**
 * <file> - mast be last
 * check strpos pos
 */
class Thumb extends File
{
    public $webDir = 'media/images';

    public $originExt;

    public $originQuality = 70;

    public $presets = [];

    public $thumbWebDir = 'media/thumbs';

    public $thumbExt;

    public $thumbQuality = 70;

    public $forceCreatePresets;

    public function init()
    {
        parent::init();

        $this->thumbWebDir = trim($this->thumbWebDir, '/');
    }

    public function getThumbUrl($object, $attr, $preset)
    {
        if (is_array($object)) {
            $object = (object) $object;
        }

        if (!$object->{$attr}) {
            return;
        }

        $url = $this->_getThumbUrl($object, $attr, $object->{$attr}, $preset);
        return "{$this->host}{$url}";
    }

    public function getThumbPath($object, $attr, $preset)
    {
        $url = $this->_getThumbUrl($object, $attr, $object->{$attr}, $preset);
        return $this->_addWebRoot($url);
    }

    protected function _getThumbUrl($object, $attr, $image, $preset)
    {
        $path = $this->_createPath($object, $attr, $image, 'thumb');
        return "/{$this->thumbWebDir}/{$preset}/{$path}";
    }

    public function delete($object, $attr, $image)
    {
        parent::delete($object, $attr, $image);

        foreach (array_keys($this->presets) as $preset) {
            $url = $this->_getThumbUrl($object, $attr, $image, $preset);
            $path = $this->_addWebRoot($url);
            $this->_unlink($path, $this->thumbWebDir);
        }
    }

    public function getUploadedFilename(UploadedFile $uploadedFile)
    {
        $info = pathinfo($uploadedFile->name);
        if (!$this->originExt || $this->originExt == $info['extension']) {
            return $uploadedFile->name;
        }

        return $info['filename'] . '.' . $this->originExt;
    }

    public function getOriginFilename($path)
    {
        $info = pathinfo($path);
        if ($this->originExt) {
            return $info['filename'] . '.' . $this->originExt;
        }

        return $info['basename'];
    }

    public function saveUploaded($object, $attr, UploadedFile $uploadedFile)
    {
        $url = $this->_getUrl($object, $attr, $uploadedFile->name);
        $path = $this->_addWebRoot($url);

        $this->_saveUploaded($uploadedFile, $path);
        $this->_forceCreateThumb($object, $attr, $path);
    }

    protected function _forceCreateThumb($object, $attr, $path)
    {
        foreach ($this->presets as $preset => $desc) {
            if (!$this->_isForceCreate($object, $preset, $desc)) {
                continue;
            }

            $thumbPath = $this->getThumbPath($object, $attr, $preset);
            $this->createThumb($preset, $path, $thumbPath);
        }
    }

    protected function _saveUploaded(UploadedFile $uploadedFile, $path)
    {
        $this->_createDirectory(dirname($path));

        if (!$this->originExt) {
            return $uploadedFile->saveAs($path);
        }

        $info = pathinfo($uploadedFile->name);
        if ($info['extension'] == $this->originExt) {
            return $uploadedFile->saveAs($path);
        }

        $this->_saveOriginImage($uploadedFile->tempName, $path);
    }

    public function saveOriginFile($object, $attr, $path)
    {
        $url = $this->_getUrl($object, $attr, $path);
        $destPath = $this->_addWebRoot($url);

        $this->_createDirectory(dirname($destPath));
        $this->_saveOriginImage($path, $destPath);

        $this->_forceCreateThumb($object, $attr, $path);
    }

    protected function _saveOriginImage($source, $dest)
    {
        $imagine = Image::getImagine();

        $originOptions = [];
        if ($this->originQuality) {
            $originOptions['quality'] = $this->originQuality;
        }

        $imagine->open($source)->save($dest, $originOptions);

        if (null !== $this->fileMode) {
            chmod($dest, $this->fileMode);
        }
    }

    public function createThumbByUrl($url = null)
    {
        if (null === $url) {
            $url = Yii::$app->request->getPathInfo();
        }

        $url = ltrim($url, '/');
        $backupUrl = $url;

        // cut thumb folder
        $url = substr($url, strlen($this->thumbWebDir));
        $url = ltrim($url, '/');

        // find preset
        $pos = strpos($url, '/');
        $preset = substr($url, 0, $pos);

        $origin = trim(substr($url, $pos), '/');
        if ($this->thumbExt) {
            if ($this->originExt) {
                $lastDot = strrpos($origin, '.');
                $origin = substr($origin, 0, $lastDot) . '.' . $this->originExt;
            } else {
                $pos = strpos($origin, '/');
                $ext = substr($origin, 0, $pos);
                $origin = trim(substr($origin, $pos), '/');

                $lastDot = strrpos($origin, '.');
                $origin = substr($origin, 0, $lastDot) . '.' . $ext;
            }
        }

        $originPath = $this->_addWebRoot("/{$this->webDir}/{$origin}");
        if (!file_exists($originPath)) {
            throw new NotFoundHttpException("Not find origin image for url '{$backupUrl}'");
        }

        $thumbPath = $this->_addWebRoot("/{$backupUrl}");
        $this->createThumb($preset, $originPath, $thumbPath);

        return $thumbPath;
    }

    public function createThumb($preset, $originPath, $thumbPath)
    {
        if (!isset($this->presets[$preset])) {
            throw new InvalidConfigException("Preset with name '{$preset}' not found");
        }

        $desc = $this->presets[$preset];
        $desc = array_merge(['width' => null, 'height' => null], $desc);

        $thumbOptions = [];
        if (isset($desc['quality']) || $this->thumbQuality) {
            $thumbOptions['quality'] = isset($desc['quality']) ? $desc['quality'] : $this->thumbQuality;
        }

        if (!file_exists($originPath)) {
            throw new NotFoundHttpException("Not find origin image '{$originPath}'");
        }

        $this->_createDirectory(dirname($thumbPath));

        $thumbnail = Image::resize($originPath, $desc['width'], $desc['height'], ManipulatorInterface::THUMBNAIL_OUTBOUND);
        $thumbnail->save($thumbPath, $thumbOptions);

        if (null !== $this->fileMode) {
            chmod($thumbPath, $this->fileMode);
        }
    }

    protected function _isForceCreate($object, $preset, $desc)
    {
        if ($this->forceCreatePresets && in_array($preset, (array) $this->forceCreatePresets)) {
            return true;
        }

        if (!isset($desc['forceCreate']) || !$desc['forceCreate']) {
            return false;
        }

        if (is_string($desc['forceCreate']) || is_array($desc['forceCreate'])) {
            foreach ((array) $desc['forceCreate'] as $class) {
                if (is_a($object, $class)) {
                    return true;
                }
            }

            return false;
        }

        return $desc['forceCreate'] ? true : false;
    }

    protected function _createPath($object, $attr, $file, $extConvert = null)
    {
        $path = parent::_createPath($object, $attr, $file, $extConvert);

        if ('thumb' == $extConvert && $this->thumbExt && !$this->originExt) {
            $path = pathinfo($file, PATHINFO_EXTENSION) . '/' . $path;
        }

        return $path;
    }

    protected function _extConvert($extConvert)
    {
        if ('origin' == $extConvert && $this->originExt) {
            return $this->originExt;
        } elseif ('thumb' == $extConvert && $this->thumbExt) {
            return $this->thumbExt;
        }
    }
}
