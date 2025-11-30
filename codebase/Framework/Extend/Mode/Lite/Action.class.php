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
 * ThinkPHP ActionController base class Lite mode
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author   liu21st <liu21st@gmail.com>
 */
abstract class Action
{

    // currentActionname
    private $name = '';
    protected $tVar = array(); // Template output variables

    /**
     * Architecturefunction Made template object instance
     * @access public
     */
    public function __construct()
    {
        tag('action_begin');
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
     * Template variable assignment
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } elseif (is_object($name)) {
            foreach ($name as $key => $val)
                $this->tVar[$key] = $val;
        } else {
            $this->tVar[$name] = $value;
        }
    }

    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * Get the value of a template variable
     * @access public
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
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
            if (method_exists($this, '_empty')) {
                // in casedefinitionThe_emptyoperating thentransfer
                $this->_empty($method, $args);
            } elseif (file_exists_case(C('TEMPLATE_NAME'))) {
                // an examinationdoes it existdefaultstencil If there isdirectExportstencil
                $this->display();
            } else {
                // Throw an exception
                throw_exception(L('_ERROR_ACTION_') . ACTION_NAME);
            }
        } else {
            switch (strtolower($method)) {
                // judgmentsubmitthe way
                case 'ispost':
                case 'isget':
                case 'ishead':
                case 'isdelete':
                case 'isput':
                    return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method, 2));
                // Obtainvariable Supports filtering and default values transferthe way $this->_post($key,$filter,$default);
                case '_get':
                    $input =& $_GET;
                    break;
                case '_post':
                    $input =& $_POST;
                    break;
                case '_put':
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
                case '_globals':
                    $input =& $GLOBALS;
                    break;
                default:
                    throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            }
            if (isset($input[$args[0]])) { // The valueoperating
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
            return $data;
        }
    }

    /**
     * operatingerrorJumpShortcutmethod
     * @access protected
     * @param string $message Error Messages
     * @param string $jumpUrl Page jump address
     * @param Boolean $ajax WhetherAjaxthe way
     * @return void
     */
    protected function error($message, $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 0, $jumpUrl, $ajax);
    }

    /**
     * Successful operationJumpShortcutmethod
     * @access protected
     * @param string $message Tips
     * @param string $jumpUrl Page jump address
     * @param Boolean $ajax WhetherAjaxthe way
     * @return void
     */
    protected function success($message, $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 1, $jumpUrl, $ajax);
    }

    /**
     * Ajaxthe wayReturn dataTo the client
     * @access protected
     * @param mixed $data Data to be returned
     * @param String $info Tips
     * @param boolean $status Return status
     * @param String $status ajaxReturn Type JSON XML
     * @return void
     */
    protected function ajaxReturn($data, $info = '', $status = 1, $type = '')
    {
        $result = array();
        $result['status'] = $status;
        $result['info'] = $info;
        $result['data'] = $data;
        //SpreadajaxReturn data, inActionDefinedfunction ajaxAssign(&$result){} method SpreadajaxReturn data.
        if (method_exists($this, "ajaxAssign"))
            $this->ajaxAssign($result);
        if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
        if (strtoupper($type) == 'JSON') {
            // returnJSONdataformatTo the client containstatusinformation
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        } elseif (strtoupper($type) == 'XML') {
            // returnxmlformatdata
            header("Content-Type:text/xml; charset=utf-8");
            exit(xml_encode($result));
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
     * @param Boolean $ajax WhetherAjaxthe way
     * @access private
     * @return void
     */
    private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false)
    {
        // Determine whetherAJAXreturn
        if ($ajax || $this->isAjax()) $this->ajaxReturn($ajax, $message, $status);
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
            if (!$this->get('waitSecond')) $this->assign('waitSecond', "1");
            // defaultSuccessful operationautomaticreturnoperatingbeforepage
            if (!$this->get('jumpUrl')) $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            $this->assign('error', $message);// Tips
            //occurerrortimedefaultRemain3second
            if (!$this->get('waitSecond')) $this->assign('waitSecond', "3");
            // defaultoccurerrorofwordsautomaticreturnlast page
            if (!$this->get('jumpUrl')) $this->assign('jumpUrl', "javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // Stay of execution  Avoid the mistakes continuecarried out
            exit;
        }
    }

    /**
     * Load TemplatewithpageExport Content can return output
     * @access public
     * @param string $templateFile Template filesname
     * @param string $charset Template output character set
     * @param string $contentType Output Type
     * @return mixed
     */
    public function display($templateFile = '', $charset = '', $contentType = '')
    {
        G('viewStartTime');
        // viewStart tag
        tag('view_begin', $templateFile);
        // ResolveandObtainTemplate content
        $content = $this->fetch($templateFile);
        // Output template content
        $this->show($content, $charset, $contentType);
        // viewEnd tag
        tag('view_end');
    }

    /**
     * Output contentText canincludeHtml
     * @access public
     * @param string $content Output content
     * @param string $charset Template output character set
     * @param string $contentType Output Type
     * @return mixed
     */
    public function show($content, $charset = '', $contentType = '')
    {
        if (empty($charset)) $charset = C('DEFAULT_CHARSET');
        if (empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        // network charactercoding
        header("Content-Type:" . $contentType . "; charset=" . $charset);
        header("Cache-control: private");  //Support page rebound
        
        // ExportTemplate files
        echo $content;
    }

    /**
     * ResolvewithObtainTemplate content For output
     * @access public
     * @param string $templateFile Template filesname
     * @return string
     */
    public function fetch($templateFile = '')
    {
        // Template file parsing tag
        tag('view_template', $templateFile);
        // Template filesdoes not existDirect return
        if (!is_file($templateFile)) return NULL;
        // pageCache
        ob_start();
        ob_implicit_flush(0);
        // viewResolvelabel
        $params = array('var' => $this->tVar, 'file' => $templateFile);
        $result = tag('view_parse', $params);
        if (false === $result) { // Undefined behavior Is usedPHPPrimeval Moban
            // templateArrayvariableBroken down intoforindependentvariable
            extract($this->tVar, EXTR_OVERWRITE);
            // directLoadingPHPtemplate
            include $templateFile;
        }
        // ObtainandClearCache
        $content = ob_get_clean();
        // contentfilterlabel
        tag('view_filter', $content);
        // ExportTemplate files
        return $content;
    }

    /**
     * Destructor
     * @access public
     */
    public function __destruct()
    {
        // StorageJournal
        if (C('LOG_RECORD')) Log::save();
        // carried outFollow-upoperating
        tag('action_end');
    }
}