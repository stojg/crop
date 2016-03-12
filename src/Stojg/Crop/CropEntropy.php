<?php

namespace Stojg\Crop;

class CropEntropy
{
    /**
     * @var \Imagick
     */
    protected $image = null;

    /**
     * CropEntropy constructor
     *
     * @param \Imagick|null $image
     */
    public function __construct(\Imagick $image = null)
    {
        if ($image !== null) {
            $this->image = $image;
        } else {
            $this->image = new \Imagick();
        }
    }

    /**
     * @return \Imagick
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Get the area in pixels for this image
     *
     * @return int
     */
    public function area()
    {
        $size = $this->image->getImageGeometry();
        return $size['height'] * $size['width'];
    }

    /**
     * Calculate the entropy for this image.
     *
     * A higher value of entropy means more noise / liveliness / color / business
     *
     * @return float
     *
     * @see http://brainacle.com/calculating-image-entropy-with-python-how-and-why.html
     * @see http://www.mathworks.com/help/toolbox/images/ref/entropy.html
     */
    public function getGrayScaleEntropy()
    {
        $histogram = $this->image->getImageHistogram();
        return $this->getEntropy($histogram, $this->area());
    }

    /**
     *
     * @param  \ImagickPixel[] $histogram
     * @param  int $area
     * @return float
     */
    protected function getEntropy($histogram, $area)
    {
        $value = 0.0;
        foreach ($histogram as $pixel) {
            // calculates the percentage of pixels having this color value
            $p = $pixel->getColorCount() / $area;
            // A common way of representing entropy in scalar
            $value += $p * log($p, 2);
        }
        // $value is always 0.0 or negative, so transform into positive scalar value
        return -$value;
    }
}
