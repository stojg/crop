<?php

namespace stojg\crop;

/**
 *
 * Base class for all Croppers
 *
 */
abstract class Crop
{
    /**
     * Timer used for profiler / debugging
     *
     * @var float
     */
    protected static $start_time = 0.0;

    /**
     *
     * @var \Imagick
     */
    protected $originalImage = null;

    /**
     * Filter to use when resizing:
     * http://php.net/manual/en/imagick.constants.php#imagick.constants.filters
     * @var int
     */
    protected $filter = \Imagick::FILTER_CUBIC;

    /**
     * Blur to use when resizing:
     * http://php.net/manual/en/imagick.resizeimage.php
     * @var float
     */
    protected $blur = 0.5;

    /**
     * baseDimension
     *
     * @var array
     * @access protected
     */
    protected $baseDimension;

    /**
     * Profiling method
     */
    public static function start()
    {
        self::$start_time = microtime(true);
    }

    /**
     * Profiling method
     *
     * @return string
     */
    public static function mark()
    {
        $end_time = (microtime(true) - self::$start_time) * 1000;

        return sprintf("%.1fms", $end_time);
    }

    /**
     *
     * @param string|\Imagick $imagePath - The path to an image to load or an
     *                        Imagick instance. Paths can include wildcards for
     *                        file names, or can be URLs.
     */
    public function __construct($imagePath = null)
    {
        if ($imagePath) {
            if(is_string($imagePath)) {
                $this->setImage(new \Imagick($imagePath));
            } else {
                $this->setImage($imagePath);
            }
        }
    }

    /**
     * Sets the object Image to be croped
     *
     * @param  \Imagick $image
     * @return Crop
     */
    public function setImage(\Imagick $image)
    {
        $this->originalImage = $image;

        // set base image dimensions
        $this->setBaseDimensions(
            $this->originalImage->getImageWidth(),
            $this->originalImage->getImageHeight()
        );
        return $this;
    }

    /**
     * Get the area in pixels for this image
     *
     * @param  \Imagick $image
     * @return int
     */
    protected function area(\Imagick $image)
    {
        $size = $image->getImageGeometry();

        return $size['height'] * $size['width'];
    }

    /**
     * Get the filter value to use for resizeImage call
     * http://php.net/manual/en/imagick.constants.php#imagick.constants.filters
     *
     * @return int
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the filter value to use for resizeImage call
     * http://php.net/manual/en/imagick.constants.php#imagick.constants.filters
     *
     * @return Crop
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get the blur value to use for resizeImage call
     * http://php.net/manual/en/imagick.resizeimage.php
     *
     * @return float
     */
    public function getBlur()
    {
        return $this->blur;
    }

    /**
     * Set the blur value to use for resizeImage call:
     * http://php.net/manual/en/imagick.resizeimage.php
     *
     * @return Crop
     */
    public function setBlur($blur)
    {
        $this->blur = $blur;
        return $this;
    }

    /**
     * Resize and crop the image so it dimensions matches $targetWidth and $targetHeight
     *
     * @param  int              $targetWidth
     * @param  int              $targetHeight
     * @return boolean|\Imagick
     */
    public function resizeAndCrop($targetWidth, $targetHeight)
    {
        // First get the size that we can use to safely trim down the image without cropping any sides
        $crop = $this->getSafeResizeOffset($this->originalImage, $targetWidth, $targetHeight);
        // Resize the image
        $this->originalImage->resizeImage($crop['width'], $crop['height'], $this->getFilter(), $this->getBlur());
        // Get the offset for cropping the image further
        $offset = $this->getSpecialOffset($this->originalImage, $targetWidth, $targetHeight);
        // Crop the image
        $this->originalImage->cropImage($targetWidth, $targetHeight, $offset['x'], $offset['y']);

        return $this->originalImage;
    }

    /**
     * Returns width and height for resizing the image, keeping the aspect ratio
     * and allow the image to be larger than either the width or height
     *
     * @param  \Imagick $image
     * @param  int      $targetWidth
     * @param  int      $targetHeight
     * @return array
     */
    protected function getSafeResizeOffset(\Imagick $image, $targetWidth, $targetHeight)
    {
        $source = $image->getImageGeometry();
        if (0 == $targetHeight || ($source['width'] / $source['height']) < ($targetWidth / $targetHeight)) {
            $scale = $source['width'] / $targetWidth;
        } else {
            $scale = $source['height'] / $targetHeight;
        }

        return array('width' => (int) ($source['width'] / $scale), 'height' => (int) ($source['height'] / $scale));
    }

    /**
     * Returns a YUV weighted greyscale value
     *
     * @param  int $r
     * @param  int $g
     * @param  int $b
     * @return int
     * @see http://en.wikipedia.org/wiki/YUV
     */
    protected function rgb2bw($r, $g, $b)
    {
        return ($r*0.299)+($g*0.587)+($b*0.114);
    }

    /**
     *
     * @param  array $histogram - a value[count] array
     * @param  int   $area
     * @return float
     */
    protected function getEntropy($histogram, $area)
    {
        $value = 0.0;

        $colors = count($histogram);
        for ($idx = 0; $idx < $colors; $idx++) {
            // calculates the percentage of pixels having this color value
            $p = $histogram[$idx]->getColorCount() / $area;
            // A common way of representing entropy in scalar
            $value = $value + $p * log($p, 2);
        }
        // $value is always 0.0 or negative, so transform into positive scalar value
        return -$value;
    }

    /**
     * setBaseDimensions
     *
     * @param int $width
     * @param int $height
     * @access protected
     * @return $this
     */
    protected function setBaseDimensions($width, $height)
    {
        $this->baseDimension = array('width' => $width, 'height' => $height);

        return $this;
    }

    /**
     * getBaseDimension
     *
     * @param string $key width|height
     * @access protected
     * @return int
     */
    protected function getBaseDimension($key)
    {
        if (isset($this->baseDimension)) {
            return $this->baseDimension[$key];
        } elseif ($key == 'width') {
            return $this->originalImage->getImageWidth();
        } else {
            return $this->originalImage->getImageHeight();
        }
    }

    /**
     * get special offset for class
     *
     * @param  \Imagick $original
     * @param  int      $targetWidth
     * @param  int      $targetHeight
     * @return array
     */
    abstract protected function getSpecialOffset(\Imagick $original, $targetWidth, $targetHeight);
}
