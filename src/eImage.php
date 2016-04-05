<?php
/**
 * Project: eImage
 * Date: 12/21/15
 * Time: 8:21 PM
 *
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Falmar\eImage;

/**
 * Class eImage
 * Image Upload and Edition
 *
 * @package eImage
 * @author  David Lavieri (falmar) <daviddlavier@gmail.com>
 */

class eImage
{
    /** @var string */
    public $NewName;

    /** @var string */
    public $UploadTo;

    /** @var string */
    public $ReturnType = 'full_path';

    /** @var bool */
    public $SafeRename = true;

    /** @var string */
    public $Duplicates = 'o';

    /** @var array */
    private $EnableMIMEs = [
        '.jpe'  => 'image/jpeg',
        '.jpg'  => 'image/jpg',
        '.jpeg' => 'image/jpeg',
        '.gif'  => 'image/gif',
        '.png'  => 'image/png',
        '.bmp'  => 'image/bmp',
        '.ico'  => 'image/x-icon',
    ];

    /** @var array */
    private $DisabledMIMEs = [];

    /** @var bool */
    public $CreateDir = false;

    /** @var string */
    public $Source;

    /** @var int */
    public $ImageQuality = 90;

    /** @var string */
    public $NewExtension;

    /** @var string */
    public $Prefix;

    /** @var string */
    public $NewPath;

    /** @var bool */
    public $AspectRatio = true;

    /** @var bool */
    public $Oversize = false;

    /** @var bool */
    public $ScaleUp = false;

    /** @var string */
    public $PadColor = 'transparent';

    /** @var bool */
    public $FitPad = true;

    /** @var string */
    public $Position = 'cc';

    /**
     * eImage constructor.
     *
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        $this->set($Options);
    }

    public function set($Options = [])
    {
        if (is_array($Options)) {
            foreach ($Options as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
            }
        }
    }

    /**
     * @param $String
     * @return mixed
     */
    public function cleanUp($String)
    {
        $String = preg_replace('/\s/i', '', $String);
        if (strrpos($String, '.')) {
            $String = substr($String, 0, strrpos($String, '.')) . strtolower(strrchr($String, '.'));
        }

        return preg_replace('/[^A-Za-z0-9_\-\.]/i', '', $String);
    }

    /**
     * @param array $arUpload
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

        if ($this->UploadTo) {
            if (!is_dir($this->UploadTo)) {
                if ($this->CreateDir) {
                    mkdir($this->UploadTo, 0777);
                } else {
                    throw new eImageException(eImageException::UPLOAD_NO_DIR);
                }
            }
            if (strrpos($this->UploadTo, DIRECTORY_SEPARATOR) !== strlen($this->UploadTo) - 1) {
                $this->UploadTo .= DIRECTORY_SEPARATOR;
            }
        }

        $ImageName     = ($this->NewName) ? $this->NewName : $arUpload['name'];
        $ImageName     = ($this->SafeRename) ? $this->cleanUp($ImageName) : $ImageName;
        $ImageType     = $arUpload['type'];
        $ImageTempName = $arUpload['tmp_name'];
        $ImageSize     = $arUpload['size'];
        $Ext           = substr($arUpload['name'], strrpos($arUpload['name'], '.'));
        $Enabled       = false;

        if (is_integer(strpos($Ext, 'jpg'))) {
            $Ext = '.jpeg';
        }

        if ($newExt = strrchr($ImageName, '.')) {
            if ($newExt != $Ext) {
                $ImageName = str_replace($newExt, '', $ImageName) . $Ext;
            }
        } else {
            $ImageName = $ImageName . $Ext;
        }

        if ($this->DisabledMIMEs && (!array_key_exists($Ext, $this->DisabledMIMEs) || !in_array($ImageType, $this->DisabledMIMEs))) {
            $Enabled = true;
        }

        if ((!array_key_exists($Ext, $this->EnableMIMEs) || !in_array($ImageType, $this->EnableMIMEs)) && !$Enabled) {
            throw new eImageException(eImageException::UPLOAD_EXT);
        }

        $Prefix = $this->Prefix;
        $this->handleDuplicates($this->UploadTo, $Prefix, $ImageName, $Ext);
        $Target = $this->UploadTo . $Prefix . $ImageName;

        if (file_exists($Target) && !is_writable($Target)) {
            @chmod($Target, 0777);
        }

        if (move_uploaded_file($ImageTempName, $Target)) {
            /** @var string $Source easy access for resize and crop functions */
            $this->Source = $Target;

            $ReturnType = strtolower($this->ReturnType);
            if ($ReturnType === 'array') {
                return [
                    'name'      => basename($ImageName),
                    'path'      => $this->UploadTo,
                    'size'      => $ImageSize,
                    'tmp_name'  => $ImageTempName,
                    'full_path' => $this->Source
                ];
            } elseif ($ReturnType === 'full_path') {
                return (file_exists($this->Source)) ? $this->Source : false;
            } else {
                return (file_exists($this->Source)) ? true : false;
            }

        } else {
            throw new eImageException(eImageException::UPLOAD_FAILED);
        }
    }

    /**
     * Create a new image from an existing file according to width and height passed
     *
     * @param $Width
     * @param $Height
     * @return array|bool|string
     * @throws eImageException
     */
    public function resize($Width, $Height)
    {
        if (!is_integer($Width)) {
            throw new eImageException(eImageException::NO_WIDTH);
        } elseif (!is_integer($Height)) {
            throw new eImageException(eImageException::NO_HEIGHT);
        } elseif (!is_string($this->Source)) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $Source = $this->Source;

        $this->imageCreateSource($Source, $File, $Ext);

        $DS   = DIRECTORY_SEPARATOR;
        $Path = (is_integer(strpos($Source, $DS))) ? substr($Source, 0, strrpos($Source, $DS) + 1) : null;
        $Path = trim(($this->NewPath) ? $this->NewPath : $Path);

        if (!is_dir($Path) && $Path) {
            if ($this->CreateDir) {
                mkdir($Path, 0777);
            } else {
                throw new eImageException(eImageException::UPLOAD_NO_DIR);
            }
        }

        $Name   = ($this->NewName) ? $this->NewName : str_replace($Path, '', $Source);
        $Name   = (strrpos($Name, $DS) !== strlen($Name)) ? $Name . $DS : $Name;
        $Name   = (strrpos($Name, '.')) ? substr($Name, 0, strrpos($Name, '.')) . $Ext : $Name . $Ext;
        $Name   = ($this->SafeRename) ? $this->cleanUp($Name) : $Name;
        $Prefix = $this->Prefix;

        $this->handleDuplicates($Path, $Prefix, $Name, $Ext);

        $Source = $Path . $Prefix . $Name;

        $s_Width  = imagesx($File);
        $s_Height = imagesy($File);

        $c_Width  = $Width;
        $c_Height = $Height;

        if ($this->AspectRatio) {
            if ($s_Width > $s_Height) {
                if ($this->Oversize) {
                    $nHeight = round(($s_Height / $s_Width) * $Width);
                    if ($nHeight < $Height) {
                        $Width = round(($Height * $s_Width) / $s_Height);
                    } else {
                        $Height = $nHeight;
                    }
                } else {
                    $Height = round(($s_Height / $s_Width) * $Width);
                }
            } elseif ($s_Height > $s_Width) {
                if ($this->Oversize) {
                    $nWidth = round(($Height * $s_Width) / $s_Height);
                    if ($nWidth < $Width) {
                        $Height = round(($s_Height / $s_Width) * $Width);
                    } else {
                        $Width = $nWidth;
                    }
                } else {
                    $Width = round(($Height * $s_Width) / $s_Height);
                }
            }
        }

        if (!$this->FitPad) {
            $c_Width  = $Width;
            $c_Height = $Height;
        }

        if (!$this->ScaleUp) {
            if ($s_Width <= $Width && $s_Height <= $Height) {
                $Width    = $s_Width;
                $Height   = $s_Height;
                $c_Width  = $s_Width;
                $c_Height = $s_Height;
            }
        }

        $Position = [
            'dx' => 0,
            'dy' => 0,
            'sx' => 0,
            'sy' => 0
        ];

        if ($this->FitPad) {
            $top    = 0;
            $left   = 0;
            $right  = $c_Width - $Width;
            $bottom = $c_Height - $Height;
            $x      = ($c_Width - $Width) / 2;
            $y      = ($c_Height - $Height) / 2;

            if (strpos($this->Position, ',')) {
                $Dimensions     = explode($this->Position, ',');
                $x              = (int)@$Dimensions[0];
                $y              = (int)@$Dimensions[1];
                $Position['dx'] = $x;
                $Position['dy'] = $y;
            } elseif ($this->Position === 'tl') {
                $Position['dx'] = $left;
                $Position['dy'] = $top;
            } elseif ($this->Position === 'tr') {
                $Position['dx'] = $right;
                $Position['dy'] = $top;
            } elseif ($this->Position === 'tc') {
                $Position['dx'] = $x;
                $Position['dy'] = $top;
            } elseif ($this->Position === 'bl') {
                $Position['dx'] = $left;
                $Position['dy'] = $bottom;
            } elseif ($this->Position === 'br') {
                $Position['dx'] = $right;
                $Position['dy'] = $bottom;
            } elseif ($this->Position === 'bc') {
                $Position['dx'] = $x;
                $Position['dy'] = $bottom;
            } elseif ($this->Position === 'cl') {
                $Position['dx'] = $left;
                $Position['dy'] = $y;
            } elseif ($this->Position === 'cr') {
                $Position['dx'] = $right;
                $Position['dy'] = $y;
            } else {
                $Position['dx'] = $x;
                $Position['dy'] = $y;
            }
        }

        $Canvas = imagecreatetruecolor($c_Width, $c_Height);

        if ($this->PadColor == 'transparent') {
            imagealphablending($Canvas, false);
            $color = imagecolorallocatealpha($Canvas, 0, 0, 0, 127);
            imagefill($Canvas, 0, 0, $color);
            imagesavealpha($Canvas, true);
        } else {
            $Color = $this->hex2rbg($this->PadColor);
            imagefill($Canvas, 0, 0, imagecolorallocate($Canvas, $Color['r'], $Color['b'], $Color['g']));
        }

        imagecopyresampled($Canvas, $File, $Position['dx'], $Position['dy'], $Position['sx'], $Position['sy'], $Width, $Height, $s_Width, $s_Height);

        $Quality = $this->ImageQuality;

        if ($this->PadColor == 'transparent') {
            if ($Ext != '.gif') {
                $Ext = '.png';
            }
        } else {
            if ($Ext == '.gif') {
                $Ext = '.jpg';
            }
        }

        if ($Ext == '.png') {
            $Quality = ($Quality > 90) ? 9 : (int)(($Quality) / 10);
        }

        $this->imageCreate($Ext, $Canvas, $Source, $Quality);

        imagedestroy($File);
        imagedestroy($Canvas);

        $ReturnType = strtolower($this->ReturnType);
        if ($ReturnType === 'array') {
            return [
                'name'      => $Name,
                'prefix'    => $Prefix,
                'path'      => $Path,
                'width'     => $Width,
                'height'    => $Height,
                'pad_color' => $this->PadColor,
                'full_path' => $Source
            ];
        } elseif ($ReturnType === 'full_path') {
            return (file_exists($Source)) ? $Source : false;
        } else {
            return (file_exists($Source)) ? true : false;
        }
    }

    /**
     * Create a new image from an existing file according to x, y, width, height passed
     *
     * @param $Width
     * @param $Height
     * @param $x
     * @param $y
     * @return array|bool|string
     * @throws eImageException
     */
    public function crop($Width, $Height, $x, $y)
    {
        if (!is_integer($Width)) {
            throw new eImageException(eImageException::NO_WIDTH);
        } elseif (!is_integer($Height)) {
            throw new eImageException(eImageException::NO_HEIGHT);
        } elseif (!is_integer($x)) {
            throw new eImageException(eImageException::NO_X);
        } elseif (!is_integer($y)) {
            throw new eImageException(eImageException::NO_Y);
        }

        if (!$this->Source || !file_exists($this->Source)) {
            throw new eImageException(eImageException::NO_IMAGE);
        }

        $Source = $this->Source;

        $this->imageCreateSource($Source, $File, $Ext);

        $DS   = DIRECTORY_SEPARATOR;
        $Path = (is_integer(strpos($Source, $DS))) ? substr($Source, 0, strrpos($Source, $DS) + 1) : null;
        $Path = trim(($this->NewPath) ? $this->NewPath : $Path);

        if (!is_dir($Path) && $Path) {
            if ($this->CreateDir) {
                mkdir($Path, 0777);
            } else {
                throw new eImageException(eImageException::UPLOAD_NO_DIR);
            }
        }

        $Name   = ($this->NewName) ? $this->NewName : str_replace($Path, '', $Source);
        $Name   = (strrpos($Name, $DS) !== strlen($Name)) ? $Name . $DS : $Name;
        $Name   = (strrpos($Name, '.')) ? substr($Name, 0, strrpos($Name, '.')) . $Ext : $Name . $Ext;
        $Name   = ($this->SafeRename) ? $this->cleanUp($Name) : $Name;
        $Prefix = $this->Prefix;

        $this->handleDuplicates($Path, $Prefix, $Name, $Ext);

        $Source  = $Path . $Prefix . $Name;
        $Canvas  = imagecreatetruecolor($Width, $Height);
        $sWidth  = imagesx($File);
        $sHeight = imagesy($File);
        imagecopyresampled($Canvas, $File, $x, $y, 0, 0, $sWidth, $sHeight, $sWidth, $sHeight);

        $Quality = $this->ImageQuality;

        if ($Ext == '.png') {
            $Quality = ($Quality > 90) ? 9 : ((int)$Quality) / 10;
        }

        $this->imageCreate($Ext, $Canvas, $Source, $Quality);

        imagedestroy($File);
        imagedestroy($Canvas);

        $ReturnType = strtolower($this->ReturnType);
        if ($ReturnType === 'array') {
            return [
                'name'      => $Name,
                'prefix'    => $Prefix,
                'path'      => $Path,
                'full_path' => $Source,
                'width'     => $Width,
                'height'    => $Height
            ];
        } elseif ($ReturnType === 'full_path') {
            return (file_exists($Source)) ? $Source : false;
        } else {
            return (file_exists($Source)) ? true : false;
        }
    }

    /**
     * @param string $Path
     * @param string $Prefix
     * @param string $Name
     * @param string $Ext
     * @return bool
     * @throws eImageException
     */
    private function handleDuplicates($Path, $Prefix, &$Name, &$Ext)
    {
        if (file_exists($Path . $Prefix . $Name)) {

            if ($this->Duplicates === 'o') {
            } elseif ($this->Duplicates === 'e') {
                throw new eImageException(eImageException::IMAGE_EXIST);
            } else {
                if (strrpos(($Name), '.')) {
                    $im = substr(($Name), 0, strrpos(($Name), '.'));
                } else {
                    $im = ($Name);
                }

                $i = 0;
                while (file_exists($Path . $Prefix . $im . '_' . $i . $Ext)) {
                    $i++;
                }
                $Name = $Prefix . $im . '_' . $i . $Ext;
            }
        }

        return true;
    }

    /**
     * @param string   $Ext
     * @param resource $Canvas
     * @param string   $Name
     * @param null|int $Quality
     */
    private function imageCreate($Ext, $Canvas, $Name, $Quality = null)
    {
        $Quality = (!is_null($Quality)) ? $Quality : $this->ImageQuality;

        if ($Ext === '.gif') {
            imagegif($Canvas, $Name);
        } elseif ($Ext === '.png') {
            imagepng($Canvas, $Name, $Quality);
        } elseif ($Ext === '.wbmp') {
            imagewbmp($Canvas, $Name);
        } else {
            imagejpeg($Canvas, $Name, $Quality);
        }
    }

    /**
     * @param string   $Source
     * @param resource $File
     * @param string   $Ext
     * @throws eImageException
     */
    private function imageCreateSource($Source, &$File, &$Ext)
    {
        $Mime = getimagesize($Source)['mime'];
        if ($Mime === 'image/gif') {
            if (imagetypes() && IMG_GIF) {
                $File = imagecreatefromgif($Source);
                $Ext  = ($this->NewExtension) ? $this->NewExtension : '.gif';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - GIF not supported PHP');
            }
        } elseif ($Mime === 'image/jpeg') {
            if (imagetypes() && IMG_JPEG) {
                $File = imagecreatefromjpeg($Source);
                $Ext  = ($this->NewExtension) ? $this->NewExtension : '.jpeg';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - JPEG not supported PHP');
            }
        } elseif ($Mime === 'image/png') {
            if (imagetypes() && IMG_PNG) {
                $File = imagecreatefrompng($Source);
                $Ext  = ($this->NewExtension) ? $this->NewExtension : '.png';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - PNG not supported PHP');
            }
        } elseif ($Mime === 'image/wbmp') {
            if (imagetypes() && IMG_WBMP) {
                $File = imagecreatefromwbmp($Source);
                $Ext  = ($this->NewExtension) ? $this->NewExtension : '.wbmp';
            } else {
                throw new eImageException(eImageException::BAD_EXT . ' - WBMP not supported PHP');
            }
        } else {
            throw new eImageException(eImageException::BAD_EXT);
        }
    }

    /**
     * @param string $Hex
     * @return array
     */
    private function hex2rbg($Hex)
    {
        $Color = str_replace('#', '', $Hex);

        return [
            'r' => hexdec(substr($Color, 0, 2)),
            'g' => hexdec(substr($Color, 2, 2)),
            'b' => hexdec(substr($Color, 4, 2))
        ];
    }

    /** Helper function */

    /**
     * @param string $Img
     * @return array
     */
    public function getImageSize($Img)
    {
        if (file_exists($Img)) {
            list($width, $height) = getimagesize($Img);

            return [
                'width'  => $width,
                'height' => $height
            ];
        } else {
            return [
                'width'  => 0,
                'height' => 0
            ];
        }
    }
}
