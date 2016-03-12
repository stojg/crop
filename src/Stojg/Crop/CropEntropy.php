<?php

namespace Stojg\Crop;

use Imagick;
use ImagickPixel;
use InvalidArgumentException;

/**
 * Class CropEntropy
 *
 * This class will help on finding the most energetic part of an image.
 *
 * @package Stojg\Crop
 */
class CropEntropy
{
    /**
     * @var Imagick
     */
    protected $image = null;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * CropEntropy constructor
     *
     * @param Imagick|null $image
     */
    public function __construct(Imagick $image = null)
    {
        if ($image !== null) {
            $this->image = $image;
        } else {
            $this->image = new Imagick();
        }
    }

    public function debugOn()
    {
        $this->debug = true;
    }

    public function debugOff()
    {
        $this->debug = false;
    }

    /**
     * @return Imagick
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param int $width - The width of the region to be extracted
     * @param int $height - The height of the region to be extracted
     * @param int $x - X-coordinate of the top-left corner of the region to be extracted
     * @param int $y -Y-coordinate of the top-left corner of the region to be extracted
     * @return CropEntropy
     */
    public function getRegion($width, $height, $x, $y)
    {
        return new CropEntropy($this->image->getImageRegion($width, $height, $x, $y));
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
     * @param CropEntropy $b
     * @return int
     */
    public function compare(CropEntropy $b)
    {
        $aValue = $this->getGrayScaleEntropy();
        $bValue = $b->getGrayScaleEntropy();


        if ($aValue == $bValue) {
            return 0;
        }

        return ($aValue < $bValue) ? -1 : 1;
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
     * @param string $axis - must be either 'x' or 'y'
     * @param $sliceSize
     * @return int
     */
    public function getMidPoint($axis, $sliceSize)
    {
        $currentPos = 0;

        if (!in_array($axis, ['x', 'y'])) {
            throw new InvalidArgumentException('argument $axis must be either "x" or "y"');
        }

        $image = new CropEntropy(clone($this->image));
        $image->getImage()->modulateImage(100, 0, 100);
        $image->getImage()->edgeImage(1);
        $image->getImage()->blackThresholdImage("#303030");

        $size = $this->image->getImageGeometry();


        if ($axis === 'x') {
            $max = $size['width'];
        } else {
            $max = $size['height'];
        }

        $sliceIndex = [];

        // until we have a slice that would fit inside the target size
        $left = $max;
        while ($left > 0) {
            $sliceSize = min($sliceSize, $left);
            if ($axis === 'x') {
                $a = $image->getVerticalSlice($currentPos, $sliceSize);
            } else {
                $a = $image->getHorizontalSlice($currentPos, $sliceSize);
            }
            $value = $a->getGrayScaleEntropy();
            $sliceIndex[] = $value;

            if ($this->debug) {
                $this->printDebug($axis, $sliceSize, $value, $currentPos, $size);
            }
            $currentPos += $sliceSize;
            $left -= $sliceSize;
        }
        $max = array_keys($sliceIndex, max($sliceIndex));
        return $sliceSize * $max[0] + $sliceSize / 2;
    }

    /**
     *
     * @param  ImagickPixel[] $histogram
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

    /**
     * @param int $x
     * @param int $sliceSize
     * @return CropEntropy
     */
    public function getVerticalSlice($x, $sliceSize)
    {
        $size = $this->image->getImageGeometry();
        return $this->getRegion($sliceSize, $size['height'], $x, 0);
    }

    /**
     * @param int $y
     * @param int $sliceSize
     * @return CropEntropy
     */
    public function getHorizontalSlice($y, $sliceSize)
    {
        $size = $this->image->getImageGeometry();
        return $this->getRegion($size['width'], $sliceSize, 0, $y);
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param string $fillColor
     */
    public function rectDraw($x1, $y1, $x2, $y2, $fillColor)
    {
        $draw = new \ImagickDraw();
        $draw->setStrokeWidth(1);
        $draw->setStrokeColor(new \ImagickPixel('rgba(0%, 0%, 0%, 0.5)'));
        $draw->setFillColor(new \ImagickPixel($fillColor));
        $draw->rectangle($x1, $y1, $x2, $y2);
        $this->image->drawImage($draw);
    }

    /**
     * @param int $x
     * @param int $y
     * @param string $text
     * @param int $angle - 0 to 350
     */
    public function drawText($x, $y, $text, $angle)
    {
        $draw = new \ImagickDraw();
        $draw->setFont('fonts/Hack-Regular.ttf');
        $draw->setFontSize(10);
        $this->image->annotateImage($draw, $x, $y, $angle, $text);
    }

    /**
     * @param string $axis
     * @param int $sliceSize
     * @param float $value
     * @param int $currentPos
     * @param array $size
     */
    protected function printDebug($axis, $sliceSize, $value, $currentPos, $size)
    {
        $text = sprintf('%05.5f', $value);
        if ($axis === 'x') {
            $this->rectDraw($currentPos, 0, $currentPos + $sliceSize, $size['height'],
                'rgba(100%, 0%, 0%, 0.1)');
            $this->drawText($currentPos + 5, $size['height'] - 5, $text, 0);
        } else {
            $this->rectDraw(0, $currentPos, $size['width'], $currentPos + $sliceSize,
                'rgba(100%, 0%, 0%, 0.1)');
            $this->drawText(5, $currentPos + 15, $text, 0);
        }
    }
}
