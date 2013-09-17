<?php

require_once 'src/stojg/crop/Crop.php';
require_once 'src/stojg/crop/CropCenter.php';
require_once 'src/stojg/crop/CropEntropy.php';

class ClassEntropyTest extends PHPUnit_Framework_TestCase {
	
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
		$center = new \stojg\crop\CropEntropy(__DIR__.'/images/side.png');
		$croppedImage = $center->resizeAndCrop(200, 200);
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
