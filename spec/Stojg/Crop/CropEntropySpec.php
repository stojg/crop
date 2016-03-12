<?php

namespace spec\Stojg\Crop;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CropEntropySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Stojg\Crop\CropEntropy');
    }

    function it_can_recieve_imagemagic_object_on_construct() {
        $this->beConstructedWith(new \Imagick('spec/Stojg/Crop/fixtures/not-a-image.txt'));
        $this->shouldHaveType('Stojg\Crop\CropEntropy');

    }

    function it_can_return_an_imagick_object() {
        $this->getImagick()->shouldHaveType('Imagick');
    }
}
