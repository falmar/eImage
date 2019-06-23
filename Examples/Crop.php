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

use Falmar\eImage\eImage;
use Falmar\eImage\eImageException;

require_once('../vendor/autoload.php');

try {
    /**
     * Crop image
     */
    $Image = new eImage([
        'source' => 'path_to_your_file.jpg',
        'prefix' => 'AfterCrop-'
    ]);

    $Image->crop(250, 250, -50, -75);
} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}
