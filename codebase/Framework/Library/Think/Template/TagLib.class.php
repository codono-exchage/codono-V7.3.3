<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think\Template;
/**
 * ThinkPHPTag LibraryTagLibAnalytical base class
 */
class TagLib
{

    /**
     * Tag library definitionsXMLfile
     * @var string
     * @access protected
     */
    protected $xml = '';
    protected $tags = array();// labeldefinition
    /**
     * Name tag library
     * @var string
     * @access protected
     */
    protected $tagLib = '';

    /**
     * Tag Library Tag List
     * @var string
     * @access protected
     */
    protected $tagList = array();

    /**
     * Tag library array analysis
     * @var string
     * @access protected
     */
    protected $parse = array();

    /**
     * Tag library is valid
     * @var string
     * @access protected
     */
    protected $valid = false;

    /**
     * The current template object
     * @var object
     * @access protected
     */
    protected $tpl;

    protected $comparison = array(' nheq ' => ' !== ', ' heq ' => ' === ', ' neq ' => ' != ', ' eq ' => ' == ', ' egt ' => ' >= ', ' gt ' => ' > ', ' elt ' => ' <= ', ' lt ' => ' < ');

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        $this->tagLib = strtolower(substr(get_class($this), 6));
        $this->tpl = \Think\Think::instance('Think\\Template');
    }

    /**
     * TagLibTag attribute analysis Back to TAG property array
     * @access public
     * @param string $tagStr Label content
     * @return array
     */
    public function parseXmlAttr($attr, $tag)
    {
        //XMLResolve security filtering
        $attr = str_replace('&', '___', $attr);
        $xml = '<tpl><tag ' . $attr . ' /></tpl>';
        $xml = simplexml_load_string($xml);
        if (!$xml) {
            E(L('_XML_TAG_ERROR_') . ' : ' . $attr);
        }
        $xml = (array)($xml->tag->attributes());
        if (isset($xml['@attributes'])) {
            $array = array_change_key_case($xml['@attributes']);
            if ($array) {
                $tag = strtolower($tag);
                if (!isset($this->tags[$tag])) {
                    // Detecting whetherexistAlias definition
                    foreach ($this->tags as $key => $val) {
                        if (isset($val['alias']) && in_array($tag, explode(',', $val['alias']))) {
                            $item = $val;
                            break;
                        }
                    }
                } else {
                    $item = $this->tags[$tag];
                }
                $attrs = explode(',', $item['attr']);
                if (isset($item['must'])) {
                    $must = explode(',', $item['must']);
                } else {
                    $must = array();
                }
                foreach ($attrs as $name) {
                    if (isset($array[$name])) {
                        $array[$name] = str_replace('___', '&', $array[$name]);
                    } elseif (false !== array_search($name, $must)) {
                        E(L('_PARAM_ERROR_') . ':' . $name);
                    }
                }
                return $array;
            }
        } else {
            return array();
        }
    }

    /**
     * Analytical conditional expression
     * @access public
     * @param string $condition Expressions label content
     * @return array
     */
    public function parseCondition($condition)
    {
        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $condition);
        switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
            case 'array': // RecognitionforArray
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $condition);
                break;
            case 'obj':  // RecognitionforObjects
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1->\\2 ', $condition);
                break;
            default:  // Automatically determine theArrayorObjects Only supportsTwo-dimensional
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ', $condition);
        }
        if (false !== strpos($condition, '$Think'))
            $condition = preg_replace_callback('/(\$Think.*?)\s/is', array($this, 'parseThinkVar'), $condition);
        return $condition;
    }

    /**
     * Construction of Automatic Identification variables
     * @access public
     * @param string $name Variable Description
     * @return string
     */
    public function autoBuildVar($name)
    {
        if ('Think.' == substr($name, 0, 6)) {
            // specialvariable
            return $this->parseThinkVar($name);
        } elseif (strpos($name, '.')) {
            $vars = explode('.', $name);
            $var = array_shift($vars);
            switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                case 'array': // RecognitionforArray
                    $name = '$' . $var;
                    foreach ($vars as $key => $val) {
                        if (0 === strpos($val, '$')) {
                            $name .= '["{' . $val . '}"]';
                        } else {
                            $name .= '["' . $val . '"]';
                        }
                    }
                    break;
                case 'obj':  // RecognitionforObjects
                    $name = '$' . $var;
                    foreach ($vars as $key => $val)
                        $name .= '->' . $val;
                    break;
                default:  // Automatically determine theArrayorObjects Only supportsTwo-dimensional
                    $name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
            }
        } elseif (strpos($name, ':')) {
            // additionalofObjectsthe waystand by
            $name = '$' . str_replace(':', '->', $name);
        } elseif (!defined($name)) {
            $name = '$' . $name;
        }
        return $name;
    }

    /**
     * ForTag attributesinsidespecialTemplate multivariate analysis
     * format With Think. HeadingofvariableIs a specialTemplate variables
     * @access public
     * @param string $varStr Variable string
     * @return string
     */
    public function parseThinkVar($varStr)
    {
        if (is_array($varStr)) {//ForRegularreplaceCallback
            $varStr = $varStr[1];
        }
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . $vars[2] . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[2] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[2] . '\']';
                    break;
                case 'COOKIE':
                    if (isset($vars[3])) {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } elseif (C('COOKIE_PREFIX')) {
                        $parseStr = '$_COOKIE[\'' . C('COOKIE_PREFIX') . $vars[2] . '\']';
                    } else {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\']';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[3])) {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } elseif (C('SESSION_PREFIX')) {
                        $parseStr = '$_SESSION[\'' . C('SESSION_PREFIX') . '\'][\'' . $vars[2] . '\']';
                    } else {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\']';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . $vars[2] . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);
                    break;
                case 'LANG':
                    $parseStr = 'L("' . $vars[2] . '")';
                    break;
                case 'CONFIG':
                    $parseStr = 'C("' . $vars[2] . '")';
                    break;
            }
        } else if (count($vars) == 2) {
            switch ($vars[1]) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = 'C("TEMPLATE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if (defined($vars[1])) $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    // Obtainlabeldefinition
    public function getTags()
    {
        return $this->tags;
    }
}