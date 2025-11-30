<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * ThinkPHP Restful Controller extension
 * @category   Extend
 * @package  Extend
 * @subpackage  Action
 * @author    liu21st <liu21st@gmail.com>
 */
abstract class RestAction
{

    // currentActionname
    private $name = '';
    // viewExamples
    protected $view = null;
    protected $_method = ''; // currentrequestTypes of
    protected $_type = ''; // currentResourcesTypes of
    // Output Type
    protected $_types = array();

    /**
     * Architecturefunction Made template object instance
     * @access public
     */
    public function __construct()
    {
        //InstantiationView class
        $this->view = Think::instance('View');

        if (!defined('__EXT__')) define('__EXT__', '');

        // Resource type detection
        if ('' == __EXT__) { // automaticDetectResourcesTypes of
            $this->_type = $this->getAcceptType();
        } elseif (false === stripos(C('REST_CONTENT_TYPE_LIST'), __EXT__)) {
            // ResourcesTypes ofnon-law Then usedefaultResourcesTypes ofaccess
            $this->_type = C('REST_DEFAULT_TYPE');
        } else {
            // Detecting the actual resource type
            if ($this->getAcceptType() == __EXT__) {
                $this->_type = __EXT__;
            } else {
                $this->_type = C('REST_DEFAULT_TYPE');
            }
        }

        // requestthe wayDetect
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (false === stripos(C('REST_METHOD_LIST'), $method)) {
            // requestthe waynon-law Then usedefaultRequest method
            $method = C('REST_DEFAULT_METHOD');
        }
        $this->_method = $method;
        // allowExportofResourcesTypes of
        $this->_types = C('REST_OUTPUT_TYPE');

        //Controllerinitialization
        if (method_exists($this, '_initialize'))
            $this->_initialize();
    }

    /**
     * Get the currentActionname
     * @access protected
     */
    protected function getActionName()
    {
        if (empty($this->name)) {
            // ObtainActionname
            $this->name = substr(get_class($this), 0, -6);
        }
        return $this->name;
    }

    /**
     * whetherAJAXrequest
     * @access protected
     * @return boolean
     */
    protected function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if (!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
            // judgmentAjaxthe waysubmit
            return true;
        return false;
    }

    /**
     * Magic Methods Havedoes not existofoperatingoftimecarried out
     * @access public
     * @param string $method methodname
     * @param array $args parameter
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (0 === strcasecmp($method, ACTION_NAME)) {
            if (method_exists($this, $method . '_' . $this->_method . '_' . $this->_type)) { // RESTFulMethods support
                $fun = $method . '_' . $this->_method . '_' . $this->_type;
                $this->$fun();
            } elseif ($this->_method == C('REST_DEFAULT_METHOD') && method_exists($this, $method . '_' . $this->_type)) {
                $fun = $method . '_' . $this->_type;
                $this->$fun();
            } elseif ($this->_type == C('REST_DEFAULT_TYPE') && method_exists($this, $method . '_' . $this->_method)) {
                $fun = $method . '_' . $this->_method;
                $this->$fun();
            } elseif (method_exists($this, '_empty')) {
                // in casedefinitionThe_emptyoperating thentransfer
                $this->_empty($method, $args);
            } elseif (file_exists_case(C('TMPL_FILE_NAME'))) {
                // an examinationdoes it existdefaultstencil If there isdirectExportstencil
                $this->display();
            } else {
                // Throw an exception
                throw_exception(L('_ERROR_ACTION_') . ACTION_NAME);
            }
        } else {
            switch (strtolower($method)) {
                // Obtainvariable Supports filtering and default values transferthe way $this->_post($key,$filter,$default);
                case '_get':
                    $input =& $_GET;
                    break;
                case '_post':
                    $input =& $_POST;
                    break;
                case '_put':
                case '_delete':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                case '_request':
                    $input =& $_REQUEST;
                    break;
                case '_session':
                    $input =& $_SESSION;
                    break;
                case '_cookie':
                    $input =& $_COOKIE;
                    break;
                case '_server':
                    $input =& $_SERVER;
                    break;
                default:
                    throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            }
            if (isset($input[$args[0]])) { // The valueoperating
                $data = $input[$args[0]];
                $fun = $args[1] ?: C('DEFAULT_FILTER');
                $data = $fun($data); // parameterfilter
            } else { // variableDefaults
                $data = $args[2] ?? NULL;
            }
            return $data;
        }
    }

    /**
     * Template display
     * transferBuilt-inTemplate enginedisplaymethod,
     * @access protected
     * @param string $templateFile DesignationwanttransferofTemplate files
     * The default is empty bysystemAutomatic positioning template file
     * @param string $charset Output encoding
     * @param string $contentType Output Type
     * @return void
     */
    protected function display($templateFile = '', $charset = '', $contentType = '')
    {
        $this->view->display($templateFile, $charset, $contentType);
    }

    /**
     * Template variable assignment
     * @access protected
     * @param mixed $name To display the template variables
     * @param mixed $value The value of the variable
     * @return void
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    public function __set($name, $value)
    {
        $this->view->assign($name, $value);
    }

    /**
     * Settings page outputCONTENT_TYPEAnd coding
     * @access public
     * @param string $type content_type Types ofcorrespondingExtension
     * @param string $charset Page output encoding
     * @return void
     */
    public function setContentType($type, $charset = '')
    {
        if (headers_sent()) return;
        if (empty($charset)) $charset = C('DEFAULT_CHARSET');
        $type = strtolower($type);
        if (isset($this->_types[$type])) //filtercontent_type
            header('Content-Type: ' . $this->_types[$type] . '; charset=' . $charset);
    }

    /**
     * Output data is returned
     * @access protected
     * @param mixed $data Data to be returned
     * @param String $type Return Type JSON XML
     * @param integer $code HTTPstatus
     * @return void
     */
    protected function response($data, $type = '', $code = 200)
    {
        // StorageJournal
        if (C('LOG_RECORD')) Log::save();
        $this->sendHttpStatus($code);
        exit($this->encodeData($data, strtolower($type)));
    }

    /**
     * Encoded data
     * @access protected
     * @param mixed $data Data to be returned
     * @param String $type Return Type JSON XML
     * @return void
     */
    protected function encodeData($data, $type = '')
    {
        if (empty($data)) return '';
        if ('json' == $type) {
            // returnJSONdataformatTo the client containstatusinformation
            $data = json_encode($data);
        } elseif ('xml' == $type) {
            // returnxmlformatdata
            $data = xml_encode($data);
        } elseif ('php' == $type) {
            $data = serialize($data);
        }// defaultdirectExport
        $this->setContentType($type);
        header('Content-Length: ' . strlen($data));
        return $data;
    }

    // sendHttpstatusinformation
    protected function sendHttpStatus($code)
    {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
            // make sureFastCGI mode normal
            header('Status:' . $code . ' ' . $_status[$code]);
        }
    }

    /**
     * Get the current requestAcceptHeader
     * @return string
     */
    protected function getAcceptType()
    {
        $type = array(
            'html' => 'text/html,application/xhtml+xml,*/*',
            'xml' => 'application/xml,text/xml,application/x-xml',
            'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
            'js' => 'text/javascript,application/javascript,application/x-javascript',
            'css' => 'text/css',
            'rss' => 'application/rss+xml',
            'yaml' => 'application/x-yaml,text/yaml',
            'atom' => 'application/atom+xml',
            'pdf' => 'application/pdf',
            'text' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpg,image/jpeg,image/pjpeg',
            'gif' => 'image/gif',
            'csv' => 'text/csv'
        );

        foreach ($type as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($_SERVER['HTTP_ACCEPT'], $v)) {
                    return $key;
                }
            }
        }
        return false;
    }
}