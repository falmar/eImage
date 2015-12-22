<?php
/**
 * Project: eImage
 * Date: 12/22/15
 * Time: 1:19 PM
 *
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('../autoload.php');

try {

    /**
     * Crop from upload
     */
    $Image = new eImage();
    $Image->upload($File);
    $Image->crop(250, 250, -50, -75);

    /** -------------------------------------------------- */

    /**
     * Crop from source file
     */
    $Image->setProperties([
        'Source' => 'path_to_your_file.jpg',
        'Prefix' => 'AfterCrop-'
    ]);
    $Image->crop(250, 250, -50, -75);

} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}