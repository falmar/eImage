# eImage

eImage it's a simple PHP Class to make Uploading and Editing Images even more easy!

A rewrite of a old Class (_image) originally written by Mark Jackson (mjdigital) all credits of main idea goes to him :D

Major changes from the original class:
- Used all available PSR (Autoload, CodeStyle, etc...)
- Added Exception to handle errors
- Reduced portions of code by putting them into general class methods
- Removed obsolete|unused methods|conditions|variables

## Examples


### Upload Image

```php
use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('eImage/autoload.php');

try {

  /**
   * Simple Upload
   */
  $Image = new eImage();
  $Image->upload($File);

  /** -------------------------------------------------- */

  /**
   * the next code will do the following:
   * Rename the image to my_new_image.
   * Place the uploaded image into base_dir/Images/
   * Create a new unique image if find an existing one.
   * return an array with the new image properties.
   */
  $Image = new eImage([
      'NewName'    => 'my_new_name',
      'UploadTo'   => 'Images/',
      'Duplicates' => 'u',
      'ReturnType' => 'array'
  ]);
  $Image->upload($File);
  
} catch (eImageException $e){
  /** do something **/
}

```
> NOTE: If there is not an extension specified in 'NewName' parameter it will take the extension from the original image.
> NOTE2: You can specify a new extension with NewExtension parameter

### Crop Image

```php
use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('eImage/autoload.php');

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
  
} catch (eImageException $e){
  /** do something **/
}

```
> NOTE: if you do not specify a NewName or Prefix parameter the original image will be override by the new crop image.

### Resize Image

```php
use eImage\eImage;
use eImage\eImageException;

/** Upload your image **/
$File = (isset($_FILES['img'])) ? $_FILES['img'] : null;

require_once('eImage/autoload.php');

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
   $Image->setProperties([
       'Source' => 'my_source_image.jpg',
       'Prefix' => 'AfterResize-',
       'AspectRatio' => false,
       'ScaleUp' => true
   ]);
   $Image->resize(600, 205);
  
} catch (eImageException $e){
  /** do something **/
}

```
> NOTE: You may want to specify resize properties such as AspectRatio, Oversize, ScaleUp according to your needs.


### Parameters and their default values

```php
public $NewName;

public $UploadTo;

public $ReturnType = 'full_path';

public $SafeRename = true;

public $Duplicates = 'o';

private $EnableMIMEs = [
    '.jpe'  => 'image/jpeg',
    '.jpg'  => 'image/jpg',
    '.jpeg' => 'image/jpeg',
    '.gif'  => 'image/gif',
    '.png'  => 'image/png',
    '.bmp'  => 'image/bmp',
    '.ico'  => 'image/x-icon',
];
private $DisabledMIMEs = [];

public $CreateDir = false;

public $Source;

public $ImageQuality = 90;

public $NewExtension;

public $Prefix;

public $NewPath;

public $AspectRatio = true;

public $Oversize = false;

public $ScaleUp = false;

public $Position = 'cc';

public $FitPad = true;

public $PadColor = 'transparent';
```

#### NewName
Specify the new name for your image.

#### UploadTo
Specify where the new image is going to be uploaded to.

#### ReturnType
- 'full_path': string with the full path to the new image.
- 'bool': true or false if the upload succeeded.
- 'array':
    - from upload() function: Pretty close to the ```$_FILE``` array it will return name, path, size, tmp_name and full_path.
    - from crop() function: Will return name, prefix, path, tmp_name, height, width and full_path.
    - from resize() function: Will return name, prefix, path, tmp_name, height, width, pad_color and full_path.

#### SafeRename
- true: will clean the image name and remove strange characters.
- false: the new image will contain the same name as the uploaded image.

#### Duplicates
If a there is an existing file:
- 'o': Overwrite the file.
- 'u': Create and unique file.
- 'e': Throw eImageException.
- 'a': Return false.

#### EnabledMIMEs
An array that contain the MIME Types the eImage Class will be allow to upload.
```php
['.jpg' => 'image/jpg']
```
#### DisabledMIMEs
If this property is set with values it will forbid to upload the MIME Types or Extensions specified.
> NOTE: Any other MIME Type or Extension THAT IS NOT SET HERE will be allowed to upload.

#### Source
Full path to a file automatically set after image upload for easy access resize and crop functions.

#### CreateDir
If set to true create a directory if not exist (UploadTo | NewPath).

#### ImageQuality
Integer [1-100]%.

#### NewExtension
Apply a new extension to the image (.jpg, .png, .gif).

#### Prefix
Specify a new prefix for the image.

#### NewPath
Specify path for the new image, it apply only for crop() and resize() functions.

#### AspectRatio
Set true or false if you want to maintain or not your image aspect ratio.

#### Oversize
If true it will oversize the image when width > height or the otherwise.

#### ScaleUp
Set true if want allow the image to scale up from a small size to a bigger one.

#### PadColor
Hexadecimal color string for the image background if does not fit the canvas, default is 'transparent'.

#### FitPad
Set true if want to make use of the Position to fit the image in the canvas when the new size does not fit and AspectRatio is true.

#### Position
Set the position of source in the canvas:
- 'tr': top right
- 'tl': top left
- 'tc': top center
- 'br': bottom right
- 'bl': bottom left
- 'bc': bottom center
- 'cr': center right
- 'cl': center left
- 'cc': center horizontal and vertically