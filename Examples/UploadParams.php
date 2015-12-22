<?php
/**
 * Project: eImage
 * Date: 12/22/15
 * Time: 1:26 PM
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

    $Image = new eImage([
        'NewName'    => 'my_new_name',
        'UploadTo'   => 'Images/',
        'Duplicates' => 'u',
        'ReturnType' => 'array'
    ]);

    $Image->upload($File);

} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}