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
     * Simple Upload
     */
    $Image = new eImage();
    $Image->upload($File);

    /** ---------- run one or another but not both --------- */

    /**
     * the next code will do the following:
     * Rename the image to my_new_image.
     * Place the uploaded image into base_dir/Images/
     * Create a new unique image if find an existing one.
     * return an array with the new image properties.
     */
    $Image = new eImage([
        'NewName'    => 'my_new_name.bmp',
        'UploadTo'   => 'Images/',
        'Duplicates' => 'u',
        'ReturnType' => 'array'
    ]);
    $Image->upload($File);

} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}
