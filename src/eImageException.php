<?php
/**
 * Project: eImage
 * Date: 12/21/15
 * Time: 8:52 PM
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Falmar\eImage;

/**
 * @codeCoverageIgnore
 * Class eImageException
 * @package eImage
 * @author  David Lavieri (falmar) <daviddlavier@gmail.com>
 */
class eImageException extends \Exception
{
    const NO_IMAGE = 'Please specify a source image';
    const NO_WIDTH = 'Width specified for the new image is NaN';
    const NO_HEIGHT = 'Height specified for the new image is NaN';
    const NO_X = 'X Position specified for the new image is NaN';
    const NO_Y = 'Y Position specified for the new image is NaN';
    const IMAGE_EXIST = 'The image file you specified already exist';

    const UPLOAD_NO_ARRAY = 'The uploaded file is not valid';
    const UPLOAD_INI_MAX = 'The file exceeds the filesize limit on the server';
    const UPLOAD_FORM_MAX = 'The file exceeds the filesize limit set for the form';
    const UPLOAD_PARTIAL = 'The file was partially uploaded';
    const UPLOAD_NO_FILE = 'No file was submitted for upload';
    const UPLOAD_NO_DIR = 'Target upload directory is missing';
    const UPLOAD_NO_TMP_DIR = 'Temporary upload directory is missing';
    const UPLOAD_WRITE_AC = 'Failed to write into the temporary folder (check permissions)';
    const UPLOAD_EXT = 'Invalid file extension';
    const UPLOAD_SIZE = 'Uploaded File had a filesize of zero or was corrupt';
    const UPLOAD_FAILED = 'Uploaded failed';

    const BAD_EXT = 'Invalid file extension';
}
