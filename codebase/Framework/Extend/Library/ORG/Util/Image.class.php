<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * imageoperatingClass Library
 * @category   ORG
 * @package  ORG
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 */
class Image
{

    /**
     * Acquires image information
     * @static
     * @access public
     * @param string $image imagefilename
     * @return mixed
     */

    static function getImageInfo($img)
    {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            $imageSize = filesize($img);
            $info = array(
                "width" => $imageInfo[0],
                "height" => $imageInfo[1],
                "type" => $imageType,
                "size" => $imageSize,
                "mime" => $imageInfo['mime']
            );
            return $info;
        } else {
            return false;
        }
    }

    /**
     * Adding watermarks to images
     * @static public
     * @param string $source originalfilename
     * @param string $water Watermark Pictures
     * @param string $$savename  Add a watermarkAfterimage Name
     * @param string $alpha Transparency watermarks
     * @return void
     */
    static public function water($source, $water, $savename = null, $alpha = 80)
    {
        //Check if the file exists
        if (!file_exists($source) || !file_exists($water))
            return false;

        //image information
        $sInfo = self::getImageInfo($source);
        $wInfo = self::getImageInfo($water);

        //in case dimensions are less than watermark image
        if ($sInfo["width"] < $wInfo["width"] || $sInfo['height'] < $wInfo['height'])
            return false;

        //Create an image
        $sCreateFun = "imagecreatefrom" . $sInfo['type'];
        $sImage = $sCreateFun($source);
        $wCreateFun = "imagecreatefrom" . $wInfo['type'];
        $wImage = $wCreateFun($water);

        //Image blending mode setting
        imagealphablending($wImage, true);

        //Image position,The default isLower right cornerAlign Right
        $posY = $sInfo["height"] - $wInfo["height"];
        $posX = $sInfo["width"] - $wInfo["width"];

        //Generating a combined image
        imagecopymerge($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);

        //Output image
        $ImageFun = 'Image' . $sInfo['type'];
        //in caseNoGivensave documentname,The default isoriginalimage Name
        if (!$savename) {
            $savename = $source;
            @unlink($source);
        }
        //Save image
        $ImageFun($sImage, $savename);
        imagedestroy($sImage);
    }

    function showImg($imgFile, $text = '', $x = '10', $y = '10', $alpha = '50')
    {
        //Acquires the image file information
        //2007/6/26 increaseimageWatermarkExport,$textforimageofcompletepathTo
        $info = Image::getImageInfo($imgFile);
        if ($info !== false) {
            $createFun = str_replace('/', 'createfrom', $info['mime']);
            $im = $createFun($imgFile);
            if ($im) {
                $ImageFun = str_replace('/', '', $info['mime']);
                //Watermark start
                if (!empty($text)) {
                    $tc = imagecolorallocate($im, 0, 0, 0);
                    if (is_file($text) && file_exists($text)) {//judgment$textWhether the image path
                        // Obtain watermark information
                        $textInfo = Image::getImageInfo($text);
                        $createFun2 = str_replace('/', 'createfrom', $textInfo['mime']);
                        $waterMark = $createFun2($text);
                        //$waterMark=imagecolorallocatealpha($text,255,255,0,50);
                        $imgW = $info["width"];
                        $imgH = $info["width"] * $textInfo["height"] / $textInfo["width"];
                        //$y	=	($info["height"]-$textInfo["height"])/2;
                        //Set upWatermarkofdisplayPosition and transparencystand byAll kindsimageformat
                        imagecopymerge($im, $waterMark, $x, $y, 0, 0, $textInfo['width'], $textInfo['height'], $alpha);
                    } else {
                        imagestring($im, 80, $x, $y, $text, $tc);
                    }
                    //ImageDestroy($tc);
                }
                //Watermark end
                if ($info['type'] == 'png' || $info['type'] == 'gif') {
                    imagealphablending($im, FALSE); //canceldefaultof混colormode
                    imagesavealpha($im, TRUE); //set upStoragecompleteof alpha aisleinformation
                }
                Header("Content-type: " . $info['mime']);
                $ImageFun($im);
                @ImageDestroy($im);
                return;
            }

            //Save image
            $ImageFun($sImage, $savename);
            imagedestroy($sImage);
            //ObtainorcreateimagefilefailurethenFormblankPNGimage
            $im = imagecreatetruecolor(80, 30);
            $bgc = imagecolorallocate($im, 255, 255, 255);
            $tc = imagecolorallocate($im, 0, 0, 0);
            imagefilledrectangle($im, 0, 0, 150, 30, $bgc);
            imagestring($im, 4, 5, 5, "no pic", $tc);
            Image::output($im);
            return;
        }
    }

    /**
     * Generate thumbnails
     * @static
     * @access public
     * @param string $image Artwork
     * @param string $type Image Format
     * @param string $thumbname Thumbnailsfilename
     * @param string $maxWidth width
     * @param string $maxHeight height
     * @param string $position Thumbnails save directory
     * @param boolean $interlace Enable interlaced
     * @return void
     */
    static function thumb($image, $thumbname, $type = '', $maxWidth = 200, $maxHeight = 50, $interlace = true)
    {
        // ObtainArtworkinformation
        $info = Image::getImageInfo($image);
        if ($info !== false) {
            $srcWidth = $info['width'];
            $srcHeight = $info['height'];
            $type = empty($type) ? $info['type'] : $type;
            $type = strtolower($type);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight); // Compute缩putproportion
            if ($scale >= 1) {
                // exceedArtworkSize is no longer an abbreviated
                $width = $srcWidth;
                $height = $srcHeight;
            } else {
                // Thumbnail size
                $width = (int)($srcWidth * $scale);
                $height = (int)($srcHeight * $scale);
            }

            // LoadingArtwork
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            if (!function_exists($createFun)) {
                return false;
            }
            $srcImg = $createFun($image);

            //createThumbnails
            if ($type != 'gif' && function_exists('imagecreatetruecolor'))
                $thumbImg = imagecreatetruecolor($width, $height);
            else
                $thumbImg = imagecreate($width, $height);
            //pngwithgifTransparent process by luofei614
            if ('png' == $type) {
                imagealphablending($thumbImg, false);//canceldefaultof混colormode(forsolveshadowforgreenofproblem)
                imagesavealpha($thumbImg, true);//set upStoragecompleteof alpha aisleinformation(forsolveshadowforgreenofproblem)    
            } elseif ('gif' == $type) {
                $trnprt_indx = imagecolortransparent($srcImg);
                if ($trnprt_indx >= 0) {
                    //its transparent
                    $trnprt_color = imagecolorsforindex($srcImg, $trnprt_indx);
                    $trnprt_indx = imagecolorallocate($thumbImg, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                    imagefill($thumbImg, 0, 0, $trnprt_indx);
                    imagecolortransparent($thumbImg, $trnprt_indx);
                }
            }
            // copyimage
            if (function_exists("ImageCopyResampled"))
                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            else
                imagecopyresized($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);

            // CorrectjpegGraphSet upInterlaced
            if ('jpg' == $type || 'jpeg' == $type)
                imageinterlace($thumbImg, $interlace);

            // Formimage
            $imageFun = 'image' . ($type == 'jpg' ? 'jpeg' : $type);
            $imageFun($thumbImg, $thumbname);
            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            return $thumbname;
        }
        return false;
    }

    /**
     * FormSpecific size thumbnails solveOriginal editionThumbnailsCan not meet certain sizeofproblem PS：Will cutimageincompatibleThumbnailsproportionofsection
     * @static
     * @access public
     * @param string $image Artwork
     * @param string $type Image Format
     * @param string $thumbname Thumbnailsfilename
     * @param string $maxWidth width
     * @param string $maxHeight height
     * @param boolean $interlace Enable interlaced
     * @return void
     */
    static function thumb2($image, $thumbname, $type = '', $maxWidth = 200, $maxHeight = 50, $interlace = true)
    {
        // ObtainArtworkinformation
        $info = Image::getImageInfo($image);
        if ($info !== false) {
            $srcWidth = $info['width'];
            $srcHeight = $info['height'];
            $type = empty($type) ? $info['type'] : $type;
            $type = strtolower($type);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            $scale = max($maxWidth / $srcWidth, $maxHeight / $srcHeight); // Compute缩putproportion
            //judgmentArtworkwithThumbnailsproportion Such asArtworkWider thanThumbnailsThe cut sides on the contrary..
            if ($maxWidth / $srcWidth > $maxHeight / $srcHeight) {
                //Higher than
                $srcX = 0;
                $srcY = ($srcHeight - $maxHeight / $scale) / 2;
                $cutWidth = $srcWidth;
                $cutHeight = $maxHeight / $scale;
            } else {
                //Wider than
                $srcX = ($srcWidth - $maxWidth / $scale) / 2;
                $srcY = 0;
                $cutWidth = $maxWidth / $scale;
                $cutHeight = $srcHeight;
            }

            // LoadingArtwork
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $srcImg = $createFun($image);

            //createThumbnails
            if ($type != 'gif' && function_exists('imagecreatetruecolor'))
                $thumbImg = imagecreatetruecolor($maxWidth, $maxHeight);
            else
                $thumbImg = imagecreate($maxWidth, $maxHeight);

            // copyimage
            if (function_exists("ImageCopyResampled"))
                imagecopyresampled($thumbImg, $srcImg, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
            else
                imagecopyresized($thumbImg, $srcImg, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
            if ('gif' == $type || 'png' == $type) {
                //imagealphablending($thumbImg, false);//canceldefaultof混colormode
                //imagesavealpha($thumbImg,true);//set upStoragecompleteof alpha aisleinformation
                $background_color = imagecolorallocate($thumbImg, 0, 255, 0);  //  Assigned a green
                imagecolortransparent($thumbImg, $background_color);  //  Set asTransparent,If theNoteThe line is outExportgreenofMap
            }

            // CorrectjpegGraphSet upInterlaced
            if ('jpg' == $type || 'jpeg' == $type)
                imageinterlace($thumbImg, $interlace);

            // Formimage
            $imageFun = 'image' . ($type == 'jpg' ? 'jpeg' : $type);
            $imageFun($thumbImg, $thumbname);
            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            return $thumbname;
        }
        return false;
    }

    /**
     * according togivenofStringFormimage
     * @static
     * @access public
     * @param string $string String
     * @param string $size Image size width,height or array(width,height)
     * @param string $font Font information fontface,fontsize or array(fontface,fontsize)
     * @param string $type Image Format defaultPNG
     * @param integer $disturb Whether interference 1 Dot interference 2 Line interference 3 Composite interference 0 Without interference
     * @param bool $border Are add borders array(color)
     * @return string
     */
    static function buildString($string, $rgb = array(), $filename = '', $type = 'png', $disturb = 1, $border = true)
    {
        if (is_string($size))
            $size = explode(',', $size);
        $width = $size[0];
        $height = $size[1];
        if (is_string($font))
            $font = explode(',', $font);
        $fontface = $font[0];
        $fontsize = $font[1];
        $length = strlen($string);
        $width = ($length * 9 + 10) > $width ? $length * 9 + 10 : $width;
        $height = 22;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = @imagecreatetruecolor($width, $height);
        } else {
            $im = @imagecreate($width, $height);
        }
        if (empty($rgb)) {
            $color = imagecolorallocate($im, 102, 104, 104);
        } else {
            $color = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        }
        $backColor = imagecolorallocate($im, 255, 255, 255);    //Background Color(random)
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //framecolor
        $pointColor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));                 //Some color

        @imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        @imagestring($im, 5, 5, 3, $string, $color);
        if (!empty($disturb)) {
            // Add interference
            if ($disturb = 1 || $disturb = 3) {
                for ($i = 0; $i < 25; $i++) {
                    imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pointColor);
                }
            } elseif ($disturb = 2 || $disturb = 3) {
                for ($i = 0; $i < 10; $i++) {
                    imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $pointColor);
                }
            }
        }
        Image::output($im, $type, $filename);
    }

    /**
     * FormimageVerification code
     * @static
     * @access public
     * @param string $length Digit
     * @param string $mode Types of
     * @param string $type Image Format
     * @param string $width width
     * @param string $height height
     * @return string
     */
    static function buildImageVerify($length = 4, $mode = 1, $type = 'png', $width = 48, $height = 22, $verifyName = 'verify')
    {
        import('ORG.Util.Stringer');
        $randval = Stringer::randString($length, $mode);
        session($verifyName, md5($randval));
        $width = ($length * 10 + 10) > $width ? $length * 10 + 10 : $width;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($width, $height);
        } else {
            $im = imagecreate($width, $height);
        }
        $r = Array(225, 255, 255, 223);
        $g = Array(225, 236, 237, 255);
        $b = Array(225, 236, 166, 125);
        $key = mt_rand(0, 3);

        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]);    //Background Color(random)
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //framecolor
        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // interference
        for ($i = 0; $i < 10; $i++) {
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $stringColor);
        }
        for ($i = 0; $i < 25; $i++) {
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $stringColor);
        }
        for ($i = 0; $i < $length; $i++) {
            imagestring($im, 5, $i * 10 + 5, mt_rand(1, 8), $randval[$i], $stringColor);
        }
        Image::output($im, $type);
    }

    // ChineseVerification code
    static function GBVerify($length = 4, $type = 'png', $width = 180, $height = 50, $fontface = 'simhei.ttf', $verifyName = 'verify')
    {
        import('ORG.Util.Stringer');
        $code = Stringer::randString($length, 4);
        $width = ($length * 45) > $width ? $length * 45 : $width;
        session($verifyName, md5($code));
        $im = imagecreatetruecolor($width, $height);
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //framecolor
        $bkcolor = imagecolorallocate($im, 250, 250, 250);
        imagefill($im, 0, 0, $bkcolor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        // interference
        for ($i = 0; $i < 15; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $fontcolor);
        }
        for ($i = 0; $i < 255; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $fontcolor);
        }
        if (!is_file($fontface)) {
            $fontface = dirname(__FILE__) . "/" . $fontface;
        }
        for ($i = 0; $i < $length; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120)); //suchEnsure random outofDarker。
            $codex = StringUtil::msubstr($code, $i, 1);
            imagettftext($im, mt_rand(16, 20), mt_rand(-60, 60), 40 * $i + 20, mt_rand(30, 35), $fontcolor, $fontface, $codex);
        }
        Image::output($im, $type);
    }

    /**
     * TheimageChangeto makecharacterdisplay
     * @static
     * @access public
     * @param string $image The image to be displayed
     * @param string $type Image Type,defaultautomaticObtain
     * @return string
     */
    static function showASCIIImg($image, $string = '', $type = '')
    {
        $info = Image::getImageInfo($image);
        if ($info !== false) {
            $type = empty($type) ? $info['type'] : $type;
            unset($info);
            // LoadingArtwork
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $im = $createFun($image);
            $dx = imagesx($im);
            $dy = imagesy($im);
            $i = 0;
            $out = '<span style="padding:0px;margin:0;line-height:100%;font-size:1px;">';
            set_time_limit(0);
            for ($y = 0; $y < $dy; $y++) {
                for ($x = 0; $x < $dx; $x++) {
                    $col = imagecolorat($im, $x, $y);
                    $rgb = imagecolorsforindex($im, $col);
                    $str = empty($string) ? '*' : $string[$i++];
                    $out .= sprintf('<span style="margin:0px;color:#%02x%02x%02x">' . $str . '</span>', $rgb['red'], $rgb['green'], $rgb['blue']);
                }
                $out .= "<br>\n";
            }
            $out .= '</span>';
            imagedestroy($im);
            return $out;
        }
        return false;
    }

    /**
     * FormUPC-ABarcode
     * @static
     * @param string $type Image Format
     * @param string $type Image Format
     * @param string $lw Cell width
     * @param string $hi Barcode height
     * @return string
     */
    static function UPCA($code, $type = 'png', $lw = 2, $hi = 100)
    {
        static $Lencode = array('0001101', '0011001', '0010011', '0111101', '0100011',
            '0110001', '0101111', '0111011', '0110111', '0001011');
        static $Rencode = array('1110010', '1100110', '1101100', '1000010', '1011100',
            '1001110', '1010000', '1000100', '1001000', '1110100');
        $ends = '101';
        $center = '01010';
        /* UPC-A Must be 11 digits, we compute the checksum. */
        if (strlen($code) != 11) {
            die("UPC-A Must be 11 digits.");
        }
        /* Compute the EAN-13 Checksum digit */
        $ncode = '0' . $code;
        $even = 0;
        $odd = 0;
        for ($x = 0; $x < 12; $x++) {
            if ($x % 2) {
                $odd += $ncode[$x];
            } else {
                $even += $ncode[$x];
            }
        }
        $code .= (10 - (($odd * 3 + $even) % 10)) % 10;
        /* Create the bar encoding using a binary string */
        $bars = $ends;
        $bars .= $Lencode[$code[0]];
        for ($x = 1; $x < 6; $x++) {
            $bars .= $Lencode[$code[$x]];
        }
        $bars .= $center;
        for ($x = 6; $x < 12; $x++) {
            $bars .= $Rencode[$code[$x]];
        }
        $bars .= $ends;
        /* Generate the Barcode Image */
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($lw * 95 + 30, $hi + 30);
        } else {
            $im = imagecreate($lw * 95 + 30, $hi + 30);
        }
        $fg = ImageColorAllocate($im, 0, 0, 0);
        $bg = ImageColorAllocate($im, 255, 255, 255);
        ImageFilledRectangle($im, 0, 0, $lw * 95 + 30, $hi + 30, $bg);
        $shift = 10;
        for ($x = 0; $x < strlen($bars); $x++) {
            if (($x < 10) || ($x >= 45 && $x < 50) || ($x >= 85)) {
                $sh = 10;
            } else {
                $sh = 0;
            }
            if ($bars[$x] == '1') {
                $color = $fg;
            } else {
                $color = $bg;
            }
            ImageFilledRectangle($im, ($x * $lw) + 15, 5, ($x + 1) * $lw + 14, $hi + 5 + $sh, $color);
        }
        /* Add the Human Readable Label */
        ImageString($im, 4, 5, $hi - 5, $code[0], $fg);
        for ($x = 0; $x < 5; $x++) {
            ImageString($im, 5, $lw * (13 + $x * 6) + 15, $hi + 5, $code[$x + 1], $fg);
            ImageString($im, 5, $lw * (53 + $x * 6) + 15, $hi + 5, $code[$x + 6], $fg);
        }
        ImageString($im, 4, $lw * 95 + 17, $hi - 5, $code[11], $fg);
        /* Output the Header and Content. */
        Image::output($im, $type);
    }

    static function output($im, $type = 'png', $filename = '')
    {
        header("Content-type: image/" . $type);
        $ImageFun = 'image' . $type;
        if (empty($filename)) {
            $ImageFun($im);
        } else {
            $ImageFun($im, $filename);
        }
        imagedestroy($im);
    }

}
