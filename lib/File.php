<?php
namespace ThumbOnDemand;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use yii\imagine\Image;
use Imagine\Image\ManipulatorInterface;

use stdClass;

/**
 * <file> - mast be last
 * check strpos pos
 */
class File extends \yii\base\BaseObject
{
    public $webDir = 'media/files';

    public $path = '<class>/<id2dirs>/<id>-<file>';

    public $id2DirsLen = 8;

    public $id2DirLen = 2;

    public $dirMode = 0777;

    public $fileMode = 0666;

    public $host = '';

    public $webRoot = '@webroot';

    public function init()
    {
        parent::init();

        $this->webDir = trim($this->webDir, '/');
    }

    public function getUrl($object, $attr, $forceModelClass = false)
    {
        if (is_array($object)) {
            $object = (object) $object;
        }

        if (!$object->{$attr}) {
            return;
        }

        $url = $this->_getUrl($object, $attr, $object->{$attr}, $forceModelClass);
        return "{$this->host}{$url}";
    }

    public function getPath($object, $attr, $forceModelClass = false)
    {
        $url = $this->_getUrl($object, $attr, $object->{$attr}, $forceModelClass);
        return $this->_addWebRoot($url);
    }

    public function delete($object, $attr, $file, $forceModelClass = false)
    {
        $url = $this->_getUrl($object, $attr, $file, $forceModelClass);
        $path = $this->_addWebRoot($url);
        $this->_unlink($path, $this->webDir);
    }

    public function getUploadedFilename(UploadedFile $uploadedFile)
    {
        $info = pathinfo($uploadedFile->name);
        return $uploadedFile->name;
    }

    public function getOriginFilename($path)
    {
        $info = pathinfo($path);

        return $info['basename'];
    }

    public function saveUploaded($object, $attr, UploadedFile $uploadedFile, $forceModelClass = false)
    {
        $url = $this->_getUrl($object, $attr, $uploadedFile->name, $forceModelClass);
        $path = $this->_addWebRoot($url);

        $this->_saveUploaded($uploadedFile, $path);
    }

    protected function _saveUploaded(UploadedFile $uploadedFile, $path)
    {
        $this->_createDirectory(dirname($path));
        return $uploadedFile->saveAs($path);
    }

    public function saveOriginFile($object, $attr, $path, $forceModelClass = false)
    {
        $url = $this->_getUrl($object, $attr, $path, $forceModelClass);
        $destPath = $this->_addWebRoot($url);

        $this->_createDirectory(dirname($destPath));
        $this->_saveFile($path, $destPath);
    }

    protected function object2params($object, $needAttrs, $forceModelClass = false)
    {
        $params = [];
        $class = null;
        if (false !== $forceModelClass) {
            $class = $forceModelClass;
        } elseif (is_object($object) && !is_a($object, stdClass::class)) {
            $class = get_class($object);
        }

        if ($class) {
            $classParts = explode('\\', $class);
            $params['class'] = lcfirst(end($classParts));
            $index = array_search('models', $classParts);
            if ($index > 0) {
                $params['module'] = $classParts[$index - 1];
            }
        }

        if (is_a($object, ActiveRecord::class) || is_a($class, ActiveRecord::class, true)) {
            $keys = $class::primaryKey();

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

    protected function _getUrl($object, $attr, $file, $forceModelClass = false)
    {
        $path = $this->_createPath($object, $attr, $file, 'origin', $forceModelClass);
        return "/{$this->webDir}/{$path}";
    }

    protected function _createPath($object, $attr, $file, $extConvert, $forceModelClass = false)
    {
        $path = $this->path;

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

        $diff = 0;
        $paramValues = $this->object2params($object, $needAttrs, $forceModelClass);
        $ext = $this->_extConvert($extConvert);

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

        return $path;
    }

    protected function _extConvert($extConvert)
    {
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

    protected function _createDirectory($dirpath)
    {
        $mode = $this->dirMode? $this->dirMode : 0775;
        FileHelper::createDirectory($dirpath, $mode);
    }

    protected function _addWebRoot($path)
    {
        return Yii::getAlias("{$this->webRoot}{$path}");
    }
}