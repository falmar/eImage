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
                    'source' => $source,
                    'prefix' => 'r_',
                    'padColor' => '#FFFFFF'
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
                    'source' => $source,
                    'prefix' => 'c_',
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
                'padColor' => '#FFFFFF'
            ]);

            $path = $eImage->resize(200, 300);

            $this->assertIsString($path);
            $this->assertStringContainsString($path, 'tests/assets/r_image.jpeg');

            $eImage->setConfig(['duplicates' => 'u']);

            $path = $eImage->crop(100, 150, -50, 50);

            $this->assertIsString($path);
            $this->assertTrue(file_exists('tests/assets/r_image_0.jpeg'));

            $eImage->setConfig([
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
