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

/**
 * ThinkPHP ActionController base class Abstract class
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author   liu21st <liu21st@gmail.com>
 */
abstract class Action
{

    /**
     * View objects Examples
     * @var view
     * @access protected
     */
    protected $view = null;

    /**
     * The current controller name
     * @var name
     * @access protected
     */
    private $name = '';

    /**
     * Controller parameters
     * @var config
     * @access protected
     */
    protected $config = array();

    /**
     * Architecturefunction Made template object instance
     * @access public
     */
    public function __construct()
    {
        tag('action_begin', $this->config);
        //InstantiationView class
        $this->view = Think::instance('View');
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
     * @return bool
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
     * Template display transferBuilt-inTemplate enginedisplaymethod,
     * @access protected
     * @param string $templateFile DesignationwanttransferofTemplate files
     * The default is empty bysystemAutomatic positioning template file
     * @param string $charset Output encoding
     * @param string $contentType Output Type
     * @param string $content Output content
     * @param string $prefix Prefix template cache
     * @return void
     */
    protected function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        $this->view->display($templateFile, $charset, $contentType, $content, $prefix);
    }

    /**
     * Output contentText canincludeHtml And analytical support content
     * @access protected
     * @param string $content Output content
     * @param string $charset Template output character set
     * @param string $contentType Output Type
     * @param string $prefix Prefix template cache
     * @return mixed
     */
    protected function show($content, $charset = '', $contentType = '', $prefix = '')
    {
        $this->view->display('', $charset, $contentType, $content, $prefix);
    }

    /**
     *  Takes the output page content
     * transferBuilt-inTemplate enginefetchmethod,
     * @access protected
     * @param string $templateFile DesignationwanttransferofTemplate files
     * The default is empty bysystemAutomatic positioning template file
     * @param string $content Template output content
     * @param string $prefix Prefix template cache*
     * @return string
     */
    protected function fetch($templateFile = '', $content = '', $prefix = '')
    {
        return $this->view->fetch($templateFile, $content, $prefix);
    }

    /**
     *  Creating a static page
     * @access protected
     * @htmlfile FormStaticfilename
     * @htmlpath FormStaticfile path
     * @param string $templateFile DesignationwanttransferofTemplate files
     * The default is empty bysystemAutomatic positioning template file
     * @return string
     */
    protected function buildHtml($htmlfile = '', $htmlpath = '', $templateFile = '')
    {
        $content = $this->fetch($templateFile);
        $htmlpath = !empty($htmlpath) ? $htmlpath : HTML_PATH;
        $htmlfile = $htmlpath . $htmlfile . C('HTML_FILE_SUFFIX');
        if (!is_dir(dirname($htmlfile)))
            // If the static directory does not exist Create
            mkdir(dirname($htmlfile), 0755, true);
        if (false === file_put_contents($htmlfile, $content))
            throw_exception(L('_CACHE_WRITE_ERROR_') . ':' . $htmlfile);
        return $content;
    }

    /**
     * Template Theme Set
     * @access protected
     * @param string $theme Template Theme
     * @return Action
     */
    protected function theme($theme)
    {
        $this->view->theme($theme);
        return $this;
    }

    /**
     * Template variable assignment
     * @access protected
     * @param mixed $name To display the template variables
     * @param mixed $value The value of the variable
     * @return Action
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }

    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * ObtainTemplate variable displayThe value
     * @access protected
     * @param string $name Template variable display
     * @return mixed
     */
    public function get($name = '')
    {
        return $this->view->get($name);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Value detection template variables
     * @access public
     * @param string $name name
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->get($name);
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
        if (0 === strcasecmp($method, ACTION_NAME . C('ACTION_SUFFIX'))) {
            if (method_exists($this, '_empty')) {
                // in casedefinitionThe_emptyoperating thentransfer
                $this->_empty($method, $args);
            } elseif (file_exists_case($this->view->parseTemplate())) {
                // an examinationdoes it existdefaultstencil If there isdirectExportstencil
                $this->display();
            } elseif (function_exists('__hack_action')) {
                // hack Define extended operations
                __hack_action();
            } else {
                _404(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
            }
        } else {
            switch (strtolower($method)) {
                // judgmentsubmitthe way
                case 'ispost'   :
                case 'isget'    :
                case 'ishead'   :
                case 'isdelete' :
                case 'isput'    :
                    return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method, 2));
                // Obtainvariable Supports filtering and default values transferthe way $this->_post($key,$filter,$default);
                case '_get'     :
                    $input =& $_GET;
                    break;
                case '_post'    :
                    $input =& $_POST;
                    break;
                case '_put'     :
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                case '_param'   :
                    switch ($_SERVER['REQUEST_METHOD']) {
                        case 'POST':
                            $input = $_POST;
                            break;
                        case 'PUT':
                            parse_str(file_get_contents('php://input'), $input);
                            break;
                        default:
                            $input = $_GET;
                    }
                    if (C('VAR_URL_PARAMS') && isset($_GET[C('VAR_URL_PARAMS')])) {
                        $input = array_merge($input, $_GET[C('VAR_URL_PARAMS')]);
                    }
                    break;
                case '_request' :
                    $input =& $_REQUEST;
                    break;
                case '_session' :
                    $input =& $_SESSION;
                    break;
                case '_cookie'  :
                    $input =& $_COOKIE;
                    break;
                case '_server'  :
                    $input =& $_SERVER;
                    break;
                case '_globals' :
                    $input =& $GLOBALS;
                    break;
                default:
                    throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            }
            if (!isset($args[0])) { // Global variables
                $data = $input; // byVAR_FILTERSFilter configuration
            } elseif (isset($input[$args[0]])) { // The valueoperating
                $data = $input[$args[0]];
                $filters = isset($args[1]) ? $args[1] : C('DEFAULT_FILTER');
                if ($filters) {// 2012/3/23 increasemanymethodfilterstand by
                    $filters = explode(',', $filters);
                    foreach ($filters as $filter) {
                        if (function_exists($filter)) {
                            $data = is_array($data) ? array_map($filter, $data) : $filter($data); // parameterfilter
                        }
                    }
                }
            } else { // variableDefaults
                $data = isset($args[2]) ? $args[2] : NULL;
            }
            Log::record('RecommendedIAlternative Methods' . $method, Log::NOTICE);
            return $data;
        }
    }

    /**
     * operatingerrorJumpShortcutmethod
     * @access protected
     * @param string $message Error Messages
     * @param string $jumpUrl Page jump address
     * @param mixed $ajax WhetherAjaxthe way whendigitalTimeDesignationJumptime
     * @return void
     */
    protected function error($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 0, $jumpUrl, $ajax);
    }

    /**
     * Successful operationJumpShortcutmethod
     * @access protected
     * @param string $message Tips
     * @param string $jumpUrl Page jump address
     * @param mixed $ajax WhetherAjaxthe way whendigitalTimeDesignationJumptime
     * @return void
     */
    protected function success($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 1, $jumpUrl, $ajax);
    }

    /**
     * Ajaxthe wayReturn dataTo the client
     * @access protected
     * @param mixed $data Data to be returned
     * @param String $type AJAXReturn dataformat
     * @return void
     */
    protected function ajaxReturn($data, $type = '')
    {
        if (func_num_args() > 2) {// compatible3.0Before usage
            $args = func_get_args();
            array_shift($args);
            $info = array();
            $info['data'] = $data;
            $info['info'] = array_shift($args);
            $info['status'] = array_shift($args);
            $data = $info;
            $type = $args ? array_shift($args) : '';
        }
        if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)) {
            case 'JSON' :
                // returnJSONdataformatTo the client containstatusinformation
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data));
            case 'XML'  :
                // returnxmlformatdata
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // returnJSONdataformatTo the client containstatusinformation
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler . '(' . json_encode($data) . ');');
            case 'EVAL' :
                // returncancarried outofjsscript
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :
                // ForSpreadotherreturnformatdata
                tag('ajax_return', $data);
        }
    }

    /**
     * ActionJump(URLRedirect) stand byDesignationModuleAnd delayJump
     * @access protected
     * @param string $url JumpURLexpression
     * @param array $params otherURLparameter
     * @param integer $delay Time delay Jump Seconds
     * @param string $msg Jump message
     * @return void
     */
    protected function redirect($url, $params = array(), $delay = 0, $msg = '')
    {
        $url = U($url, $params);
        redirect($url, $delay, $msg);
    }

    /**
     * Default jump operation stand byerrorGuide and correctJump
     * Call the template display The default ispublicDirectoryofsuccesspage
     * Tip page is configurable Support Template Tags
     * @param string $message Tips
     * @param Boolean $status status
     * @param string $jumpUrl Page jump address
     * @param mixed $ajax WhetherAjaxthe way whendigitalTimeDesignationJumptime
     * @access private
     * @return void
     */
    private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false)
    {
        if (true === $ajax || IS_AJAX) {// AJAXsubmit
            $data = is_array($ajax) ? $ajax : array();
            $data['info'] = $message;
            $data['status'] = $status;
            $data['url'] = $jumpUrl;
            $this->ajaxReturn($data);
        }
        if (is_int($ajax)) $this->assign('waitSecond', $ajax);
        if (!empty($jumpUrl)) $this->assign('jumpUrl', $jumpUrl);
        // prompttitle
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //If you setshut downwindow,thenpromptcompleteRearautomaticshut downwindow
        if ($this->get('closeWin')) $this->assign('jumpUrl', 'javascript:window.close();');
        $this->assign('status', $status);   // status
        //Ensure the export is not affected by the static cache

        C('HTML_CACHE_ON', false);
        if ($status) { //sendsuccessinformation
            $this->assign('message', $message);// Tips
            // successoperatingReardefaultRemain1second
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '1');
            // defaultSuccessful operationautomaticreturnoperatingbeforepage
            if (!isset($this->jumpUrl)) $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            $this->assign('error', $message);// Tips
            //occurerrortimedefaultRemain3second
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '3');
            // defaultoccurerrorofwordsautomaticreturnlast page
            if (!isset($this->jumpUrl)) $this->assign('jumpUrl', "javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // Stay of execution  Avoid the mistakes continuecarried out
            exit;
        }
    }

    /**
     * Destructor
     * @access public
     */
    public function __destruct()
    {
        // carried outFollow-upoperating
        tag('action_end');
    }
}