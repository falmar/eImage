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

namespace eImage;

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

    /**
     * eImage constructor.
     *
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        $this->setProperties($Options);
    }

    public function setProperties($Options = [])
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

        if (!$arUpload['error']) {
            switch ($arUpload['error']) {
                case 1:
                    throw new eImageException(eImageException::UPLOAD_INI_MAX);
                    break;
                case 2:
                    throw new eImageException(eImageException::UPLOAD_FORM_MAX);
                    break;
                case 3:
                    throw new eImageException(eImageException::UPLOAD_PARTIAL);
                    break;
                case 4:
                    throw new eImageException(eImageException::UPLOAD_NO_FILE);
                    break;
                case 6:
                    throw new eImageException(eImageException::UPLOAD_NO_TMP_DIR);
                    break;
                case 7:
                    throw new eImageException(eImageException::UPLOAD_WRITE_AC);
                    break;
                case 8:
                    throw new eImageException(eImageException::UPLOAD_EXT);
                    break;
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

        $ImageName     = $arUpload['name'];
        $ImageType     = $arUpload['type'];
        $ImageTempName = $arUpload['tmp_name'];
        $ImageSize     = $arUpload['size'];
        $Enabled       = false;

        $Ext = substr($ImageName, strrpos($ImageName, '.'));

        if ($this->DisabledMIMEs && (!array_key_exists($Ext, $this->DisabledMIMEs) || !in_array($ImageType, $this->DisabledMIMEs))) {
            $Enabled = true;
        }

        if ((!array_key_exists($Ext, $this->EnableMIMEs) || !in_array($ImageType, $this->EnableMIMEs)) && !$Enabled) {
            throw new eImageException(eImageException::UPLOAD_EXT);
        }

        if ($this->SafeRename) {
            $ImagePath = str_replace(basename($ImageName), '', $ImageName);
            $ImageName = $this->cleanUp(($this->NewName) ? $this->NewName : $ImageName);
            $ImageName = $ImagePath . $ImageName;
        }

        if ($newExt = strrchr($ImageName, '.')) {
            if ($newExt != $Ext) {
                $ImageName = str_replace($newExt, '', $ImageName) . $Ext;
            }
        } else {
            $ImageName = $ImageName . $Ext;
        }

        $Target = $this->UploadTo . basename($ImageName);

        if (file_exists($Target)) {
            switch ($this->Duplicates) {
                case 'o':
                    break;
                case 'e':
                    throw new eImageException(eImageException::IMAGE_EXIST);
                    break;
                case 'a':
                    return false;
                    break;
                default:
                    if (strrpos(basename($ImageName), '.')) {
                        $im = substr(basename($ImageName), 0, strrpos(basename($ImageName), '.'));
                    } else {
                        $im = basename($ImageName);
                    }

                    $Ext  = str_replace($im, '', basename($ImageName));
                    $Path = $this->UploadTo;
                    $i    = 0;

                    while (file_exists($Path . $im . '_' . $i . $Ext)) {
                        $i++;
                    }

                    $im        = $im . '_' . $i . $Ext;
                    $Path      = str_replace(basename($ImageName), '', $ImageName);
                    $ImageName = $Path . $im;
                    $Target    = $this->UploadTo . $ImageName;
                    break;
            }
        }

        if (file_exists($Target) && !is_writable($Target)) {
            @chmod($Target, 0777);
        }

        if (move_uploaded_file($ImageTempName, $Target)) {
            /** @var string $Source easy access for resize and crop functions */
            $this->Source = $Target;

            switch (strtolower($this->ReturnType)) {
                case 'array':
                    return [
                        'name'      => basename($ImageName),
                        'path'      => $this->UploadTo,
                        'size'      => $ImageSize,
                        'tmp_name'  => $ImageTempName,
                        'full_path' => $Target
                    ];
                    break;
                case 'full_path':
                    return (file_exists($Target)) ? $Target : false;
                default:
                    return (file_exists($Target)) ? true : false;
                    break;
            }

        } else {
            throw new eImageException(eImageException::UPLOAD_FAILED);
        }
    }

    /**
     * TODO
     * Create a new image from an existing file according to width and height passed
     *
     * @param $Width
     * @param $Height
     */
    public function resize($Width, $Height) { }

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

        switch (getimagesize($Source)['mime']) {
            case 'image/gif':
                if (imagetypes() && IMG_GIF) {
                    $File = imagecreatefromgif($Source);
                    $Ext  = ($this->NewExtension) ? $this->NewExtension : '.gif';
                } else {
                    throw new eImageException(eImageException::CROP_EXT . ' - GIF not supported PHP');
                }
                break;
            case 'image/jpeg':
                if (imagetypes() && IMG_JPEG) {
                    $File = imagecreatefromjpeg($Source);
                    $Ext  = ($this->NewExtension) ? $this->NewExtension : '.jpeg';
                } else {
                    throw new eImageException(eImageException::CROP_EXT . ' - JPEG not supported PHP');
                }
                break;
            case 'image/png':
                if (imagetypes() && IMG_PNG) {
                    $File = imagecreatefrompng($Source);
                    $Ext  = ($this->NewExtension) ? $this->NewExtension : '.png';
                    imagealphablending($File, true);
                    imagesavealpha($File, true);
                } else {
                    throw new eImageException(eImageException::CROP_EXT . ' - PNG not supported PHP');
                }
                break;
            case 'image/wbmp':
                if (imagetypes() && IMG_WBMP) {
                    $File = imagecreatefromwbmp($Source);
                    $Ext  = ($this->NewExtension) ? $this->NewExtension : '.wbmp';
                } else {
                    throw new eImageException(eImageException::CROP_EXT . ' - WBMP not supported PHP');
                }
                break;
            default:
                throw new eImageException(eImageException::CROP_EXT);
                break;
        }

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


        $Name   = str_replace($Path, '', $Source);
        $Name   = (strrpos($Name, $DS) !== strlen($Name)) ? $Name . $DS : $Name;
        $Name   = (strrpos($Name, '.')) ? substr($Name, 0, strrpos($Name, '.')) . $Ext : $Name . $Ext;
        $Name   = ($this->SafeRename) ? $this->cleanUp($Name) : $Name;
        $Prefix = $this->Prefix;

        if (file_exists($Path . $Prefix . $Name)) {
            switch ($this->Duplicates) {
                case 'o':
                    break;
                case 'e':
                    throw new eImageException(eImageException::IMAGE_EXIST);
                    break;
                case 'a':
                    return false;
                    break;
                default:
                    if (strrpos(($Name), '.')) {
                        $im = substr(($Name), 0, strrpos(($Name), '.'));
                    } else {
                        $im = ($Name);
                    }

                    $i = 0;
                    while (file_exists($Path . $im . '_' . $i . $Ext)) {
                        $i++;
                    }
                    $Name = $im . '_' . $i . $Ext;
                    break;
            }
        }

        $Source  = $Path . $Prefix . $Name;
        $Canvas  = imagecreatetruecolor($Width, $Height);
        $sWidth  = imagesx($File);
        $sHeight = imagesy($File);
        imagecopyresampled($Canvas, $File, $x, $y, 0, 0, $sWidth, $sHeight, $sWidth, $sHeight);

        switch ($Ext) {
            case '.png':
                imagepng($Canvas, $Source, ($this->ImageQuality > 90) ? 9 : ((int)$this->ImageQuality) / 10);
                break;
            case '.wbmp':
                imagewbmp($Canvas, $Source);
                break;
            default:
                imagejpeg($Canvas, $Source, $this->ImageQuality);
                break;
        }

        imagedestroy($File);
        imagedestroy($Canvas);

        switch (strtolower($this->ReturnType)) {
            case 'array':
                return [
                    'name'      => $Name,
                    'prefix'    => $Prefix,
                    'path'      => $Path,
                    'tmp_name'  => $Prefix . $Name,
                    'full_path' => $Source,
                    'width'     => $Width,
                    'height'    => $Height
                ];
                break;
            case 'full_path':
                return (file_exists($Source)) ? $Source : false;
            default:
                return (file_exists($Source)) ? true : false;
                break;
        }
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