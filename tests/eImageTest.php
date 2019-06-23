<?php
/**
 * Project: eImage
 * Date: 1/4/16
 * Time: 2:03 PM
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2016 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

use Falmar\eImage\eImage;
use Falmar\eImage\eImageException;
use PHPUnit\Framework\TestCase;

class eImageTest extends TestCase
{
    private function getSources()
    {
        return [
            'tests/assets/image.jpg' => [600, 399],
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


    /**
     * @covers Falmar\eImage\eImage::getImageSize
     */
    public function testGetImageSize()
    {
        foreach ($this->getSources() as $source => $size) {
            $this->assertArraySubset((new eImage())->getImageSize($source), [
                'width' => $size[0],
                'height' => $size[1]
            ]);
        }

        $this->assertArraySubset((new eImage())->getImageSize('fake_source.joke'), ['width' => 0, 'height' => 0]);
    }

    /**
     * @covers Falmar\eImage\eImage::resize
     */
    public function testResize()
    {
        foreach ($this->getSources() as $source => $sizes) {
            try {
                $name = substr($source, strrpos($source, '/') + 1);
                $width = $sizes[0];
                $height = $sizes[1];

                if (strpos($name, '.jpg')) {
                    $name = str_replace('.jpg', '.jpeg', $name);
                }

                $eImage = new eImage([
                    'source' => $source,
                    'prefix' => 'r_',
                    'returnType' => 'array',
                    'padColor' => '#FFFFFF'
                ]);

                $n_width = 300;
                $n_height = 273;

                $rst = $eImage->resize(300, 273);

                $this->getAspectRatio($n_width, $n_height, $width, $height, false);

                $ex_rst = [
                    'name' => $name,
                    'prefix' => 'r_',
                    'path' => 'tests/assets/',
                    'width' => $n_width,
                    'height' => $n_height,
                    'pad_color' => '#FFFFFF',
                    'full_path' => 'tests/assets/r_' . $name,
                ];

                $this->assertArraySubset($rst, $ex_rst);

                $width = $sizes[0];
                $height = $sizes[1];

                $eImage->set([
                    'oversize' => true,
                    'scaleUp' => true
                ]);

                $n_width = 200;
                $n_height = 250;

                $rst = $eImage->resize(200, 250);

                $this->getAspectRatio($n_width, $n_height, $width, $height, true);

                $ex_rst['height'] = $n_height;
                $ex_rst['width'] = $n_width;

                $this->assertArraySubset($rst, $ex_rst);

            } catch (eImageException $e) {
                $this->assertFalse(isset($e));
            }
        }
    }

    /**
     * @covers Falmar\eImage\eImage::crop
     */
    public function testCrop()
    {
        foreach ($this->getSources() as $source => $sizes) {

            try {
                $eImage = new eImage([
                    'source' => $source,
                    'prefix' => 'c_',
                    'returnType' => 'whatever'
                ]);

                $eImage->crop(rand(200, $sizes[0]), rand(200, $sizes[1]), rand(-400, 400), rand(-400, 400));
            } catch (eImageException $e) {

            }

            $this->assertFalse(isset($e));
        }
    }

    /**
     * @covers Falmar\eImage\eImage::crop
     * @covers Falmar\eImage\eImage::resize
     */
    public function testHandleDuplicates()
    {
        try {
            $eImage = new eImage([
                'source' => 'tests/assets/r_image.jpeg',
                'returnType' => 'bool',
                'padColor' => '#FFFFFF'
            ]);

            $this->assertTrue($eImage->resize(200, 300));

            $eImage->set(['duplicates' => 'u']);

            $this->assertTrue($eImage->crop(100, 150, -50, 50));
            $this->assertTrue(file_exists('tests/assets/r_image_0.jpeg'));

            $eImage->set([
                'source' => 'tests/assets/c_image.jpeg',
                'duplicates' => 'e',
                'scaleUp' => true
            ]);

            $eImage->resize(500, 500);
        } catch (eImageException $e) {
            $this->assertEquals(eImageException::IMAGE_EXIST, $e->getMessage());
        }

        $this->assertTrue(isset($e));
    }
}
