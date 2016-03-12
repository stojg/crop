<?php

namespace Stojg\Crop;

class CropEntropy
{
    /**
     * @var \Imagick
     */
    protected $imagick = null;

    /**
     * CropEntropy constructor
     *
     * @param \Imagick|null $image
     */
    public function __construct(\Imagick $image = null)
    {
        if ($image !== null) {
            $this->imagick = $image;
        } else {
            $this->imagick = new \Imagick();
        }
    }

    /**
     * @return \Imagick
     */
    public function getImagick()
    {
        return $this->imagick;
    }
}
