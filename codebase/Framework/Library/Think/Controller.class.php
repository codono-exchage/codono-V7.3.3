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
namespace Think;
/**
 * ThinkPHP Controller base class Abstract class
 */
abstract class Controller
{

    /**
     * View objects Examples
     * @var view
     * @access protected
     */
    protected $view = null;

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
        $am_i_banned=isIPBanned(hacker_ip(),"banned_list");
        
        if($am_i_banned){
            exit('Your IP has been banned from Using this site , Please contact administration');
        }
        Hook::listen('action_begin', $this->config);
        //InstantiationView class
        $this->view = Think::instance('Think\View');
        //Controllerinitialization
        if (method_exists($this, '_initialize'))
            $this->_initialize();
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
        Storage::put($htmlfile, $content, 'html');
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
            } else {
                if (ADMIN_DEBUG == 0) {
                    die($this->redirect('Home/Index/index'));
                };
                E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
            }
        } else {
            if (ADMIN_DEBUG == 0) {
                die($this->redirect('Home/Index/index'));
            }
            clog(__CLASS__.'/'.__METHOD__ ,  $method . L('_METHOD_NOT_EXIST_'));
            return "We are upgrading our system :FLTCC02";
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
     * @param string $type AJAXReturn dataformat
     * @param int $json_option Pass tojson_encodeofoptionparameter
     * @return void
     */
    protected function ajaxReturn($data, $type = '', $json_option = 0)
    {
        if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)) {
            case 'JSON' :
                // returnJSONdataformatTo the client containstatusinformation
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data, $json_option));
            case 'XML'  :
                // returnxmlformatdata
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // returnJSONdataformatTo the client containstatusinformation
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler . '(' . json_encode($data, $json_option) . ');');
            case 'EVAL' :
                // returncancarried outofjsscript
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :
                // ForSpreadotherreturnformatdata
                Hook::listen('ajax_return', $data);
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
     * @param boolean $status status
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
		//	cookie('previous_error', $message);

            // successoperatingReardefaultRemain1second
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '1');
            // defaultSuccessful operationautomaticreturnoperatingbeforepage
            if (!isset($this->jumpUrl)) $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            
            $this->assign('error', $message);// Tips
			$previous_page=$_SERVER['HTTP_REFERER'];
			//$_SESSION['previous_error'] = $message;

			cookie('previous_error', $message);

            //occur error time default Remain 3seconds
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '0');
            // default occur error of words automatic return last page
			$back_url=$_SERVER["HTTP_REFERER"]?:'/';
            
			//die(var_dump($back_url));
			if (!isset($this->jumpUrl))
            {
                $this->assign('jumpUrl', $back_url);
            }

		//	redirect("javascript:history.back(-1);");
			$this->display(C('TMPL_ACTION_ERROR'));
			// redirect($back_url);
		    // Stay of execution  Avoid the mistakes continue carried out
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
        Hook::listen('action_end');
    }
}

// Set upControllerSlug Easy to upgrade
class_alias('Think\Controller', 'Think\Action');
