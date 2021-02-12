<?php
namespace ThumbOnDemand;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;

use yii\imagine\Image;

use Imagine\Image\ManipulatorInterface;

/**
 * <file> - mast be last
 */
class Thumb extends BaseObject
{
    public $originFolder = 'images/origin';

    public $originPath = '<class>/<id2dirs>/<id>-<file>';

    public $originExt; //  = 'webp'

    public $id2DirsLen = 8;

    public $id2DirLen = 2;

    public $thumbFolder = 'images/thumb';

    public $thumbQuality = 70;

    public $presets = [];

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
        $path = $this->_createPath($object, $attr, $image);
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
        $path = $this->_createPath($object, $attr, $image);
        return "/{$this->originFolder}/{$path}";
    }

    protected function _addWebRoot($path)
    {
        return Yii::getAlias("@webroot{$path}");
    }

    public function deleteImage($object, $attr, $image)
    {
        $url = $this->_getOriginUrl($object, $attr, $image);
        $path = $this->_addWebRoot($url);
        if (file_exists($path)) {
            FileHelper::unlink($path);
        }

        foreach (array_keys($this->presets) as $preset) {
            $url = $this->_getThumbUrl($object, $attr, $image, $preset);
            $path = $this->_addWebRoot($url);

            if (!file_exists($path)) {
                continue;
            }

            FileHelper::unlink($path);
        }
    }

    public function getUploadedFilename(UploadedFile $uploadedFile)
    {
        $info = pathinfo($uploadedFile->name);
        if (!$this->originExt || $this->originExt == $info['extension']) {
            return $uploadedFile->name;
        }

        return $info['filename'] . '.' .$this->originExt;
    }

    public function getOriginFilename($path)
    {
        $info = pathinfo($path);
        if (!$this->originExt || $this->originExt == $info['extension']) {
            return $info['basename'];
        }

        return $info['filename'] . '.' .$this->originExt;
    }

    public function saveUploaded($object, $attr, UploadedFile $uploadedFile)
    {
        $url = $this->_getOriginUrl($object, $attr, $uploadedFile->name);
        $path = $this->_addWebRoot($url);

        FileHelper::createDirectory(dirname($path));
        $uploadedFile->saveAs($path);
    }

    public function saveOriginFile($object, $attr, $path)
    {
        $url = $this->_getOriginUrl($object, $attr, $path);
        $destPath = $this->_addWebRoot($url);

        FileHelper::createDirectory(dirname($destPath));
        copy($path, $destPath);
    }

    protected function _createPath($object, $attr, $file)
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
                    $value = basename($file);
                    break;

                case 'filename':
                    $value = pathinfo($file, PATHINFO_FILENAME);
                    break;

                case 'extension':
                    $value = $this->originExt? $this->originExt : pathinfo($file, PATHINFO_EXTENSION);
                    break;

                default:
                    $value = $paramValues[$param];
            }

            $path = substr($path, 0, $begin + $diff)
                    . (string) $value
                    . substr($path, $begin + $len + $diff);

            $diff += (strlen($value) - $len);
        }

        return $path;
    }

    public function object2params($object, $needAttrs = [])
    {
        $params = [];
        $class = get_class($object);

        if ($class) {
            $classParts = explode('\\', $class);
            $params['class'] = lcfirst(end($classParts));
            $count = count($classParts);
            if ($count > 2) {
                $params['module'] = $classParts[$count - 3];
            }
        }

        if (is_a($object, ActiveRecord::class)) {
            $keys = $object::primaryKey();

            if (count($keys) > 1) {
                throw new InvalidConfigException("Preset with name '{$name}' not found");
            } elseif ($keys) {
                $id = current($keys);
                $id = $object->{$id};
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

    public function createThumb($url = null)
    {
        if (null === $url) {
            $url = Yii::$app->request->getPathInfo();
        }

        $url = ltrim($url, '/');
        $backupUrl = $url;

        $url = substr($url, strlen($this->thumbFolder));
        $url = ltrim($url, '/');

        $pos = strpos($url, '/');
        $name = substr($url, 0, $pos);
        if (!isset($this->presets[$name])) {
            throw new InvalidConfigException("Preset with name '{$name}' not found");
        }

        $desc = $this->presets[$name];
        $desc = array_merge(['width' => null, 'height' => null], $desc);

        $thumbOptions = [];
        if (isset($desc['quality']) || $this->thumbQuality) {
            $thumbOptions['quality'] = isset($desc['quality'])? $desc['quality'] : $this->thumbQuality;
        }

        $origin = trim(substr($url, $pos), '/');
        $originPath = $this->_addWebRoot("/{$this->originFolder}/{$origin}");
        if (!file_exists($originPath)) {
            throw new NotFoundHttpException("Not find origin image for url '{$backupUrl}'");
        }

        $thumbPath = $this->_addWebRoot("/{$backupUrl}");
        FileHelper::createDirectory(dirname($thumbPath));

        $thumbnail = Image::resize($originPath, $desc['width'], $desc['height'], ManipulatorInterface::THUMBNAIL_OUTBOUND);
        $thumbnail->save($thumbPath, $thumbOptions);

        return $thumbPath;
    }
}
