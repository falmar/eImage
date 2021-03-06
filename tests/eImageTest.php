<?php
/**
 * Project: eImage
 * Date: 1/4/16
 * Time: 2:03 PM
 *
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2016 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

use Falmar\eImage\eImage;
use Falmar\eImage\eImageException;

class eImageTest extends PHPUnit_Framework_TestCase
{
    private function getSources()
    {
        return [
            'tests/assets/image.jpg'  => [600, 399],
            'tests/assets/image2.png' => [1024, 819],
            'tests/assets/image3.gif' => [500, 278],
            'tests/assets/image4.jpg' => [457, 640]
        ];
    }

    private function getAspectRatio(&$Width, &$Height, $s_Width, $s_Height, $Oversize)
    {
        if ($s_Width > $s_Height) {
            if ($Oversize) {
                $nHeight = round(($s_Height / $s_Width) * $Width);
                if ($nHeight < $Height) {
                    $Width = round(($Height * $s_Width) / $s_Height);
                } else {
                    $Height = $nHeight;
                }
            } else {
                $Height = round(($s_Height / $s_Width) * $Width);
            }
        } elseif ($s_Height > $s_Width) {
            if ($Oversize) {
                $nWidth = round(($Height * $s_Width) / $s_Height);
                if ($nWidth < $Width) {
                    $Height = round(($s_Height / $s_Width) * $Width);
                } else {
                    $Width = $nWidth;
                }
            } else {
                $Width = round(($Height * $s_Width) / $s_Height);
            }
        }
    }


    public function testgetImageSize()
    {
        foreach ($this->getSources() as $source => $size) {
            $this->assertArraySubset((new eImage())->getImageSize($source), [
                'width'  => $size[0],
                'height' => $size[1]
            ]);
        }

        $this->assertArraySubset((new eImage())->getImageSize('fake_source.joke'), ['width' => 0, 'height' => 0]);

    }

    public function testResize()
    {
        foreach ($this->getSources() as $source => $sizes) {
            try {
                $name   = substr($source, strrpos($source, '/') + 1);
                $width  = $sizes[0];
                $height = $sizes[1];

                if (strpos($name, '.jpg')) {
                    $name = str_replace('.jpg', '.jpeg', $name);
                }

                $eImage = new eImage([
                    'Source'     => $source,
                    'Prefix'     => 'r_',
                    'ReturnType' => 'array',
                    'PadColor'   => '#FFFFFF'
                ]);

                $n_width  = 300;
                $n_height = 273;

                $rst = $eImage->resize(300, 273);

                $this->getAspectRatio($n_width, $n_height, $width, $height, false);

                $ex_rst = [
                    'name'      => $name,
                    'prefix'    => 'r_',
                    'path'      => 'tests/assets/',
                    'width'     => $n_width,
                    'height'    => $n_height,
                    'pad_color' => '#FFFFFF',
                    'full_path' => 'tests/assets/r_' . $name,
                ];

                $this->assertArraySubset($rst, $ex_rst);

                $width  = $sizes[0];
                $height = $sizes[1];

                $eImage->set(['Oversize' => true, 'ScaleUp' => true]);

                $n_width  = 200;
                $n_height = 250;

                $rst = $eImage->resize(200, 250);

                $this->getAspectRatio($n_width, $n_height, $width, $height, true);

                $ex_rst['height'] = $n_height;
                $ex_rst['width']  = $n_width;

                $this->assertArraySubset($rst, $ex_rst);

            } catch (eImageException $e) {

            }

            $this->assertFalse(isset($e));
        }
    }

    public function testCrop()
    {
        foreach ($this->getSources() as $source => $sizes) {

            try {
                $eImage = new eImage([
                    'Source'     => $source,
                    'Prefix'     => 'c_',
                    'ReturnType' => 'whatever'
                ]);

                $eImage->crop(rand(200, $sizes[0]), rand(200, $sizes[1]), rand(-400, 400), rand(-400, 400));
            } catch (eImageException $e) {

            }

            $this->assertFalse(isset($e));
        }
    }

    public function testhandleDuplicates()
    {
        try {
            $eImage = new eImage([
                'Source'     => 'tests/assets/r_image.jpeg',
                'ReturnType' => 'bool',
                'PadColor'   => '#FFFFFF'
            ]);

            $this->assertTrue($eImage->resize(200, 300));

            $eImage->set(['Duplicates' => 'u']);

            $this->assertTrue($eImage->crop(100, 150, -50, 50));
            $this->assertTrue(file_exists('tests/assets/r_image_0.jpeg'));

            $eImage->set([
                'Source'     => 'tests/assets/c_image.jpeg',
                'Duplicates' => 'e',
                'ScaleUp'    => true
            ]);

            $eImage->resize(500, 500);

        } catch (eImageException $e) {
            $this->assertEquals(eImageException::IMAGE_EXIST, $e->getMessage());
        }

        $this->assertTrue(isset($e));
    }
}
