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

    /**
     * @covers Falmar\eImage\eImage::getImageSize
     */
    public function testGetImageSize()
    {
        foreach ($this->getSources() as $source => [$width, $height]) {
            $size = eImage::getImageSize($source);

            $this->assertEquals($width, $size['width']);
            $this->assertEquals($height, $size['height']);
        }

        $size = eImage::getImageSize('fake_source.joke');

        $this->assertEquals(0, $size['width']);
        $this->assertEquals(0, $size['height']);
    }

    /**
     * @covers Falmar\eImage\eImage::resize
     */
    public function testResize()
    {
        foreach ($this->getSources() as $source => $sizes) {
            try {
                $dir = dirname($source);
                $name = basename($source);

                $expectedPath = $dir . '/' . 'r_' . (str_replace('.jpg', '.jpeg', $name));

                $eImage = new eImage([
                    'sourcePath' => $source,
                    'prefix' => 'r_',
                    // 'padColor' => '#FFFFFF'
                ]);

                $path = $eImage->resize(300, 273);

                $this->assertIsString($path);

                $this->assertStringContainsString($expectedPath, $path);

                $size = $eImage->getImageSize($path);

                $this->assertEquals(300, $size['width']);
                $this->assertEquals(273, $size['height']);
            } catch (eImageException $e) {
                $this->fail($e->getMessage());
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
                    'sourcePath' => $source,
                    'prefix' => 'c_',
                ]);

                $eImage->crop(rand(200, $sizes[0]), rand(200, $sizes[1]), rand(-400, 400), rand(-400, 400));
            } catch (eImageException $e) {
                $this->fail($e->getMessage());
            }
        }
    }

    public function testHandleDuplicates()
    {
        // implement me
    }
}
