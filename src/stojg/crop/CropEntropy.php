<?php

namespace stojg\crop;

/**
 * SlyCropEntropy
 *
 * This class finds the a position in the picture with the most energy in it.
 *
 * Energy is in this case calculated by this
 *
 * 1. Take the image and turn it into black and white
 * 2. Run a edge filter so that we're left with only edges.
 * 3. Find a piece in the picture that has the highest entropy (i.e. most edges)
 * 4. Return coordinates that makes sure that this piece of the picture is not cropped 'away'
 *
 */
class CropEntropy extends Crop
{

	/**
	 * get special offset for class
	 *
	 * @param Imagick $original
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getSpecialOffset(\Imagick $original, $targetWidth, $targetHeight)
    {
		return $this->getEntropyOffsets($original, $targetWidth, $targetHeight);
	}

	
	/**
	 * Get the topleftX and topleftY that will can be passed to a cropping method.
	 *
	 * @param Imagick $original
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getEntropyOffsets(\Imagick $original, $targetWidth, $targetHeight)
    {
		$measureImage = clone($original);
		// Enhance edges
		$measureImage->edgeimage(1);
		// Turn image into a grayscale
		$measureImage->modulateImage(100, 0, 100);
		// Turn everything darker than this to pitch black
		$measureImage->blackThresholdImage("#070707");
		// Get the calculated offset for cropping
		return $this->getOffsetFromEntropy($measureImage, $targetWidth, $targetHeight);
	}

	/**
	 * Get the offset of where the crop should start
	 *
	 * @param \Imagick $image
	 * @param int $targetHeight
	 * @param int $targetHeight
	 * @param int $sliceSize
	 * @return array
	 */
	protected function getOffsetFromEntropy(\Imagick $originalImage, $targetWidth, $targetHeight)
    {
        // The entropy works better on a blured image
		$image = clone $originalImage;
		$image->blurImage(3, 2);

		$size = $image->getImageGeometry();

		$originalWidth = $rightX = $size['width'];
		$originalHeight = $bottomY = $size['height'];
		// This is going to be our goal for topleftY
		$topY = 0;
		// This is going to be our goal for topleftX
		$leftX = 0;

		// Just an arbitrary size of slice size
		$sliceSize = ceil(($originalWidth - $targetWidth) / 25);
				
		$leftSlice = null;
		$rightSlice = null;

		// while there still are uninvestigated slices of the image
		while ($rightX-$leftX > $targetWidth) {
			// Make sure that we don't try to slice outside the picture
			$sliceSize = min($rightX - $leftX - $targetWidth, $sliceSize);

			// Make a left slice image
			if (!$leftSlice) {
				$leftSlice = clone($image);
				$leftSlice->cropImage($sliceSize, $originalHeight, $leftX, 0);
			}

			// Make a right slice image
			if (!$rightSlice) {
				$rightSlice = clone($image);
				$rightSlice->cropImage($sliceSize, $originalHeight, $rightX - $sliceSize, 0);
			}

			// rightSlice has more entropy, so remove leftSlice and bump leftX to the right
			if ($this->grayscaleEntropy($leftSlice) < $this->grayscaleEntropy($rightSlice)) {
				$leftX += $sliceSize;
				$leftSlice = null;
			} else {
				$rightX -= $sliceSize;
				$rightSlice = null;
			}
		}

		$topSlice = null;
		$bottomSlice = null;

		// Just an arbitrary size of slice size
		$sliceSize = ceil(($originalHeight - $targetHeight) / 25);
				
		// while there still are uninvestigated slices of the image
		while ($bottomY-$topY > $targetHeight) {
			// Make sure that we don't try to slice outside the picture
			$sliceSize = min($bottomY - $topY - $targetHeight, $sliceSize);

			// Make a top slice image
			if (!$topSlice) {
				$topSlice = clone($image);
				$topSlice->cropImage($originalWidth, $sliceSize, 0, $topY);
			}
			// Make a bottom slice image
			if (!$bottomSlice) {
				$bottomSlice = clone($image);
				$bottomSlice->cropImage($originalWidth, $sliceSize, 0, $bottomY - $sliceSize);
			}
			// bottomSlice has more entropy, so remove topSlice and bump topY down
			if ($this->grayscaleEntropy($topSlice) < $this->grayscaleEntropy($bottomSlice)) {
				$topY += $sliceSize;
				$topSlice = null;
			} else {
				$bottomY -= $sliceSize;
				$bottomSlice = null;
			}
		}

		return array('x' => $leftX, 'y' => $topY);
	}

	/**
	 * Calculate the entropy for this image.
	 *
	 * A higher value of entropy means more noise / liveliness / color / business
	 *
	 * @param \Imagick $image
	 * @return float
	 *
	 * @see http://brainacle.com/calculating-image-entropy-with-python-how-and-why.html
	 * @see http://www.mathworks.com/help/toolbox/images/ref/entropy.html
	 */
	protected function grayscaleEntropy(\Imagick $image)
    {
		// The histogram consists of a list of 0-254 and the number of pixels that has that value
		$histogram = $image->getImageHistogram();
		
		return $this->getEntropy($histogram, $this->area($image));
	}

	/**
	 * Find out the entropy for a color image
	 * 
	 * If the source image is in color we need to transform RGB into a grayscale image
	 * so we can calculate the entropy more performant.
	 *
	 * @param \Imagick $image
	 * @return float
	 */
	protected function colorEntropy(Imagick $image)
    {
		$histogram = $image->getImageHistogram();
		$newHistogram = array();

		// Translates a color histogram into a bw histogram
		for ($idx = 0; $idx < count($histogram); $idx++) {
			$colors = $histogram[$idx]->getColor();
			$grey = $this->rgb2bw($colors['r'], $colors['g'], $colors['b']);
			if (!isset($newHistogram[$grey])) {
				$newHistogram[$grey] = $histogram[$idx]->getColorCount();
			} else {
				$newHistogram[$grey] += $histogram[$idx]->getColorCount();
			}
		}
		return $this->entropy($newHistogram, $this->area($image));
	}
}
