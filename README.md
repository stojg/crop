# Crop

## Usage

	$center = new \stojg\crop\CropCenter($filepath);
	$croppedImage = $center->resizeAndCrop($width, $height);
	$thumbnailPath = 'assets/thumbs/'.$fileInfo['filename'].'-center.jpg';
	$this->enhance($croppedImage);
	$croppedImage->writeimage($thumbnailPath);