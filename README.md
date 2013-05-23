# Crop

This is a small set of image croppers that I created for testing automated cropping. 

## Requirements

 - PHP 5.3 with Imagick extension

## Usage

	$center = new \stojg\crop\CropCenter($filepath);
	$croppedImage = $center->resizeAndCrop($width, $height);
	$thumbnailPath = 'assets/thumbs/'.$fileInfo['filename'].'-center.jpg';
	$this->enhance($croppedImage);
	$croppedImage->writeimage($thumbnailPath);
