<?php

require_once 'src/stojg/crop/Crop.php';
require_once 'src/stojg/crop/CropCenter.php';
require_once 'src/stojg/crop/CropEntropy.php';

use stojg\crop\CropEntropy;

class ClassEntropyTest extends PHPUnit_Framework_TestCase {
    
    const EXAMPLE_IMAGE = '/images/side.png';

    /**
     *
     * @var string 
     */
    protected $tempDir = '';
    
    public function setUp() {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
            return;
        }
         $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'croptest';
         
         if(file_exists($this->tempDir)) {
             $this->cleanup();
         }
         
         if(!mkdir($this->tempDir)) {
            $this->markTestSkipped('Can\'t create temp directory '. $this->tempDir .' skipping test');
        }
    }
    
    /**
     * 
     */
    public function tearDown() {
        $this->cleanup();
    }
    
    public function testEntropy() {
        $center = new CropEntropy(__DIR__ . self::EXAMPLE_IMAGE);
        $croppedImage = $center->resizeAndCrop(200, 200);
        $croppedImage->writeimage($this->tempDir.'/entropy-test.png');
    }
    
    public function testEntropyWithPreviusImagick() {
        $image = new Imagick(__DIR__ . self::EXAMPLE_IMAGE);

        $center = new CropEntropy();
        $center->setImage($image);

        $croppedImage = $center->resizeAndCrop(200, 200);

        $this->assertSame($image, $croppedImage);

        $croppedImage->writeimage($this->tempDir.'/entropy-test.png');
    }

    private function cleanup() {
        $testFiles = glob($this->tempDir.DIRECTORY_SEPARATOR.'*');
        foreach($testFiles as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }
}
