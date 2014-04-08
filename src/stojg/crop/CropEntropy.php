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
    const POTENTIAL_RATIO = 1.5;

    /**
     * get special offset for class
     *
     * @param  \Imagick $original
     * @param  int      $targetWidth
     * @param  int      $targetHeight
     * @return array
     */
    protected function getSpecialOffset(\Imagick $original, $targetWidth, $targetHeight)
    {
        return $this->getEntropyOffsets($original, $targetWidth, $targetHeight);
    }


    /**
     * Get the topleftX and topleftY that will can be passed to a cropping method.
     *
     * @param  \Imagick $original
     * @param  int      $targetWidth
     * @param  int      $targetHeight
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
     * @param  \Imagick $image
     * @param  int      $targetHeight
     * @param  int      $targetHeight
     * @param  int      $sliceSize
     * @return array
     */
    protected function getOffsetFromEntropy(\Imagick $originalImage, $targetWidth, $targetHeight)
    {
        // The entropy works better on a blured image
        $image = clone $originalImage;
        $image->blurImage(3, 2);

        $size = $image->getImageGeometry();

        $originalWidth = $size['width'];
        $originalHeight = $size['height'];

        $leftX = $this->slice($image, $originalWidth, $targetWidth, 'h');
        $topY = $this->slice($image, $originalHeight, $targetHeight, 'v');

        return array('x' => $leftX, 'y' => $topY);
    }


    /**
     * slice
     *
     * @param mixed $image
     * @param mixed $originalSize
     * @param mixed $targetSize
     * @param mixed $axis         h=horizontal, v = vertical
     * @access protected
     * @return void
     */
    protected function slice($image, $originalSize, $targetSize, $axis)
    {
        $aSlice = null;
        $bSlice = null;

        // Just an arbitrary size of slice size
        $sliceSize = ceil(($originalSize - $targetSize) / 25);

        $aBottom = $originalSize;
        $aTop = 0;

        // while there still are uninvestigated slices of the image
        while ($aBottom - $aTop > $targetSize) {
            // Make sure that we don't try to slice outside the picture
            $sliceSize = min($aBottom - $aTop - $targetSize, $sliceSize);

            // Make a top slice image
            if (!$aSlice) {
                $aSlice = clone $image;
                if ($axis === 'h') {
                    $aSlice->cropImage($sliceSize, $originalSize, $aTop, 0);
                } else {
                    $aSlice->cropImage($originalSize, $sliceSize, 0, $aTop);
                }
            }

            // Make a bottom slice image
            if (!$bSlice) {
                $bSlice = clone $image;
                if ($axis === 'h') {
                    $bSlice->cropImage($sliceSize, $originalSize, $aBottom - $sliceSize, 0);
                } else {
                    $bSlice->cropImage($originalSize, $sliceSize, 0, $aBottom - $sliceSize);
                }
            }

            // calculate slices potential
            $aPosition = ($axis === 'h' ? 'left' : 'top');
            $bPosition = ($axis === 'h' ? 'right' : 'bottom');

            $aPot = $this->getPotential($aPosition, $aTop, $sliceSize);
            $bPot = $this->getPotential($bPosition, $aBottom, $sliceSize);

            $canCutA = ($aPot <= 0);
            $canCutB = ($bPot <= 0);

            // if no slices are "cutable", we force if a slice has a lot of potential
            if (!$canCutA && !$canCutB) {
                if ($aPot * self::POTENTIAL_RATIO < $bPot) {
                    $canCutA = true;
                } elseif ($aPot > $bPot * self::POTENTIAL_RATIO) {
                    $canCutB = true;
                }
            }

            // if we can only cut on one side
            if ($canCutA xor $canCutB) {
                if ($canCutA) {
                    $aTop += $sliceSize;
                    $aSlice = null;
                } else {
                    $aBottom -= $sliceSize;
                    $bSlice = null;
                }
            } elseif ($this->grayscaleEntropy($aSlice) < $this->grayscaleEntropy($bSlice)) {
                // bSlice has more entropy, so remove aSlice and bump aTop down
                $aTop += $sliceSize;
                $aSlice = null;
            } else {
                $aBottom -= $sliceSize;
                $bSlice = null;
            }
        }

        return $aTop;
    }

    /**
     * getSafeZoneList
     *
     * @access protected
     * @return array
     */
    protected function getSafeZoneList()
    {
        return array();
    }

    /**
     * getPotential
     *
     * @param mixed $position
     * @param mixed $top
     * @param mixed $sliceSize
     * @access protected
     * @return void
     */
    protected function getPotential($position, $top, $sliceSize)
    {
        $safeZoneList = $this->getSafeZoneList();

        $safeRatio = 0;

        if ($position == 'top' || $position == 'left') {
            $start = $top;
            $end = $top + $sliceSize;
        } else {
            $start = $top - $sliceSize;
            $end = $top;
        }

        for ($i = $start; $i < $end; $i++) {
            foreach ($safeZoneList as $safeZone) {
                if ($position == 'top' || $position == 'bottom') {
                    if ($safeZone['top'] <= $i && $safeZone['bottom'] >= $i) {
                        $safeRatio = max($safeRatio, ($safeZone['right'] - $safeZone['left']));
                    }
                } else {
                    if ($safeZone['left'] <= $i && $safeZone['right'] >= $i) {
                        $safeRatio = max($safeRatio, ($safeZone['bottom'] - $safeZone['top']));
                    }
                }
            }
        }

        return $safeRatio;
    }

    /**
     * Calculate the entropy for this image.
     *
     * A higher value of entropy means more noise / liveliness / color / business
     *
     * @param  \Imagick $image
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
     * @param  \Imagick $image
     * @return float
     */
    protected function colorEntropy(\Imagick $image)
    {
        $histogram = $image->getImageHistogram();
        $newHistogram = array();

        // Translates a color histogram into a bw histogram
        $colors = count($histogram);
        for ($idx = 0; $idx < $colors; $idx++) {
            $colors = $histogram[$idx]->getColor();
            $grey = $this->rgb2bw($colors['r'], $colors['g'], $colors['b']);
            if (!isset($newHistogram[$grey])) {
                $newHistogram[$grey] = $histogram[$idx]->getColorCount();
            } else {
                $newHistogram[$grey] += $histogram[$idx]->getColorCount();
            }
        }

        return $this->getEntropy($newHistogram, $this->area($image));
    }
}
