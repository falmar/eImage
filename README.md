# eImage

eImage it's a simple PHP Class to make Uploading and Editing Images even more easy!

## Examples

### Simple Upload

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
  /** do something **/
}

```

### Setting up some parameters to the upload
This example will do the following:
- Rename the image to my_new_image
- Place the uploaded image into base_dir/Images/
- Create a new unique image if find an existing one
- return an array with the new image properties

```php
use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('eImage/autoload.php');

try {

    $Image = new eImage([
        'NewName' => 'my_new_name',
        'UploadTo' => 'Images/',
        'Duplicates' => 'u',
        'ReturnType' => 'array'
    ]);

    $Image->upload($File);

} catch (eImageException $e) {
    /** do something **/
}
```
> NOTE: If there is not an extension specified in 'NewName' Parameter it will take the extension from the original image


## Parameters and its default values

```php
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
```

#### NewName
Specify the new name for your image

#### UploadTo
Specify where the new image is going to be uploaded to

#### ReturnType
- 'array': Pretty close to the ```$_FILE``` array it will return name, path, size, tmp_name and additionally full_path
- 'full_path': string with the full path to the new image
- 'bool': true or false if the upload succeeded

#### SafeRename
- true: will clean the image name and remove strange characters
- false: the new image will contain the same name as the uploaded image 

#### Duplicates
if a there is an existing file:
- 'o': Overwrite the file
- 'u': Create and unique file
- 'e': throw eImageException
- 'a': return false 