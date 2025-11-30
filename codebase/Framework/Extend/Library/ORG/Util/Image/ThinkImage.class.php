<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
// | ThinkImage.class.php 2013-03-05
// +----------------------------------------------------------------------

/* Drive related constants defined */
define('THINKIMAGE_GD', 1); //constant,MarkGDStorehouseTypes of
define('THINKIMAGE_IMAGICK', 2); //constant,MarkimagickStorehouseTypes of

/* ThumbnailsRelatedconstantdefinition */
define('THINKIMAGE_THUMB_SCALING', 1); //constant,MarkThumbnailsScalingTypes of
define('THINKIMAGE_THUMB_FILLED', 2); //constant,MarkThumbnailsAfter scalingfillingTypes of
define('THINKIMAGE_THUMB_CENTER', 3); //constant,MarkThumbnailsCenter cropTypes of
define('THINKIMAGE_THUMB_NORTHWEST', 4); //constant,MarkThumbnailsCrop the upper left cornerTypes of
define('THINKIMAGE_THUMB_SOUTHEAST', 5); //constant,MarkThumbnailsThe lower right corner cuttingTypes of
define('THINKIMAGE_THUMB_FIXED', 6); //constant,MarkThumbnailsfixedSize MagnificationTypes of

/* Watermark related constants defined */
define('THINKIMAGE_WATER_NORTHWEST', 1); //constant,MarkThe upper left corner Watermark
define('THINKIMAGE_WATER_NORTH', 2); //constant,MarkCentered on the watermark
define('THINKIMAGE_WATER_NORTHEAST', 3); //constant,MarkThe upper right corner Watermark
define('THINKIMAGE_WATER_WEST', 4); //constant,MarkLeft center Watermark
define('THINKIMAGE_WATER_CENTER', 5); //constant,MarkWatermark center
define('THINKIMAGE_WATER_EAST', 6); //constant,MarkRight Center watermark
define('THINKIMAGE_WATER_SOUTHWEST', 7); //constant,MarkThe lower left corner Watermark
define('THINKIMAGE_WATER_SOUTH', 8); //constant,MarkCentering watermark
define('THINKIMAGE_WATER_SOUTHEAST', 9); //constant,MarkThe lower right corner Watermark

/**
 * imagedeal withdriveclass,canConfigurationimagedeal withStorehouse
 * Currently supportGDLibrary andimagick
 * @author When wheat seedlings child <zuojiazi.cn@gmail.com>
 */
class ThinkImage
{
    /**
     * Image Resources
     * @var resource
     */
    private $img;

    /**
     * structuremethod,ForInstantiationOneimagedeal withObjects
     * @param string $type wantuseofClass Library,defaultuseGDStorehouse
     */
    public function __construct($type = THINKIMAGE_GD, $imgname = null)
    {
        /* Type judgment call library */
        switch ($type) {
            case THINKIMAGE_GD:
                $class = 'ImageGd';
                break;
            case THINKIMAGE_IMAGICK:
                $class = 'ImageImagick';
                break;
            default:
                throw new Exception('It does not support the type of image processing library');
        }

        /* Introduceddeal withStorehouse,Instantiationimagedeal withObjects */
        require_once "Driver/{$class}.class.php";
        $this->img = new $class($imgname);
    }

    /**
     * Open an image
     * @param  string $imgname Picture Path
     * @return Object          currentimagedeal withStorehouseObjects
     */
    public function open($imgname)
    {
        $this->img->open($imgname);
        return $this;
    }

    /**
     * save Picture
     * @param  string $imgname Save the picture name
     * @param  string $type Picture type
     * @param  boolean $interlace WhetherJPEGTypes ofimageSet upInterlaced
     * @return Object             currentimagedeal withStorehouseObjects
     */
    public function save($imgname, $type = null, $interlace = true)
    {
        $this->img->save($imgname, $type, $interlace);
        return $this;
    }

    /**
     * Return to image width
     * @return integer Image widthdegree
     */
    public function width()
    {
        return $this->img->width();
    }

    /**
     * Return to image height
     * @return integer Image Height
     */
    public function height()
    {
        return $this->img->height();
    }

    /**
     * Returns the image type
     * @return string Picture type
     */
    public function type()
    {
        return $this->img->type();
    }

    /**
     * Returns the imageMIMETypes of
     * @return string imageMIMETypes of
     */
    public function mime()
    {
        return $this->img->mime();
    }

    /**
     * Returns an array of image size 0 - Image widthdegree,1 - Image Height
     * @return array size of the picture
     */
    public function size()
    {
        return $this->img->size();
    }

    /**
     * Crop picture
     * @param  integer $w Crop area width
     * @param  integer $h Crop Area Height
     * @param  integer $x Crop areaxcoordinate
     * @param  integer $y Crop areaycoordinate
     * @param  integer $width Save the picture width
     * @param  integer $height Save the picture height
     * @return Object          currentimagedeal withStorehouseObjects
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        $this->img->crop($w, $h, $x, $y, $width, $height);
        return $this;
    }

    /**
     * Generate thumbnails
     * @param  integer $width The maximum width of the thumbnail
     * @param  integer $height The maximum height of the thumbnail
     * @param  integer $type Thumbnail crop type
     * @return Object          currentimagedeal withStorehouseObjects
     */
    public function thumb($width, $height, $type = THINKIMAGE_THUMB_SCALE)
    {
        $this->img->thumb($width, $height, $type);
        return $this;
    }

    /**
     * Add a watermark
     * @param  string $source Watermark image path
     * @param  integer $locate Watermark position
     * @param  integer $alpha Watermark transparency
     * @return Object          currentimagedeal withStorehouseObjects
     */
    public function water($source, $locate = THINKIMAGE_WATER_SOUTHEAST)
    {
        $this->img->water($source, $locate);
        return $this;
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
     * @return Object          currentimagedeal withStorehouseObjects
     */
    public function text($text, $font, $size, $color = '#00000000',
                         $locate = THINKIMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        $this->img->text($text, $font, $size, $color, $locate, $offset, $angle);
        return $this;
    }
}