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

require_once('../vendor/autoload.php');

try {
    /**
     * Resize image
     */
    $Image = new eImage([
        'source' => 'my_source_image.jpg',
        'prefix' => 'AfterResize-',
        'aspectRatio' => false,
        'scaleUp' => true
    ]);

    $Image->resize(600, 205);
} catch (eImageException $e) {
    echo $e->getMessage();
    /** do something else **/
}
