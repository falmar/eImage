# eImage

eImage it's a simple PHP Class to make Uploading and Editing Images even more simple!

Examples
--------

```php

use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('eImage/autoload.php');

try {

  $Image = new eImage();
  $Image->upload($File);
  
} catch (eImageException $e){
  /** do somethng **/
}

```
