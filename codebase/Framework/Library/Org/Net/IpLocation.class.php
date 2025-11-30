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
namespace Org\Net;
/**
 *  IP Location query class Modified from CoolCode.CN
 *  The use ofUTF8coding If you use pureIPAddress database of words needCorrectBack to ResultsEnterRowcodingChange
 * @author    liu21st <liu21st@gmail.com>
 */
class IpLocation
{
    /**
     * QQWry.DatFile pointer
     *
     * @var resource
     */
    private $fp;

    /**
     * ArticleIPrecordingofOffsetaddress
     *
     * @var int
     */
    private $firstip;

    /**
     * The last oneIPrecordingofOffsetaddress
     *
     * @var int
     */
    private $lastip;

    /**
     * IPrecordingoftotalArticle number(Does not containVersion Informationrecording)
     *
     * @var int
     */
    private $totalip;

    /**
     * Constructor, open QQWry.Dat fileandinitializationclassmiddleinformation
     *
     * @param string $filename
     * @return IpLocation
     */
    public function __construct($filename = "UTFWry.dat")
    {
        $this->fp = 0;
        if (($this->fp = fopen(dirname(__FILE__) . '/' . $filename, 'rb')) !== false) {
            $this->firstip = $this->getlong();
            $this->lastip = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        }
    }

    /**
     * Return to readLong integer
     *
     * @access private
     * @return int
     */
    private function getlong()
    {
        //willReadoflittle-endiancodingof4Byte conversionforLong integer
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }

    /**
     * Return to read3Byte long integer
     *
     * @access private
     * @return int
     */
    private function getlong3()
    {
        //willReadoflittle-endiancodingof3Byte conversionforLong integer
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));
        return $result['long'];
    }

    /**
     * returnAfter compression can be comparedofIPaddress
     *
     * @access private
     * @param string $ip
     * @return string
     */
    private function packip($ip)
    {
        // willIPAddresses intoLong integerIf thePHP5in,IPaddresserror,thenreturnFalse,
        // ThenintvalwillFlaseTransformationforInteger-1,After compressed intobig-endiancodingofString
        return pack('N', intval(ip2long($ip)));
    }

    /**
     * Returns a string read
     *
     * @access private
     * @param string $data
     * @return string
     */
    private function getstring($data = "")
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) {        // Stringaccording toCSave format to\0End
            $data .= $char;             // willReadofcharacterconnectionGiven toStringafter that
            $char = fread($this->fp, 1);
        }
        return $data;
    }

    /**
     * Area information return
     *
     * @access private
     * @return string
     */
    private function getarea()
    {
        $byte = fread($this->fp, 1);    // 标志byte
        switch (ord($byte)) {
            case 0:                     // Noareainformation
                $area = "";
                break;
            case 1:
            case 2:                     // 标志bytefor1or2,ShowareainformationIsRedirect
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default:                    // otherwise,ShowareainformationNoIsRedirect
                $area = $this->getstring($byte);
                break;
        }
        return $area;
    }

    /**
     * According to the given IP addressorareanamereturnRegioninformation
     *
     * @access public
     * @param string $ip
     * @return array
     */
    public function getlocation($ip = '')
    {
        if (!$this->fp) return null;            // in casedatafileNoIs correctturn on,thenDirect returnair
        if (empty($ip)) $ip = get_client_ip();
        $location['ip'] = gethostbyname($ip);   // willenterofDomain transformationforIPaddress
        $ip = $this->packip($location['ip']);   // willenterofIPAddresses intoComparableofIPaddress
        // 不legitimateofIPaddressIt will be transformedfor255.255.255.255
        // CorrectMinutesearch for
        $l = 0;                         // search forofbelowCircles
        $u = $this->totalip;            // search forofOn top ofCircles
        $findip = $this->lastip;        // in caseNoFind itreturnThe last oneIPrecording(QQWry.Datversion ofinformation)
        while ($l <= $u) {              // whenOn top ofCommunity less thanbelowWhen the community,Seekfailure
            $i = floor(($l + $u) / 2);  // ComputeApproximate intermediaterecording
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4));     // ObtainintermediaterecordingofStartIPaddress
            // strrevfunctioninHereofRole is tolittle-endianCompressionIPAddresses intobig-endianThe format
            // so thatForCompare,Rear面相同。
            if ($ip < $beginip) {       // userofIPLess than the intermediaterecordingofStartIPaddressTime
                $u = $i - 1;            // The searchofOn top ofCirclesmodifyforintermediaterecordingminus one
            } else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));   // ObtainintermediaterecordingofEndIPaddress
                if ($ip > $endip) {     // userofIPLarger than the intermediaterecordingofEndIPaddressTime
                    $l = $i + 1;        // The searchofbelowCirclesmodifyforintermediaterecordingplus one
                } else {                  // userofIPin the middlerecordingofIPWithin the range
                    $findip = $this->firstip + $i * 7;
                    break;              // It meansturn upresult,drop outcycle
                }
            }
        }

        //ObtainSeekToIP地理positioninformation
        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong());   // userIPWhere the rangeofStartaddress
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());     // userIPWhere the rangeofEndaddress
        $byte = fread($this->fp, 1);    // 标志byte
        switch (ord($byte)) {
            case 1:                     // 标志bytefor1Representing national and regionalinformationAre at the same timeRedirect
                $countryOffset = $this->getlong3();         // Redirectaddress
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);    // 标志byte
                switch (ord($byte)) {
                    case 2:             // 标志bytefor2,ShowcountryinformationHas beenRedirect
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default:            // otherwise,ShowcountryinformationNoIsRedirect
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2:                     // 标志bytefor2,ShowcountryinformationIsRedirect
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default:                    // otherwise,ShowcountryinformationNoIsRedirect
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }
        if (trim($location['country']) == 'CZ88.NET') {  // CZ88.NETIndicates that no valid information
            $location['country'] = 'unknown';
        }
        if (trim($location['area']) == 'CZ88.NET') {
            $location['area'] = '';
        }
        return $location;
    }

    /**
     * 析构function,Forpagecarried outEndRearautomaticshut downturn ondocument。
     *
     */
    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }

}