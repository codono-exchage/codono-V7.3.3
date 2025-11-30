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
 * ThinkPHP APIMode controller base class
 */
abstract class Controller
{

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        //Controllerinitialization
        if (method_exists($this, '_initialize'))
            $this->_initialize();
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
            } else {
                E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
            }
        } else {
            E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
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

}