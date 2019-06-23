<?php
/**
 * Project: eImage
 * Date: 12/21/15
 * Time: 8:21 PM
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Falmar\eImage;

/**
 * Class eImage
 * Image Upload and Edition
 * @package eImage
 * @author  David Lavieri (falmar) <daviddlavier@gmail.com>
 */
class eImage
{
    /** @var string */
    public $newName;

    /** @var string */
    public $uploadTo;

    /** @var string */
    public $returnType = 'full_path';

    /** @var bool */
    public $safeRename = true;

    /** @var string */
    public $duplicates = 'o';

    /** @var array */
    private $enabledMIMEs = [
        '.jpe' => 'image/jpeg',
        '.jpg' => 'image/jpg',
        '.jpeg' => 'image/jpeg',
        '.gif' => 'image/gif',
        '.png' => 'image/png',
        '.bmp' => 'image/bmp',
        '.ico' => 'image/x-icon',
    ];

    /** @var array */
    private $disabledMIMEs = [];

    /** @var bool */
    public $createDir = false;

    /** @var string */
    public $source;

    /** @var int */
    public $imageQuality = 90;

    /** @var string */
    public $newExtension;

    /** @var string */
    public $prefix;

    /** @var string */
    public $newPath;

    /** @var bool */
    public $aspectRatio = true;

    /** @var bool */
    public $oversize = false;

    /** @var bool */
    public $scaleUp = false;

    /** @var string */
    public $padColor = 'transparent';

    /** @var bool */
    public $fitPad = true;

    /** @var string */
    public $position = 'cc';

    /**
     * eImage constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->set($options);
    }

    public function set($options = [])
    {
        if (is_array($options)) {
            foreach ($options as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
            }
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $string
     *
     * @return mixed
     */
    public function cleanUp($string)
    {
        $string = preg_replace('/\s/i', '', $string);
        if (strrpos($string, '.')) {
            $string = substr($string, 0, strrpos($string, '.')) . strtolower(strrchr($string, '.'));
        }

        return preg_replace('/[^A-Za-z0-9_\-\.]/i', '', $string);
    }

    /**
     * @param array $arUpload
     *
     * @return array|bool|string
     * @throws eImageException
     */
    public function upload($arUpload)
    {
        if (!is_array($arUpload) || !isset($arUpload['name'])) {
            throw new eImageException(eImageException::UPLOAD_NO_ARRAY);
        }

        if (isset($arUpload['error'])) {
            if ($arUpload['error'] === 1) {
                throw new eImageException(eImageException::UPLOAD_INI_MAX);
            } elseif ($arUpload['error'] === 2) {
                throw new eImageException(eImageException::UPLOAD_FORM_MAX);
            } elseif ($arUpload['error'] === 3) {
                throw new eImageException(eImageException::UPLOAD_PARTIAL);
            } elseif ($arUpload['error'] === 4) {
                throw new eImageException(eImageException::UPLOAD_NO_FILE);
            } elseif ($arUpload['error'] === 6) {
                throw new eImageException(eImageException::UPLOAD_NO_TMP_DIR);
            } elseif ($arUpload['error'] === 7) {
                throw new eImageException(eImageException::UPLOAD_WRITE_AC);
            } elseif ($arUpload['error'] === 8) {
                throw new eImageException(eImageException::UPLOAD_EXT);
            }
        }

        if (!$arUpload['size']) {
            throw new eImageException(eImageException::UPLOAD_SIZE);
        }

        if ($this->uploadTo) {
            if (!is_dir($this->uploadTo)) {
                if ($this->createDir) {
                    mkdir($this->uploadTo, 0777);
                } else {
                    throw new eImageException(eImageException::UPLOAD_NO_DIR);
                }
            }
            if (strrpos($this->uploadTo, DIRECTORY_SEPARATOR) !== strlen($this->uploadTo) - 1) {
                $this->uploadTo .= DIRECTORY_SEPARATOR;
            }
        }

        $imageName = ($this->newName) ? $this->newName : $arUpload['name'];
        $imageName = ($this->safeRename) ? $this->cleanUp($imageName) : $imageName;
        $imageType = $arUpload['type'];
        $imageTempName = $arUpload['tmp_name'];
        $imageSize = $arUpload['size'];
        $ext = substr($arUpload['name'], strrpos($arUpload['name'], '.'));
        $enabled = false;

        if (is_integer(strpos($ext, 'jpg'))) {
            $ext = '.jpeg';
        }

        if ($newExt = strrchr($imageName, '.')) {
            if ($newExt != $ext) {
                $imageName = str_replace($newExt, '', $imageName) . $ext;
            }
        } else {
            $imageName = $imageName . $ext;
        }

        if ($this->disabledMIMEs && (!array_key_exists($ext, $this->disabledMIMEs) || !in_array($imageType,
                    $this->disabledMIMEs))
        ) {
            $enabled = true;
        }

        if ((!array_key_exists($ext, $this->enabledMIMEs) || !in_array($imageType, $this->enabledMIMEs)) && !$enabled) {
            throw new eImageException(eImageException::UPLOAD_EXT);
        }

        $prefix = $this->prefix;
        $this->handleDuplicates($this->uploadTo, $prefix, $imageName, $ext);
        $target = $this->uploadTo . $prefix . $imageName;

        if (file_exists($target) && !is_writable($target)) {
            @chmod($target, 0777);
        }

        if (move_uploaded_file($imageTempName, $target)) {
            /** @var string $source easy access for resize and crop functions */
            $this->source = $target;

            $returnType = strtolower($this->returnType);
            if ($returnType === 'array') {
                return [
                    'name' => basename($imageName),
                    'path' => $this->uploadTo,
                    'size' => $imageSize,
                    'tmp_name' => $imageTempName,
                    'full_path' => $this->source
                ];
            } elseif ($returnType === 'full_path') {
                return (file_exists($this->source)) ? $this->source : false;
            } else {
                return (file_exists($this->source)) ? true : false;
            }

        } else {
            throw new eImageException(eImageException::UPLOAD_FAILED);
        }
    }

    /**
     * Create a new image from an existing file according to width and height passed
     *
     * @param $width
     * @param $height
     *
     * @return array|bool|string
     * @throws eImageException
     */
    public function resize($width, $height)
    {
        if (!is_integer($width)) {
            throw new eImageException(eImageException::NO_WIDTH);
        } elseif (!is_integer($height)) {
            throw new eImageException(eImageException::NO_HEIGHT);
        } elseif (!is_string($this->source)) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $source = $this->source;

        $this->imageCreateSource($source, $file, $ext);

        $DS = DIRECTORY_SEPARATOR;
        $path = (is_integer(strpos($source, $DS))) ? substr($source, 0, strrpos($source, $DS) + 1) : null;
        $path = trim(($this->newPath) ? $this->newPath : $path);

        if (!is_dir($path) && $path) {
            if ($this->createDir) {
                mkdir($path, 0777);
            } else {
                throw new eImageException(eImageException::UPLOAD_NO_DIR);
            }
        }

        $name = ($this->newName) ? $this->newName : str_replace($path, '', $source);
        $name = (strrpos($name, $DS) !== strlen($name)) ? $name . $DS : $name;
        $name = (strrpos($name, '.')) ? substr($name, 0, strrpos($name, '.')) . $ext : $name . $ext;
        $name = ($this->safeRename) ? $this->cleanUp($name) : $name;
        $prefix = $this->prefix;

        $this->handleDuplicates($path, $prefix, $name, $ext);

        $source = $path . $prefix . $name;

        $sWidth = imagesx($file);
        $sHeight = imagesy($file);

        $cWidth = $width;
        $cHeight = $height;

        if ($this->aspectRatio) {
            if ($sWidth > $sHeight) {
                if ($this->oversize) {
                    $nHeight = round(($sHeight / $sWidth) * $width);
                    if ($nHeight < $height) {
                        $width = round(($height * $sWidth) / $sHeight);
                    } else {
                        $height = $nHeight;
                    }
                } else {
                    $height = round(($sHeight / $sWidth) * $width);
                }
            } elseif ($sHeight > $sWidth) {
                if ($this->oversize) {
                    $nWidth = round(($height * $sWidth) / $sHeight);
                    if ($nWidth < $width) {
                        $height = round(($sHeight / $sWidth) * $width);
                    } else {
                        $width = $nWidth;
                    }
                } else {
                    $width = round(($height * $sWidth) / $sHeight);
                }
            }
        }

        if (!$this->fitPad) {
            $cWidth = $width;
            $cHeight = $height;
        }

        if (!$this->scaleUp) {
            if ($sWidth <= $width && $sHeight <= $height) {
                $width = $sWidth;
                $height = $sHeight;
                $cWidth = $sWidth;
                $cHeight = $sHeight;
            }
        }

        $position = [
            'dx' => 0,
            'dy' => 0,
            'sx' => 0,
            'sy' => 0
        ];

        if ($this->fitPad) {
            $top = 0;
            $left = 0;
            $right = $cWidth - $width;
            $bottom = $cHeight - $height;
            $x = ($cWidth - $width) / 2;
            $y = ($cHeight - $height) / 2;

            if (strpos($this->position, ',')) {
                $dimensions = explode($this->position, ',');
                $x = (int)@$dimensions[0];
                $y = (int)@$dimensions[1];
                $position['dx'] = $x;
                $position['dy'] = $y;
            } elseif ($this->position === 'tl') {
                $position['dx'] = $left;
                $position['dy'] = $top;
            } elseif ($this->position === 'tr') {
                $position['dx'] = $right;
                $position['dy'] = $top;
            } elseif ($this->position === 'tc') {
                $position['dx'] = $x;
                $position['dy'] = $top;
            } elseif ($this->position === 'bl') {
                $position['dx'] = $left;
                $position['dy'] = $bottom;
            } elseif ($this->position === 'br') {
                $position['dx'] = $right;
                $position['dy'] = $bottom;
            } elseif ($this->position === 'bc') {
                $position['dx'] = $x;
                $position['dy'] = $bottom;
            } elseif ($this->position === 'cl') {
                $position['dx'] = $left;
                $position['dy'] = $y;
            } elseif ($this->position === 'cr') {
                $position['dx'] = $right;
                $position['dy'] = $y;
            } else {
                $position['dx'] = $x;
                $position['dy'] = $y;
            }
        }

        $canvas = imagecreatetruecolor($cWidth, $cHeight);

        if ($this->padColor == 'transparent') {
            imagealphablending($canvas, false);
            $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $color);
            imagesavealpha($canvas, true);
        } else {
            $color = $this->hex2rbg($this->padColor);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $color['r'], $color['b'], $color['g']));
        }

        imagecopyresampled(
            $canvas,
            $file,
            $position['dx'],
            $position['dy'],
            $position['sx'],
            $position['sy'],
            $width,
            $height,
            $sWidth,
            $sHeight
        );

        $quality = $this->imageQuality;

        if ($this->padColor == 'transparent') {
            if ($ext != '.gif') {
                $ext = '.png';
            }
        } else {
            if ($ext == '.gif') {
                $ext = '.jpg';
            }
        }

        if ($ext == '.png') {
            $quality = ($quality > 90) ? 9 : (int)(($quality) / 10);
        }

        $this->imageCreate($ext, $canvas, $source, $quality);

        imagedestroy($file);
        imagedestroy($canvas);

        $returnType = strtolower($this->returnType);
        if ($returnType === 'array') {
            return [
                'name' => $name,
                'prefix' => $prefix,
                'path' => $path,
                'width' => $width,
                'height' => $height,
                'pad_color' => $this->padColor,
                'full_path' => $source
            ];
        } elseif ($returnType === 'full_path') {
            return (file_exists($source)) ? $source : false;
        } else {
            return (file_exists($source)) ? true : false;
        }
    }

    /**
     * Create a new image from an existing file according to x, y, width, height passed
     *
     * @param $width
     * @param $height
     * @param $x
     * @param $y
     *
     * @return array|bool|string
     * @throws eImageException
     */
    public function crop($width, $height, $x, $y)
    {
        if (!is_integer($width)) {
            throw new eImageException(eImageException::NO_WIDTH);
        } elseif (!is_integer($height)) {
            throw new eImageException(eImageException::NO_HEIGHT);
        } elseif (!is_integer($x)) {
            throw new eImageException(eImageException::NO_X);
        } elseif (!is_integer($y)) {
            throw new eImageException(eImageException::NO_Y);
        }

        if (!$this->source || !file_exists($this->source)) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $source = $this->source;

        $this->imageCreateSource($source, $file, $ext);

        $DS = DIRECTORY_SEPARATOR;
        $path = (is_integer(strpos($source, $DS))) ? substr($source, 0, strrpos($source, $DS) + 1) : null;
        $path = trim(($this->newPath) ? $this->newPath : $path);

        if (!is_dir($path) && $path) {
            if ($this->createDir) {
                mkdir($path, 0777);
            } else {
                throw new eImageException(eImageException::UPLOAD_NO_DIR);
            }
        }

        $name = ($this->newName) ? $this->newName : str_replace($path, '', $source);
        $name = (strrpos($name, $DS) !== strlen($name)) ? $name . $DS : $name;
        $name = (strrpos($name, '.')) ? substr($name, 0, strrpos($name, '.')) . $ext : $name . $ext;
        $name = ($this->safeRename) ? $this->cleanUp($name) : $name;
        $prefix = $this->prefix;

        $this->handleDuplicates($path, $prefix, $name, $ext);

        $source = $path . $prefix . $name;
        $canvas = imagecreatetruecolor($width, $height);
        $sWidth = imagesx($file);
        $sHeight = imagesy($file);
        imagecopyresampled($canvas, $file, $x, $y, 0, 0, $sWidth, $sHeight, $sWidth, $sHeight);

        $quality = $this->imageQuality;

        if ($ext == '.png') {
            $quality = ($quality > 90) ? 9 : ((int)$quality) / 10;
        }

        $this->imageCreate($ext, $canvas, $source, $quality);

        imagedestroy($file);
        imagedestroy($canvas);

        $returnType = strtolower($this->returnType);
        if ($returnType === 'array') {
            return [
                'name' => $name,
                'prefix' => $prefix,
                'path' => $path,
                'full_path' => $source,
                'width' => $width,
                'height' => $height
            ];
        } elseif ($returnType === 'full_path') {
            return (file_exists($source)) ? $source : false;
        } else {
            return (file_exists($source)) ? true : false;
        }
    }

    /** Helper functions */

    /**
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param string $prefix
     * @param string $name
     * @param string $ext
     *
     * @return bool
     * @throws eImageException
     */
    private function handleDuplicates($path, $prefix, &$name, &$ext)
    {
        if (file_exists($path . $prefix . $name)) {

            if ($this->duplicates === 'o') {
            } elseif ($this->duplicates === 'e') {
                throw new eImageException(eImageException::IMAGE_EXIST);
            } else {
                if (strrpos(($name), '.')) {
                    $im = substr(($name), 0, strrpos(($name), '.'));
                } else {
                    $im = ($name);
                }

                $i = 0;
                while (file_exists($path . $prefix . $im . '_' . $i . $ext)) {
                    $i++;
                }
                $name = $prefix . $im . '_' . $i . $ext;
            }
        }

        return true;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $ext
     * @param resource $canvas
     * @param string $name
     * @param null|int $quality
     */
    private function imageCreate($ext, $canvas, $name, $quality = null)
    {
        $quality = (!is_null($quality)) ? $quality : $this->imageQuality;

        if ($ext === '.gif') {
            imagegif($canvas, $name);
        } elseif ($ext === '.png') {
            imagepng($canvas, $name, $quality);
        } elseif ($ext === '.wbmp') {
            imagewbmp($canvas, $name);
        } else {
            imagejpeg($canvas, $name, $quality);
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $source
     * @param resource $file
     * @param string $ext

     * @throws eImageException
     */
    private function imageCreateSource($source, &$file, &$ext)
    {
        $mime = getimagesize($source)['mime'];

        if ($mime === 'image/gif') {
            if (imagetypes() && IMG_GIF) {
                $file = imagecreatefromgif($source);
                $ext = ($this->newExtension) ? $this->newExtension : '.gif';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - GIF not supported PHP');
            }
        } elseif ($mime === 'image/jpeg') {
            if (imagetypes() && IMG_JPEG) {
                $file = imagecreatefromjpeg($source);
                $ext = ($this->newExtension) ? $this->newExtension : '.jpeg';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - JPEG not supported PHP');
            }
        } elseif ($mime === 'image/png') {
            if (imagetypes() && IMG_PNG) {
                $file = imagecreatefrompng($source);
                $ext = ($this->newExtension) ? $this->newExtension : '.png';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - PNG not supported PHP');
            }
        } elseif ($mime === 'image/wbmp') {
            if (imagetypes() && IMG_WBMP) {
                $file = imagecreatefromwbmp($source);
                $ext = ($this->newExtension) ? $this->newExtension : '.wbmp';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - WBMP not supported PHP');
            }
        } else {
            throw new eImageException(eImageException::BAD_EXT);
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $hex
     *
     * @return array
     */
    private function hex2rbg($hex)
    {
        $color = str_replace('#', '', $hex);

        return [
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        ];
    }

    /**
     * @param string $img
     *
     * @return array
     */
    public function getImageSize($img)
    {
        if (file_exists($img)) {
            list($width, $height) = getimagesize($img);

            return [
                'width' => $width,
                'height' => $height
            ];
        } else {
            return [
                'width' => 0,
                'height' => 0
            ];
        }
    }
}
