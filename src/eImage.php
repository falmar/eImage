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
    protected $config = [
        'newPath' => null,
        'safeRename' => true,
        'duplicates' => 'o',
        'sourcePath' => null,
        'prefix' => null,
        'keepAspectRatio' => true,
        'oversize' => false,
        'scaleUp' => false,

        'fitPad' => true,
        'padColor' => 'transparent',
        'position' => 'cc',

        'png' => [
            'imageQuality' => 90,
        ]
    ];

    /**
     * eImage constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->setConfig($options);
    }

    public function setConfig($options = [])
    {
        $this->config = array_merge($this->config, $options);
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
        } elseif (!is_string($this->config['sourcePath'])) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $source = $this->config['sourcePath'];

        $this->imageCreateSource($source, $file, $ext);

        $DS = DIRECTORY_SEPARATOR;
        $path = trim($source);

        if ($this->config['newPath']) {
            $path = trim($this->config['newPath']);
        }

        $dirPath = dirname($path) . $DS;
        $fileName = basename($path);
        $fileName = ($this->config['safeRename']) ? $this->cleanUp($fileName) : $fileName;

        if (!file_exists($dirPath)) {
            mkdir($path, 0777);
        }

        if (strpos($fileName, $ext) === false) {
            $fileName = substr($fileName, 0, strrpos($fileName, '.')) . $ext;
        }

        $this->handleDuplicates($dirPath, $this->config['prefix'], $fileName, $ext);

        $filePath = $dirPath . $this->config['prefix'] . $fileName;

        $sWidth = imagesx($file);
        $sHeight = imagesy($file);

        $cWidth = $width;
        $cHeight = $height;

        if ($this->config['keepAspectRatio']) {
            $this->getAspectRatio($width, $height, $sWidth, $sHeight, $this->config['oversize']);
        }

        if (!$this->config['fitPad']) {
            $cWidth = $width;
            $cHeight = $height;
        }

        if (!$this->config['scaleUp']) {
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

        if ($this->config['fitPad']) {
            $top = 0;
            $left = 0;
            $right = $cWidth - $width;
            $bottom = $cHeight - $height;
            $x = ($cWidth - $width) / 2;
            $y = ($cHeight - $height) / 2;

            $pos = $this->config['position'];

            if (strpos($pos, ',')) {
                $dimensions = explode($pos, ',');
                $x = (int)@$dimensions[0];
                $y = (int)@$dimensions[1];
                $position['dx'] = $x;
                $position['dy'] = $y;
            } elseif ($pos === 'tl') {
                $position['dx'] = $left;
                $position['dy'] = $top;
            } elseif ($pos === 'tr') {
                $position['dx'] = $right;
                $position['dy'] = $top;
            } elseif ($pos === 'tc') {
                $position['dx'] = $x;
                $position['dy'] = $top;
            } elseif ($pos === 'bl') {
                $position['dx'] = $left;
                $position['dy'] = $bottom;
            } elseif ($pos === 'br') {
                $position['dx'] = $right;
                $position['dy'] = $bottom;
            } elseif ($pos === 'bc') {
                $position['dx'] = $x;
                $position['dy'] = $bottom;
            } elseif ($pos === 'cl') {
                $position['dx'] = $left;
                $position['dy'] = $y;
            } elseif ($pos === 'cr') {
                $position['dx'] = $right;
                $position['dy'] = $y;
            } else {
                $position['dx'] = $x;
                $position['dy'] = $y;
            }
        }

        $canvas = imagecreatetruecolor($cWidth, $cHeight);

        if ($ext === '.png') {
            if ($this->config['padColor'] == 'transparent') {
                imagealphablending($canvas, false);
                $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $color);
                imagesavealpha($canvas, true);
            } else {
                $color = $this->hex2rbg($this->config['padColor']);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, $color['r'], $color['b'], $color['g']));
            }
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

        $quality = $this->config['png']['imageQuality'];

        if ($ext == '.png') {
            $quality = ($quality > 90) ? 9 : (int)(($quality) / 10);
        }

        $this->imageCreate($ext, $canvas, $filePath, $quality);

        imagedestroy($file);
        imagedestroy($canvas);

        return (file_exists($filePath)) ? $filePath : false;
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

        if (!$this->config['sourcePath'] || !file_exists($this->config['sourcePath'])) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $source = $this->config['sourcePath'];

        $this->imageCreateSource($source, $file, $ext);

        $DS = DIRECTORY_SEPARATOR;
        $path = trim($source);

        if ($this->config['newPath']) {
            $path = trim($this->config['newPath']);
        }

        $dirPath = dirname($path) . $DS;
        $fileName = basename($path);
        $fileName = ($this->config['safeRename']) ? $this->cleanUp($fileName) : $fileName;

        if (!file_exists($dirPath)) {
            mkdir($path, 0777);
        }

        if (strpos($fileName, $ext) === false) {
            $fileName = substr($fileName, 0, strrpos($fileName, '.')) . $ext;
        }

        $this->handleDuplicates($dirPath, $this->config['prefix'], $fileName, $ext);

        $filePath = $dirPath . $this->config['prefix'] . $fileName;

        $canvas = imagecreatetruecolor($width, $height);

        if ($ext === '.png') {
            if ($this->config['padColor'] == 'transparent') {
                imagealphablending($canvas, false);
                $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $color);
                imagesavealpha($canvas, true);
            } else {
                $color = $this->hex2rbg($this->config['padColor']);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, $color['r'], $color['b'], $color['g']));
            }
        }

        $sWidth = imagesx($file);
        $sHeight = imagesy($file);
        imagecopyresampled($canvas, $file, $x, $y, 0, 0, $sWidth, $sHeight, $sWidth, $sHeight);

        $quality = $this->config['png']['imageQuality'];

        if ($ext == '.png') {
            $quality = ($quality > 90) ? 9 : ((int)$quality) / 10;
        }

        $this->imageCreate($ext, $canvas, $filePath, $quality);

        imagedestroy($file);
        imagedestroy($canvas);

        return (file_exists($filePath)) ? $filePath : false;
    }

    /** Helper functions */

    /**
     * @codeCoverageIgnore
     *
     * @param $string
     *
     * @return mixed
     */
    protected function cleanUp($string)
    {
        $string = preg_replace('/\s/i', '', $string);

        if (strrpos($string, '.')) {
            $string = substr($string, 0, strrpos($string, '.')) . strtolower(strrchr($string, '.'));
        }

        return preg_replace('/[^A-Za-z0-9_\-\.]/i', '', $string);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param string $prefix
     * @param string $name
     * @param string $ext
     *
     * @return void
     * @throws eImageException
     */
    protected function handleDuplicates($path, $prefix, &$name, &$ext)
    {
        if (!file_exists($path . $prefix . $name)) {
            return;
        }

        if ($this->config['duplicates'] === 'e') {
            throw new eImageException(eImageException::IMAGE_EXIST);
        } elseif ($this->config['duplicates'] !== 'o') {
            if ($pos = strrpos($name, '.')) {
                $im = substr($name, 0, $pos);
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

    /**
     * @codeCoverageIgnore
     *
     * @param string $ext
     * @param resource $canvas
     * @param string $name
     * @param null|int $quality
     */
    protected function imageCreate($ext, $canvas, $name, $quality = null)
    {
        $quality = (!is_null($quality)) ? $quality : $this->config['png']['imageQuality'];

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
    protected function imageCreateSource($source, &$file, &$ext)
    {
        $mime = getimagesize($source)['mime'];

        if ($mime === 'image/gif') {
            if (imagetypes() && IMG_GIF) {
                $file = imagecreatefromgif($source);
                $ext = '.gif';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - GIF not supported PHP');
            }
        } elseif ($mime === 'image/jpeg') {
            if (imagetypes() && IMG_JPEG) {
                $file = imagecreatefromjpeg($source);
                $ext = '.jpeg';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - JPEG not supported PHP');
            }
        } elseif ($mime === 'image/png') {
            if (imagetypes() && IMG_PNG) {
                $file = imagecreatefrompng($source);
                $ext = '.png';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - PNG not supported PHP');
            }
        } elseif ($mime === 'image/wbmp') {
            if (imagetypes() && IMG_WBMP) {
                $file = imagecreatefromwbmp($source);
                $ext = '.wbmp';
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
    protected function hex2rbg($hex)
    {
        $color = str_replace('#', '', $hex);

        return [
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        ];
    }

    static public function getAspectRatio(&$width, &$height, $s_width, $s_height, $oversize)
    {
        if ($s_width > $s_height) {
            if ($oversize) {
                $nHeight = round(($s_height / $s_width) * $width);
                if ($nHeight < $height) {
                    $width = round(($height * $s_width) / $s_height);
                } else {
                    $height = $nHeight;
                }
            } else {
                $height = round(($s_height / $s_width) * $width);
            }
        } elseif ($s_height > $s_width) {
            if ($oversize) {
                $nWidth = round(($height * $s_width) / $s_height);
                if ($nWidth < $width) {
                    $height = round(($s_height / $s_width) * $width);
                } else {
                    $width = $nWidth;
                }
            } else {
                $width = round(($height * $s_width) / $s_height);
            }
        }
    }

    /**
     * @param string $img
     *
     * @return array
     */
    static public function getImageSize($img)
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
