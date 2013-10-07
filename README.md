# Crop

This is a small set of image croppers that I created for testing automated cropping. 

[![Build Status](https://secure.travis-ci.org/stojg/crop.png?branch=master)](http://travis-ci.org/stojg/crop)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/stojg/crop/badges/quality-score.png?s=3fb9e495961dba3ae96d96d7632236ef0227d3cb)](https://scrutinizer-ci.com/g/stojg/crop/)
[![Code Coverage](https://scrutinizer-ci.com/g/stojg/crop/badges/coverage.png?s=cbd5ef15ca6f4f886bbcd88d45eb79cc861368cc)](https://scrutinizer-ci.com/g/stojg/crop/)

## Requirements

 - PHP 5.3 with Imagick extension

## Description

This little project includes three functional image cropers:

### CropCenter

 This is the most basic of cropping techniques:

   1. Find the exact center of the image
   2. Trim any edges that is bigger than the targetWidth and targetHeight

### CropEntropy

This class finds the a position in the picture with the most "energy" in it. Energy (or entropy) in
images are defined by 'edginess' in the image. For example a image of the sky have low edginess and
an image of an anthill has very high edginess.

Energy is in this case calculated like this

  1. Take the image and turn it into black and white
  2. Run a edge filter so that we're left with only edges.
  3. Find a piece in the picture that has the highest entropy (i.e. most edges)
  4. Return coordinates that makes sure that this piece of the picture is not cropped 'away'

### CropBalanced

Crop balanced is a variant of CropEntropy where I tried to the cropping a bit more balanced.

  1. Dividing the image into four equally squares
  2. Find the most energetic point per square
  3. Finding the images weighted mean interest point for all squares

### CropFace

Crop face uses [PHP Facedetect Extension](http://www.xarg.org/project/php-facedetect/) (which uses OpenCV).

In details, the FaceCrop uses Entropy Crop but puts blocking "limits" on the faces.
If the program faces two limits, we let the entropy decide the best crop.


## Usage

	$center = new \stojg\crop\CropCenter($filepath);
	$croppedImage = $center->resizeAndCrop($width, $height);
	$croppedImage->writeimage('assets/thumbs/cropped-center.jpg');
