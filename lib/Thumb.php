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

/**
 * <file> - mast be last
 * check strpos pos
 */
class Thumb extends BaseObject
{
    public $presets = [];

    public $originFolder = 'images/origin';

    public $originPath = '<class>/<id2dirs>/<id>-<file>';

    public $originExt;

    public $originQuality = 70;

    public $thumbFolder = 'images/thumb';

    public $thumbExt;

    public $thumbQuality = 70;

    public $id2DirsLen = 8;

    public $id2DirLen = 2;

    public $webroot = '@webroot';

    public function init()
    {
        parent::init();

        $this->thumbFolder = trim($this->thumbFolder, '/');

        $this->originFolder = trim($this->originFolder, '/');
    }

    public function getThumbUrl($object, $attr, $preset)
    {
        if (!$object->{$attr}) {
            return;
        }

        return $this->_getThumbUrl($object, $attr, $object->{$attr}, $preset);
    }

    public function _getThumbUrl($object, $attr, $image, $preset)
    {
        $path = $this->_createPath($object, $attr, $image, 'thumb');
        return "/{$this->thumbFolder}/{$preset}/{$path}";
    }

    public function getThumbPath($object, $attr, $preset)
    {
        return $this->_addWebRoot($this->getThumbUrl($object, $attr, $preset));
    }

    public function getOriginUrl($object, $attr)
    {
        if (!$object->{$attr}) {
            return;
        }

        return $this->_getOriginUrl($object, $attr, $object->{$attr});
    }

    public function getOriginPath($object, $attr)
    {
        return $this->_addWebRoot($this->_getOriginUrl($object, $attr, $object->{$attr}));
    }

    protected function _getOriginUrl($object, $attr, $image)
    {
        $path = $this->_createPath($object, $attr, $image, 'origin');
        return "/{$this->originFolder}/{$path}";
    }

    public function deleteImage($object, $attr, $image)
    {
        $url = $this->_getOriginUrl($object, $attr, $image);
        $path = $this->_addWebRoot($url);
        $this->_unlink($path, $this->originFolder);

        foreach (array_keys($this->presets) as $preset) {
            $url = $this->_getThumbUrl($object, $attr, $image, $preset);
            $path = $this->_addWebRoot($url);

            $this->_unlink($path, $this->thumbFolder);
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
        $url = $this->_getOriginUrl($object, $attr, $uploadedFile->name);
        $path = $this->_addWebRoot($url);

        $this->_saveUploaded($uploadedFile, $path);
        $this->_forceCreateThumb($object, $attr, $path);
    }

    protected function _forceCreateThumb($object, $attr, $path)
    {
        foreach ($this->presets as $preset => $desc) {
            if (!$this->_isForceCreate($object, $desc)) {
                continue;
            }

            $thumbPath = $this->getThumbPath($object, $attr, $preset);
            $this->createThumb($preset, $path, $thumbPath);
        }
    }

    protected function _saveUploaded(UploadedFile $uploadedFile, $path)
    {
        FileHelper::createDirectory(dirname($path));

        if (!$this->originExt) {
            return $uploadedFile->saveAs($path);
        }

        $info = pathinfo($uploadedFile->name);
        if ($info['extension'] == $this->originExt) {
            return $uploadedFile->saveAs($path);
        }

        $this->_saveFile($uploadedFile->tempName, $path);
    }

    public function saveOriginFile($object, $attr, $path)
    {
        $url = $this->_getOriginUrl($object, $attr, $path);
        $destPath = $this->_addWebRoot($url);

        FileHelper::createDirectory(dirname($destPath));
        $this->_saveFile($path, $destPath);

        $this->_forceCreateThumb($object, $attr, $path);
    }

    protected function _saveFile($source, $dest)
    {
        $imagine = Image::getImagine();

        $originOptions = [];
        if ($this->originQuality) {
            $originOptions['quality'] = $this->originQuality;
        }

        $imagine->open($source)->save($dest, $originOptions);
    }

    protected function object2params($object, $needAttrs = [])
    {
        $params = [];
        $class = get_class($object);

        if ($class) {
            $classParts = explode('\\', $class);
            $params['class'] = lcfirst(end($classParts));
            $index = array_search('models', $classParts);
            if ($index > 0) {
                $params['module'] = $classParts[$index - 1];
            }
        }

        if (is_a($object, ActiveRecord::class)) {
            $keys = $object::primaryKey();

            if (count($keys) > 1) {
                throw new InvalidConfigException("Preset with name '{$name}' not found");
            } elseif ($keys) {
                $idKey = current($keys);
                $id = $object->{$idKey};
                $params['id'] = $id;

                $count2dir = 10 ** $this->id2DirLen;
                $num = intdiv($id, $count2dir);
                $id2dirs = sprintf("%0{$this->id2DirsLen}d", $num);

                $reg = '/\d{' . $this->id2DirLen . '}/';
                $id2dirs = trim(preg_replace($reg, '$0/', $id2dirs), '/');
                $params['id2dirs'] = $id2dirs;
            }
        }

        if ($needAttrs && is_a($object, BaseObject::class)) {
            foreach ($needAttrs as $attr) {
                $params["attributes:{$attr}"] = $object->{$attr};
            }
        }

        return $params;
    }

    public function createThumbByUrl($url = null)
    {
        if (null === $url) {
            $url = Yii::$app->request->getPathInfo();
        }

        $url = ltrim($url, '/');
        $backupUrl = $url;

        // cut thumb folder
        $url = substr($url, strlen($this->thumbFolder));
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

        $originPath = $this->_addWebRoot("/{$this->originFolder}/{$origin}");
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

        FileHelper::createDirectory(dirname($thumbPath));

        $thumbnail = Image::resize($originPath, $desc['width'], $desc['height'], ManipulatorInterface::THUMBNAIL_OUTBOUND);
        $thumbnail->save($thumbPath, $thumbOptions);
    }

    protected function _unlink($path, $topDir)
    {
        if (!file_exists($path)) {
            return;
        }

        FileHelper::unlink($path);

        $topDirLen = strlen($this->_addWebRoot('/' . $topDir));
        $dir = dirname($path);
        while (strlen($dir) > $topDirLen) {
            $count = count(scandir($dir)) - 2;
            if ($count > 0) {
                break;
            }

            FileHelper::removeDirectory($dir);
            $dir = dirname($dir);
        }
    }

    protected function _isForceCreate($object, $desc)
    {
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

    protected function _createPath($object, $attr, $file, $extFrom)
    {
        $path = $this->originPath;

        $matchs = [];
        preg_match_all('/<([^>]+)>/', $path, $matchs,
                PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);

        $needAttrs = $params = $datas = [];
        foreach ($matchs[1] as $i => $match) {
            $param = $match[0];

            $pos = strpos($param, ':');
            if (false === $pos) {
                $params[$i] = $param;
                continue;
            }

            $data = substr($param, $pos + 1);
            $param = substr($param, 0, $pos);

            $params[$i] = $param;
            $datas[$i] = $data;

            if ('attributes' == $param) {
                $needAttrs[] = $data;
            }
        }

        $paramValues = $this->object2params($object, $needAttrs);

        $ext = null;
        if ('origin' == $extFrom && $this->originExt) {
            $ext = $this->originExt;
        } elseif ('thumb' == $extFrom && $this->thumbExt) {
            $ext = $this->thumbExt;
        }

        $diff = 0;
        foreach ($matchs[0] as $i => $match) {
            $tag = $match[0];
            $begin = $match[1];
            $len = strlen($tag);

            $param = $params[$i];
            switch ($param) {
                case 'attr':
                    $value = $attr;
                    break;

                case 'attributes':
                    $value = $paramValues[$param . ':' . $datas[$i]];
                    break;

                case 'file':
                    $value = $ext ?
                        pathinfo($file, PATHINFO_FILENAME) . '.' . $ext
                        :
                        basename($file);
                    break;

                case 'filename':
                    $value = pathinfo($file, PATHINFO_FILENAME);
                    break;

                case 'extension':
                    $value = $ext ? $ext : pathinfo($file, PATHINFO_EXTENSION);
                    break;

                default:
                    $value = $paramValues[$param];
            }

            $path = substr($path, 0, $begin + $diff)
                    . (string) $value
                    . substr($path, $begin + $len + $diff);

            $diff += (strlen($value) - $len);
        }

        if ('thumb' == $extFrom && $this->thumbExt && !$this->originExt) {
            $path = pathinfo($file, PATHINFO_EXTENSION) . '/' . $path;
        }

        return $path;
    }

    protected function _addWebRoot($path)
    {
        return Yii::getAlias("{$this->webroot}{$path}");
    }
}
