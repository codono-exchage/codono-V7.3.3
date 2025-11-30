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
// | ImageImagick.class.php 2013-03-06
// +----------------------------------------------------------------------
namespace Think\Image\Driver;

use Think\Image;

class Imagick
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

        //destroyAlreadyexistImage
        empty($this->img) || $this->img->destroy();

        //Loadingimage
        $this->img = new \Imagick(realpath($imgname));

        //Set upimageinformation
        $this->info = array(
            'width' => $this->img->getImageWidth(),
            'height' => $this->img->getImageHeight(),
            'type' => strtolower($this->img->getImageFormat()),
            'mime' => $this->img->getImageMimeType(),
        );
    }

    /**
     * Save image
     * @param  string $imgname Save the image name
     * @param  string $type Image Type
     * @param  integer $quality JPEGImage Quality
     * @param  boolean $interlace WhetherJPEGTypes ofInterlaced image settings
     */
    public function save($imgname, $type = null, $quality = 80, $interlace = true)
    {
        if (empty($this->img)) E('No image resources can be saved');

        //Set upPicture type
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
            $this->img->setImageFormat($type);
        }

        //JPEGInterlaced image settings
        if ('jpeg' == $type || 'jpg' == $type || 'png' == $type) {
            $this->img->setImageInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }

        // Set the image quality
        $this->img->setImageCompressionQuality($quality);

        //RemovalimageConfigurationinformation
        $this->img->stripImage();

        //Save image
        $imgname = realpath(dirname($imgname)) . '/' . basename($imgname); //Absolutely mandatorypath
        if ('gif' == $type) {
            $this->img->writeImages($imgname, true);
        } else {
            $this->img->writeImage($imgname);
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

        //Crop picture
        if ('gif' == $this->info['type']) {
            $img = $this->img->coalesceImages();
            $this->img->destroy(); //destroyArtwork

            //Cut each frame cycle
            do {
                $this->_crop($w, $h, $x, $y, $width, $height, $img);
            } while ($img->nextImage());

            //compressionimage
            $this->img = $img->deconstructImages();
            $img->destroy(); //destroyZeroimage
        } else {
            $this->_crop($w, $h, $x, $y, $width, $height);
        }
    }

    /* Crop picture,internaltransfer */
    private function _crop($w, $h, $x, $y, $width, $height, $img = null)
    {
        is_null($img) && $img = $this->img;

        //Cut out
        $info = $this->info;
        if ($x != 0 || $y != 0 || $w != $info['width'] || $h != $info['height']) {
            $img->cropImage($w, $h, $x, $y);
            $img->setImagePage($w, $h, 0, 0); //AdjustmentAnd canvasimageConsistency
        }

        //Adjustmentsize
        if ($w != $width || $h != $height) {
            $img->sampleImage($width, $height);
        }

        //Set upCachesize
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
                //Compute缩putproportion
                $scale = max($width / $w, $height / $h);

                //Set upThumbnailcoordinateandwidthwithheight
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            /* Crop the upper left corner */
            case Image::IMAGE_THUMB_NORTHWEST:
                //Compute缩putproportion
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
                //Compute缩putproportion
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }

                //Set upThumbnailcoordinateandwidthwithheight
                $neww = $w * $scale;
                $newh = $h * $scale;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;

                //createA newimage
                $newimg = new \Imagick();
                $newimg->newImage($width, $height, 'white', $this->info['type']);


                if ('gif' == $this->info['type']) {
                    $imgs = $this->img->coalesceImages();
                    $img = new \Imagick();
                    $this->img->destroy(); //destroyArtwork

                    //cyclefillingEach frame
                    do {
                        //fillingimage
                        $image = $this->_fill($newimg, $posx, $posy, $neww, $newh, $imgs);

                        $img->addImage($image);
                        $img->setImageDelay($imgs->getImageDelay());
                        $img->setImagePage($width, $height, 0, 0);

                        $image->destroy(); //destroyZeroimage

                    } while ($imgs->nextImage());

                    //compressionimage
                    $this->img->destroy();
                    $this->img = $img->deconstructImages();
                    $imgs->destroy(); //destroyZeroimage
                    $img->destroy(); //destroyZeroimage

                } else {
                    //fillingimage
                    $img = $this->_fill($newimg, $posx, $posy, $neww, $newh);
                    //destroyArtwork
                    $this->img->destroy();
                    $this->img = $img;
                }

                //Set upnewimageAttributes
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

    /* fillingDesignationimage,internaluse */
    private function _fill($newimg, $posx, $posy, $neww, $newh, $img = null)
    {
        is_null($img) && $img = $this->img;

        /* willDesignationimageDrawn into the blankimage */
        $draw = new \ImagickDraw();
        $draw->composite($img->getImageCompose(), $posx, $posy, $neww, $newh, $img);
        $image = $newimg->clone();
        $image->drawImage($draw);
        $draw->destroy();

        return $image;
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

        //createWatermarkimageResources
        $water = new \Imagick(realpath($source));
        $info = array($water->getImageWidth(), $water->getImageHeight());

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

        //createDrawResources
        $draw = new \ImagickDraw();
        $draw->composite($water->getImageCompose(), $x, $y, $info[0], $info[1], $water);

        if ('gif' == $this->info['type']) {
            $img = $this->img->coalesceImages();
            $this->img->destroy(); //destroyArtwork

            do {
                //Add a watermark
                $img->drawImage($draw);
            } while ($img->nextImage());

            //compressionimage
            $this->img = $img->deconstructImages();
            $img->destroy(); //destroyZeroimage

        } else {
            //Add a watermark
            $this->img->drawImage($draw);
        }

        //destroyWatermarkResources
        $draw->destroy();
        $water->destroy();
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

        //ObtainColor and transparency
        if (is_array($color)) {
            $color = array_map('dechex', $color);
            foreach ($color as &$value) {
                $value = str_pad($value, 2, '0', STR_PAD_LEFT);
            }
            $color = '#' . implode('', $color);
        } elseif (!is_string($color) || 0 !== strpos($color, '#')) {
            E('Wrong color values');
        }
        $col = substr($color, 0, 7);
        $alp = strlen($color) == 9 ? substr($color, -2) : 0;


        //ObtainWritinginformation
        $draw = new \ImagickDraw();
        $draw->setFont(realpath($font));
        $draw->setFontSize($size);
        $draw->setFillColor($col);
        $draw->setFillAlpha(1 - hexdec($alp) / 127);
        $draw->setTextAntialias(true);
        $draw->setStrokeAntialias(true);

        $metrics = $this->img->queryFontMetrics($draw, $text);

        /* ComputeThe initial textcoordinateAnd size */
        $x = 0;
        $y = $metrics['ascender'];
        $w = $metrics['textWidth'];
        $h = $metrics['textHeight'];

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

        /* Written text */
        if ('gif' == $this->info['type']) {
            $img = $this->img->coalesceImages();
            $this->img->destroy(); //destroyArtwork
            do {
                $img->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
            } while ($img->nextImage());

            //compressionimage
            $this->img = $img->deconstructImages();
            $img->destroy(); //destroyZeroimage

        } else {
            $this->img->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
        }
        $draw->destroy();
    }

    /**
     * Destructor,FordestroyimageResources
     */
    public function __destruct()
    {
        empty($this->img) || $this->img->destroy();
    }
}