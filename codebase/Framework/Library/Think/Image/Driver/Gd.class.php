<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
// | ImageGd.class.php 2013-03-05
// +----------------------------------------------------------------------
namespace Think\Image\Driver;

use Think\Image;

class Gd
{
    /**
     * Image resource object
     * @var resource
     */
    private $img;

    /**
     * Image information, includingwidth,height,type,mime,size
     * @var array
     */
    private $info;

    /**
     * structuremethod,AvailableinOpen an image
     * @param string $imgname Image path
     */
    public function __construct($imgname = null)
    {
        $imgname && $this->open($imgname);
    }

    /**
     * Open an image
     * @param  string $imgname Image path
     */
    public function open($imgname)
    {
        //Detectimagefile
        if (!is_file($imgname)) E('There is no image file');

        //Obtainimageinformation
        $info = getimagesize($imgname);

        //Detectimagelegitimate性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            E('Illegal image files');
        }

        //Set upimageinformation
        $this->info = array(
            'width' => $info[0],
            'height' => $info[1],
            'type' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
        );

        //destroyAlreadyexistImage
        empty($this->img) || imagedestroy($this->img);

        //turn onimage
        if ('gif' == $this->info['type']) {
            $class = 'Think\\Image\\Driver\\GIF';
            $this->gif = new $class($imgname);
            $this->img = imagecreatefromstring($this->gif->image());
        } else {
            $fun = "imagecreatefrom{$this->info['type']}";
            $this->img = $fun($imgname);
        }
    }

    /**
     * Save image
     * @param  string $imgname Save the image name
     * @param  string $type Image Type
     * @param  integer $quality Image Quality
     * @param  boolean $interlace WhetherJPEGTypes ofInterlaced image settings
     */
    public function save($imgname, $type = null, $quality = 80, $interlace = true)
    {
        if (empty($this->img)) E('No image resources can be saved');

        //automaticObtainImage Type
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        //Save image
        if ('jpeg' == $type || 'jpg' == $type) {
            //JPEGInterlaced image settings
            imageinterlace($this->img, $interlace);
            imagejpeg($this->img, $imgname, $quality);
        } elseif ('gif' == $type && !empty($this->gif)) {
            $this->gif->save($imgname);
        } else {
            $fun = 'image' . $type;
            $fun($this->img, $imgname);
        }
    }

    /**
     * Back image width
     * @return integer imagewidth
     */
    public function width()
    {
        if (empty($this->img)) E('Do not specify an image resource');
        return $this->info['width'];
    }

    /**
     * Returns the image height
     * @return integer Image height
     */
    public function height()
    {
        if (empty($this->img)) E('Do not specify an image resource');
        return $this->info['height'];
    }

    /**
     * Returns the image type
     * @return string Image Type
     */
    public function type()
    {
        if (empty($this->img)) E('Do not specify an image resource');
        return $this->info['type'];
    }

    /**
     * Returns the imageMIMETypes of
     * @return string imageMIMETypes of
     */
    public function mime()
    {
        if (empty($this->img)) E('Do not specify an image resource');
        return $this->info['mime'];
    }

    /**
     * Returns an array of image size 0 - imagewidth,1 - Image height
     * @return array Image size
     */
    public function size()
    {
        if (empty($this->img)) E('Do not specify an image resource');
        return array($this->info['width'], $this->info['height']);
    }

    /**
     * Cropped image
     * @param  integer $w Crop area width
     * @param  integer $h Crop Area Height
     * @param  integer $x Crop areaxcoordinate
     * @param  integer $y Crop areaycoordinate
     * @param  integer $width Save the image width
     * @param  integer $height Save the image height
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if (empty($this->img)) E('No image can be cropped resources');

        //Set upStoragesize
        empty($width) && $width = $w;
        empty($height) && $height = $h;

        do {
            //createnewimage
            $img = imagecreatetruecolor($width, $height);
            // Adjustmentdefaultcolour
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);

            //Cut out
            imagecopyresampled($img, $this->img, 0, 0, $x, $y, $width, $height, $w, $h);
            imagedestroy($this->img); //destroyArtwork

            //Set upnewimage
            $this->img = $img;
        } while (!empty($this->gif) && $this->gifNext());

        $this->info['width'] = $width;
        $this->info['height'] = $height;
    }

    /**
     * Generate thumbnails
     * @param  integer $width The maximum width of the thumbnail
     * @param  integer $height The maximum height of the thumbnail
     * @param  integer $type Thumbnail crop type
     */
    public function thumb($width, $height, $type = Image::IMAGE_THUMB_SCALE)
    {
        if (empty($this->img)) E('No image resources can be abbreviated');

        //Artworkwidthwithheight
        $w = $this->info['width'];
        $h = $this->info['height'];

        /* ComputeThumbnailsFormofnecessaryparameter */
        switch ($type) {
            /* Scaling */
            case Image::IMAGE_THUMB_SCALE:
                //ArtworkSize is less thanThumbnail sizeNot be abbreviated
                if ($w < $width && $h < $height) return;

                //Compute缩putproportion
                $scale = min($width / $w, $height / $h);

                //Set upThumbnailcoordinateandwidthwithheight
                $x = $y = 0;
                $width = $w * $scale;
                $height = $h * $scale;
                break;

            /* Center crop */
            case Image::IMAGE_THUMB_CENTER:
                //Compute put proportion
                $scale = max($width / $w, $height / $h);

                //Set upThumbnailcoordinateandwidthwithheight
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            /* Crop the upper left corner */
            case Image::IMAGE_THUMB_NORTHWEST:
                //Compute put proportion
                $scale = max($width / $w, $height / $h);

                //Set upThumbnailcoordinateandwidthwithheight
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;

            /* The lower right corner cutting */
            case Image::IMAGE_THUMB_SOUTHEAST:
                //Compute缩putproportion
                $scale = max($width / $w, $height / $h);

                //Set upThumbnailcoordinateandwidthwithheight
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;

            /* filling */
            case Image::IMAGE_THUMB_FILLED:
                //Compute put proportion
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }

                //Set up Thumbnail coordinate and width with height
                $neww = $w * $scale;
                $newh = $h * $scale;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;

                do {
                    //createnewimage
                    $img = imagecreatetruecolor($width, $height);
                    // Adjustmentdefaultcolour
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);

                    //Cut out
                    imagecopyresampled($img, $this->img, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->img); //destroyArtwork
                    $this->img = $img;
                } while (!empty($this->gif) && $this->gifNext());

                $this->info['width'] = $width;
                $this->info['height'] = $height;
                return;

            /* fixed */
            case Image::IMAGE_THUMB_FIXED:
                $x = $y = 0;
                break;

            default:
                E('Does not support thumbnail crop type');
        }

        /* Cropped image */
        $this->crop($w, $h, $x, $y, $width, $height);
    }

    /**
     * Add a watermark
     * @param  string $source Watermark image path
     * @param  integer $locate Watermark position
     * @param  integer $alpha Watermark transparency
     */
    public function water($source, $locate = Image::IMAGE_WATER_SOUTHEAST, $alpha = 80)
    {
        //ResourcesDetect
        if (empty($this->img)) E('It can not be added watermark image resources');
        if (!is_file($source)) E('Watermark image does not exist');

        //ObtainWatermarkimageinformation
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            E('Illegal file watermark');
        }

        //createWatermarkimageResources
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);

        //set upWatermarkimageof混colormode
        imagealphablending($water, true);

        /* Set watermark position */
        switch ($locate) {
            /* The lower right corner Watermark */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;

            /* The lower left corner Watermark */
            case Image::IMAGE_WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;

            /* The upper left corner Watermark */
            case Image::IMAGE_WATER_NORTHWEST:
                $x = $y = 0;
                break;

            /* The upper right corner Watermark */
            case Image::IMAGE_WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;

            /* Watermark center */
            case Image::IMAGE_WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* Centering watermark */
            case Image::IMAGE_WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;

            /* Right Center watermark */
            case Image::IMAGE_WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* Centered on the watermark */
            case Image::IMAGE_WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;

            /* Left center Watermark */
            case Image::IMAGE_WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            default:
                /* Custom Watermark coordinates */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    E('It does not support the type of watermark position');
                }
        }

        do {
            //Add a watermark
            $src = imagecreatetruecolor($info[0], $info[1]);
            // Adjustmentdefaultcolour
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);

            imagecopy($src, $this->img, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->img, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);

            //destroyZeroImage Resources
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());

        //destroyWatermarkResources
        imagedestroy($water);
    }

    /**
     * Add text image
     * @param  string $text Adding text
     * @param  string $font Font path
     * @param  integer $size Font size
     * @param  string $color Writingcolour
     * @param  integer $locate Text writing position
     * @param  integer $offset WritingrelativelycurrentpositionofOffset
     * @param  integer $angle Text tilt angle
     */
    public function text($text, $font, $size, $color = '#00000000',
                         $locate = Image::IMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        //ResourcesDetect
        if (empty($this->img)) E('No image resources can be written text');
        if (!is_file($font)) E("does not existofFontsfile：{$font}");

        //ObtainWritinginformation
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);

        /* ComputeThe initial textcoordinateAnd size */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;

        /* Set text position */
        switch ($locate) {
            /* Lower right corner of the text */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x += $this->info['width'] - $w;
                $y += $this->info['height'] - $h;
                break;

            /* The lower left corner character */
            case Image::IMAGE_WATER_SOUTHWEST:
                $y += $this->info['height'] - $h;
                break;

            /* The upper left corner character */
            case Image::IMAGE_WATER_NORTHWEST:
                // Startingcoordinatewhich isforThe upper left cornercoordinate,No needAdjustment
                break;

            /* The upper-right corner character */
            case Image::IMAGE_WATER_NORTHEAST:
                $x += $this->info['width'] - $w;
                break;

            /* Centered text */
            case Image::IMAGE_WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* Centering text */
            case Image::IMAGE_WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;

            /* Right Center text */
            case Image::IMAGE_WATER_EAST:
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* The text is centered */
            case Image::IMAGE_WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                break;

            /* Left centered text */
            case Image::IMAGE_WATER_WEST:
                $y += ($this->info['height'] - $h) / 2;
                break;

            default:
                /* Custom text coordinates */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    E('The text does not support the type of position');
                }
        }

        /* Set the offset */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        /* Set Color */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            E('Wrong color values');
        }

        do {
            /* Written text */
            $col = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->img, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
    }

    /* Switch toGIFofAnd the next frameStoragecurrentframe,internaluse */
    private function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->img);
        $img = ob_get_clean();

        $this->gif->image($img);
        $next = $this->gif->nextImage();

        if ($next) {
            $this->img = imagecreatefromstring($next);
            return $next;
        } else {
            $this->img = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    /**
     * Destructor,FordestroyimageResources
     */
    public function __destruct()
    {
        empty($this->img) || imagedestroy($this->img);
    }
}