<?php

namespace stojg\crop;

/**
 * CropFace
 * 
 * This class will try to find the most interesting point in the image by trying to find a face and
 * center the crop on that
 *
 * @todo implement
 * @see https://github.com/mauricesvay/php-facedetection/blob/master/FaceDetector.php
 */
class CropFace extends CropEntropy
{
    const CLASSIFIER_FACE = '/classifier/haarcascade_frontalface_default.xml';
    const CLASSIFIER_PROFILE = '/classifier/haarcascade_profileface.xml';

    /**
     * imagePath original image path
     * 
     * @var mixed
     * @access protected
     */
    protected $imagePath;

    /**
     * safeZoneList
     * 
     * @var array
     * @access protected
     */
    protected $safeZoneList;

	/**
	 *
	 * @param string $imagePath
	 */
	public function __construct($imagePath)
    {
		$this->imagePath = $imagePath;
        parent::__construct($imagePath);
    }

    /**
     * getFaceList get faces positions and sizes
     *
     * @access protected
     * @return array
     */
    protected function getFaceList()
    {
        if (!function_exists('face_detect')) {
            $msg = 'PHP Facedetect extension must be installed.
                    See http://www.xarg.org/project/php-facedetect/ for more details';
            throw new \Exception($msg);
        }

        $faceList = $this->getFaceListFromClassifier(self::CLASSIFIER_FACE);

        $profileList = $this->getFaceListFromClassifier(self::CLASSIFIER_PROFILE);

        $faceList = array_merge($faceList, $profileList);

        return $faceList;
    }

    /**
     * getFaceListFromClassifier
     *
     * @param string $classifier
     * @access protected
     * @return array
     */
    protected function getFaceListFromClassifier($classifier)
    {
        $faceList = face_detect($this->imagePath, __DIR__ . $classifier);
        return $faceList;
    }

    /**
     * getSafeZoneList
     *
     * @param array $faceList
     * @access private
     * @return void
     */
    protected function getSafeZoneList()
    {
        if (!isset($this->safeZoneList)) {
            $faceList = $this->getFaceList();

            $safeZoneList = array();
            foreach ($faceList as $face) {
                $hw = ceil($face['w'] / 2);
                $hh = ceil($face['h'] / 2);
                $safeZone = array(
                    'left' => $face['x'] - $hw,
                    'right' => $face['x'] + $face['w'] + $hw,
                    'top' => $face['y'] - $hh,
                    'bottom' => $face['y'] + $face['h'] + $hh
                );

                $safeZoneList[] = $safeZone;
            }
            $this->safeZoneList = $safeZoneList;
        }

        return $this->safeZoneList;
    }
}
