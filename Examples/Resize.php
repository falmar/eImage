<?php
/**
 * Project: eImage
 * Date: 12/23/15
 * Time: 20:15 PM
 *
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

use Falmar\eImage\eImage;
use Falmar\eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('../vendor/autoload.php');

try {

    /**
     * Resize from upload
     */
    $Image = new eImage();
    $Image->upload($File);
    $Image->resize(600, 450);

    /** -------------------------------------------------- */


    /**
     * Resize from source file
     */
    $Image->set([
        'Source' => 'my_source_image.jpg',
        'Prefix' => 'AfterResize-',
        'AspectRatio' => false,
        'ScaleUp' => true
    ]);
    $Image->resize(600, 205);

} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}
