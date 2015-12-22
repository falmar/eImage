<?php
/**
 * Created by PhpStorm.
 * User: dlavieri
 * Date: 12/21/15
 * Time: 10:32 PM
 * Project: eImage
 */


use eImage\eImage;
use eImage\eImageException;

$Img = (isset($_FILES['img'])) ? $_FILES['img'] : null;

if ($Img) {
    require_once('../autoload.php');
    $eImage = new eImage([
        'SafeRename' => true,
        'NewName'    => 'sexyBuns',
        'Duplicates' => 'o',
        'UploadTo'   => __DIR__.'/../../YoloAmazing/'
    ]);

    try {
        $eImage->upload($Img);
    } catch (eImageException $e) {
        echo $e->getMessage();
    }

} else {
    echo "No File";
}