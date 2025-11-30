<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Think;

class Verify
{
    protected $config = array(
        'seKey' => 'codonoinc',   // PIN encryption keys
        'codeSet' => '2345678ABCDEFGHJKLMNPRTUVWXY',             // Code set of characters
        'expire' => 1800,            // Code expiration time (s)
        'useZh' => false,           // Use Code Chinese
        'zhSet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',            // ChineseVerification codeString
        'useImgBg' => false,           // Use background picture
        'fontSize' => 25,              // Code font size(px)
        'useCurve' => true,            // Whether painting confuse curve
        'useNoise' => true,            // Are Add Noise
        'imageH' => 0,               // CAPTCHA image height
        'imageW' => 0,               // CAPTCHA image width
        'length' => 5,               // Code-digit
        'fontttf' => '',              // Verification codeFonts,Set uprandomObtain
        'bg' => array(19, 23, 34),  // background color
        'reset' => true,           // verificationsuccessRearwhetherReset
    );

    private $_image = NULL;     // Image verification code examples
    private $_color = NULL;     // Font color code

    /**
     * Architectural approach Setting parameters
     * @access public
     * @param  array $config Configuration parameters
     */
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * use $this->name Get Configuration
     * @access public
     * @param  string $name Configuration Name
     * @return multitype    Configuration values
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * Set upVerification codeConfiguration
     * @access public
     * @param  string $name Configuration Name
     * @param  string $value Configuration values
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * Check the configuration
     * @access public
     * @param  string $name Configuration Name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * verificationVerification codewhethercorrect
     * @access public
     * @param string $code User authentication code
     * @param string $id Identity verification code
     * @return bool User authentication code is correct
     */
    public function check($code, $id = '')
    {
        $key = $this->authcode($this->seKey) . $id;
        // verification code must be filled
        $secode = session($key);
        if (empty($code) || empty($secode)) {
            return false;
        }
        // session Expired
        if (NOW_TIME - $secode['verify_time'] > $this->expire) {
            session($key, null);
            return false;
        }

        if ($this->authcode(strtoupper($code)) == $secode['verify_code']) {
//            $this->reset && session($key, null);
            return true;
        }

        return false;
    }

    /**
     * ExportVerification codeAnd theVerification codeThe valueStorageofsessionin
     * Save this code tosessionThe format is: array('verify_code' => 'Verification code value', 'verify_time' => 'Code Created');
     * @access public
     * @param string $id To generate the identification codes
     * @return void
     */
    public function entry($id = '')
    {
        // Image width (px)
        if (!$this->imageW) {
            $this->imageW = $this->length * $this->fontSize * 1.5 + $this->length * $this->fontSize / 2;
        }
        // Image height (px)
        if (!$this->imageH) {
            $this->imageH = $this->fontSize * 2.5;
        }
        // Establish a $this->imageW x $this->imageH Image
        $this->_image = imagecreate($this->imageW, $this->imageH);
        // Set Background      
        imagecolorallocate($this->_image, $this->bg[0], $this->bg[1], $this->bg[2]);

        // Verification codeFontsrandom color
        $this->_color = imagecolorallocate($this->_image, mt_rand(255, 255), mt_rand(255, 255), mt_rand(255, 255));
        // Verification codeuserandomFonts
        $ttfPath = dirname(__FILE__) . '/Verify/ttfs/';

        if (empty($this->fontttf)) {
            $dir = dir($ttfPath);
            $ttfs = array();
            while (false !== ($file = $dir->read())) {
                if ($file[0] != '.' && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $this->fontttf = $ttfs[array_rand($ttfs)];
        }
        $this->fontttf = $ttfPath . $this->fontttf;

        if ($this->useImgBg) {
            $this->_background();
        }

        if ($this->useNoise) {
            // Painted Noise
            $this->_writeNoise();
        }
        if ($this->useCurve) {
            // Painted line interference
            $this->_writeCurve();
        }

        // Painted verification code
        $code = array(); // Verification code
        $codeNX = 0; // The first codeNCharacters from the left
        if ($this->useZh) { // Chinese verification code
            for ($i = 0; $i < $this->length; $i++) {
                $code[$i] = mb_substr($this->zhSet, floor(mt_rand(0, mb_strlen($this->zhSet, 'utf-8') - 1)), 1, 'utf-8');
                imagettftext($this->_image, $this->fontSize, mt_rand(-40, 40), $this->fontSize * ($i + 1) * 1.5, $this->fontSize + mt_rand(10, 20), $this->_color, $this->fontttf, $code[$i]);
            }
        } else {
            for ($i = 0; $i < $this->length; $i++) {
                $code[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
                $codeNX += mt_rand($this->fontSize * 1.2, $this->fontSize * 1.6);
                imagettftext($this->_image, $this->fontSize, mt_rand(-40, 40), $codeNX, $this->fontSize * 1.6, $this->_color, $this->fontttf, $code[$i]);
            }
        }


        // StorageVerification code
        $key = $this->authcode($this->seKey);
        $code = $this->authcode(strtoupper(implode('', $code)));
        $secode = array();
        $secode['verify_code'] = $code; // The check code to savesession
        $secode['verify_time'] = NOW_TIME;  // Code Created
        session($key . $id, $secode);

        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header("content-type: image/png");

        // Output image
        imagepng($this->_image);
        imagedestroy($this->_image);
    }

    /**
     * Generate a random sine function curve line as interference.
     *
     * Sinusoidal function formula: y = Asin(ωx + φ) + b
     * Each constant in the formula influences the function image as follows:
     *   - A: Determines the peak (longitudinal tension and compression)
     *   - b: Determines the position or distance of the longitudinal movement in Y-axis (plus for upward, minus for downward)
     *   - φ: Determines the lateral movement from the positional relationship in X-axis (plus for rightward, minus for leftward)
     *   - ω: Determines the cycle (minimum positive period T = 2π/|ω|)
     *
     * @access private
     * @return void
     */
    private function _writeCurve()
    {
        $px = $py = 0;

        // Curved front portion
        $A = mt_rand(1, $this->imageH / 2);                  // amplitude
        $b = mt_rand(-$this->imageH / 4, $this->imageH / 4);   // YAxis offset
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4);   // XAxis offset
        $T = mt_rand($this->imageH, $this->imageW * 2);  // cycle
        $w = (2 * M_PI) / $T;

        $px1 = 0;  // Curvilinear abscissa starting position
        $px2 = mt_rand($this->imageW / 2, $this->imageW * 0.8);  // Curvilinear abscissa end position

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color);  // Here(while)Cycle than painting pixelsimagettftextwithimagestringuseFontsThe size of a draw(This is notwhileCycling) performance is much better
                    $i--;
                }
            }
        }

        // Curve portion
        $A = mt_rand(1, $this->imageH / 2);                  // amplitude
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4);   // XAxis offset
        $T = mt_rand($this->imageH, $this->imageW * 2);  // cycle
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageW;

        for ($px = $px1; $px <= $px2; $px++) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color);
                    $i--;
                }
            }
        }
    }

    /**
     * Noise painting
     * toimageWritten on different colorsofletterordigital
     */
    private function _writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //Noise Color
            $noiseColor = imagecolorallocate($this->_image, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // Painted Noise
                imagestring($this->_image, 5, mt_rand(-10, $this->imageW), mt_rand(-10, $this->imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * Draw the background image
     * 注：in caseVerification codeExportimagebigger,It will take moreofsystemResources
     */
    private function _background()
    {
        $path = dirname(__FILE__) . '/Verify/bgs/';
        $dir = dir($path);

        $bgs = array();
        while (false !== ($file = $dir->read())) {
            if ($file[0] != '.' && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();

        $gb = $bgs[array_rand($bgs)];

        list($width, $height) = @getimagesize($gb);
        // Resample
        $bgImage = @imagecreatefromjpeg($gb);
        @imagecopyresampled($this->_image, $bgImage, 0, 0, 0, 0, $this->imageW, $this->imageH, $width, $height);
        @imagedestroy($bgImage);
    }

    /* Encryption code */
    private function authcode($str)
    {
        $key = substr(md5($this->seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }

}
