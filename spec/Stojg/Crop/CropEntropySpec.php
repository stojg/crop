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

    function it_can_recieve_imagemagic_object_on_construct()
    {
        $this->beConstructedWith(new \Imagick('spec/Stojg/Crop/fixtures/not-a-image.txt'));
        $this->shouldHaveType('Stojg\Crop\CropEntropy');

    }

    function it_can_return_an_imagick_object()
    {
        $this->getImage()->shouldHaveType('Imagick');
    }

    function it_can_return_the_same_imagick_object_that_it_was_constructed_with()
    {
        $obj = new \Imagick('spec/Stojg/Crop/fixtures/not-a-image.txt');
        $this->beConstructedWith($obj);
        $this->getImage()->shouldHaveType('Imagick');
        $this->getImage()->shouldBeLike($obj);
    }

    function it_can_calculate_an_image_area()
    {
        $obj = new \Imagick('spec/Stojg/Crop/fixtures/cloud-01.jpg');
        $this->beConstructedWith($obj);
        $this->area()->shouldBeLike(1000 * 600);
    }

    function it_can_calculate_a_grayscale_entropy_value()
    {
        $obj = new \Imagick('spec/Stojg/Crop/fixtures/cloud-01.jpg');
        $this->beConstructedWith($obj);
        $this->getGrayScaleEntropy()->shouldBeFloat();
        $this->getGrayScaleEntropy()->shouldBeCloseTo(11.521439369834);
    }

    public function getMatchers()
    {
        return [
            'beCloseTo' => function ($subject, $key) {
                return (abs(($subject - $key) / $subject) < 0.0000000000001);
            },
        ];
    }
}
