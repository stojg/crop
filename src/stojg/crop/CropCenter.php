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
class CropCenter extends Crop
{

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
        return $this->getCenterOffset($original, $targetWidth, $targetHeight);
    }

    /**
     * Get the cropping offset for the image based on the center of the image
     *
     * @param  \Imagick $image
     * @param  int      $targetWidth
     * @param  int      $targetHeight
     * @return array
     */
    protected function getCenterOffset(\Imagick $image, $targetWidth, $targetHeight)
    {
        $size = $image->getImageGeometry();
        $originalWidth = $size['width'];
        $originalHeight = $size['height'];
        $goalX = (int) (($originalWidth-$targetWidth)/2);
        $goalY = (int) (($originalHeight-$targetHeight)/2);

        return array('x' => $goalX, 'y' => $goalY);
    }
}
