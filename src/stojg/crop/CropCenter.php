<?php

namespace stojg\crop;

/**
 * CropCenter
 * 
 * The most basic of cropping techniques:
 *
 * 1. Find the exact center of the image
 * 2. Trim any edges that is bigger than the targetWidth and targetHeight
 *
 */
class CropCenter extends Crop {

	/**
	 *
	 * @param string $imagePath
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return boolean|\Imagick
	 */
	public function resizeAndCrop($targetWidth, $targetHeight) {
		// First get the size that we can use to safely trim down the image to without cropping any sides
		$crop = $this->getSafeResizeOffset($this->originalImage, $targetWidth, $targetHeight);
		// Resize image
		$this->originalImage->resizeImage($crop['width'], $crop['height'], \Imagick::FILTER_CATROM, 0.5);
		// Get the offset from the center of the image
		$offset = $this->getCenterOffset($this->originalImage, $targetWidth, $targetHeight);
		// Crop the image
		$this->originalImage->cropImage($targetWidth, $targetHeight, $offset['x'], $offset['y']);
		return $this->originalImage;
	}

	/**
	 * Get the cropping offset for the image based on the center of the image
	 *
	 * @param Imagick $image
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getCenterOffset(\Imagick $image, $targetWidth, $targetHeight) {
		$size = $image->getImageGeometry();
		$originalWidth = $size['width'];
		$originalHeight = $size['height'];
		$goalX = (int)(($originalWidth-$targetWidth)/2);
		$goalY = (int)(($originalHeight-$targetHeight)/2);
		return array('x' => $goalX, 'y' => $goalY);
	}

}
