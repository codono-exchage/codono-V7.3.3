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
 * ThinkPHPBuilt-in template engine class
 * stand byXMLlabelwithordinarylabeloftemplateResolve
 * Compiled template engine Support dynamic cache
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author liu21st <liu21st@gmail.com>
 */
class  ThinkTemplate
{

    // templatepageIntroduceds MarkStorehouseList
    protected $tagLib = array();
    // currentTemplate files
    protected $templateFile = '';
    // Template variables
    public $tVar = array();
    public $config = array();
    private $literal = array();
    private $block = array();

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        $this->config['cache_path'] = C('CACHE_PATH');
        $this->config['template_suffix'] = C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix'] = C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache'] = C('TMPL_CACHE_ON');
        $this->config['cache_time'] = C('TMPL_CACHE_TIME');
        $this->config['taglib_begin'] = $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end'] = $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin'] = $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end'] = $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl'] = C('TEMPLATE_NAME');
        $this->config['layout_item'] = C('TMPL_LAYOUT_ITEM');
    }

    private function stripPreg($str)
    {
        return str_replace(
            array('{', '}', '(', ')', '|', '[', ']', '-', '+', '*', '.', '^', '?'),
            array('\{', '\}', '\(', '\)', '\|', '\[', '\]', '\-', '\+', '\*', '\.', '\^', '\?'),
            $str);
    }

    // Template variablesObtainwithSet up
    public function get($name)
    {
        if (isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    public function set($name, $value)
    {
        $this->tVar[$name] = $value;
    }

    /**
     * Load Template
     * @access public
     * @param string $tmplTemplateFile Template files
     * @param array $templateVar Template variables
     * @param string $prefix Template ID prefix
     * @return void
     */
    public function fetch($templateFile, $templateVar, $prefix = '')
    {
        $this->tVar = $templateVar;
        $templateCacheFile = $this->loadTemplate($templateFile, $prefix);
        // templateArrayvariableBroken down intoforindependentvariable
        extract($templateVar, EXTR_OVERWRITE);
        //LoadingstencilCachefile
        include $templateCacheFile;
    }

    /**
     * Load master template and cache
     * @access public
     * @param string $tmplTemplateFile Template files
     * @param string $prefix Template ID prefix
     * @return string
     * @throws ThinkExecption
     */
    public function loadTemplate($tmplTemplateFile, $prefix = '')
    {
        if (is_file($tmplTemplateFile)) {
            $this->templateFile = $tmplTemplateFile;
            // ReadTemplate filescontent
            $tmplContent = file_get_contents($tmplTemplateFile);
        } else {
            $tmplContent = $tmplTemplateFile;
        }
        // according tostencilfilePositioning nameCachefile
        $tmplCacheFile = $this->config['cache_path'] . $prefix . md5($tmplTemplateFile) . $this->config['cache_suffix'];

        // Determine whetherEnablelayout
        if (C('LAYOUT_ON')) {
            if (false !== strpos($tmplContent, '{__NOLAYOUT__}')) { // You can separatedefinition uselayout
                $tmplContent = str_replace('{__NOLAYOUT__}', '', $tmplContent);
            } else { // replacelayoutofmain bodycontent
                $layoutFile = THEME_PATH . C('LAYOUT_NAME') . $this->config['template_suffix'];
                $tmplContent = str_replace($this->config['layout_item'], $tmplContent, file_get_contents($layoutFile));
            }
        }
        // CompileTemplate content
        $tmplContent = $this->compiler($tmplContent);
        // Detection template directory
        $dir = dirname($tmplCacheFile);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        //RewriteCachefile
        if (false === file_put_contents($tmplCacheFile, trim($tmplContent)))
            throw_exception(L('_CACHE_WRITE_ERROR_') . ':' . $tmplCacheFile);
        return $tmplCacheFile;
    }

    /**
     * Compiled template file contents
     * @access protected
     * @param mixed $tmplContent Template content
     * @return string
     */
    protected function compiler($tmplContent)
    {
        //templateResolve
        $tmplContent = $this->parse($tmplContent);
        // Restore replacedLiterallabel
        $tmplContent = preg_replace('/<!--###literal(\d+)###-->/eis', "\$this->restoreLiteral('\\1')", $tmplContent);
        // Add toSafetyCode
        $tmplContent = '<?php if (!defined(\'THINK_PATH\')) exit();?>' . $tmplContent;
        if (C('TMPL_STRIP_SPACE')) {
            /* RemovalhtmlSpaces and line breaks */
            $find = array('~>\s+<~', '~>(\s+\n|\r)~');
            $replace = array('><', '>');
            $tmplContent = preg_replace($find, $replace, $tmplContent);
        }
        // optimizationFormofphpCode
        $tmplContent = str_replace('?><?php', '', $tmplContent);
        return strip_whitespace($tmplContent);
    }

    /**
     * Template parsing entrance
     * Label and general supportTagLibResolve Support for custom tag libraries
     * @access public
     * @param string $content To be parsed template content
     * @return string
     */
    public function parse($content)
    {
        // contentforair Resolve
        if (empty($content)) return '';
        $begin = $this->config['taglib_begin'];
        $end = $this->config['taglib_end'];
        // an examinationincludegrammar
        $content = $this->parseInclude($content);
        // an examinationPHPgrammar
        $content = $this->parsePhp($content);
        // First of allreplaceliteralLabel content
        $content = preg_replace('/' . $begin . 'literal' . $end . '(.*?)' . $begin . '\/literal' . $end . '/eis', "\$this->parseLiteral('\\1')", $content);

        // ObtainneedIntroduceds MarkStorehouseList
        // Tag Libraryonlyneeddefinition Secondary,allowIntroducedMoreSecondary
        // General onDocumentsFrontmost
        // format:<taglib name="html,mytag..." />
        // whenTAGLIB_LOADConfigurationfortrueWhen will beDetect
        if (C('TAGLIB_LOAD')) {
            $this->getIncludeTagLib($content);
            if (!empty($this->tagLib)) {
                // CorrectImportingofTagLibEnterRowResolve
                foreach ($this->tagLib as $tagLibName) {
                    $this->parseTagLib($tagLibName, $content);
                }
            }
        }
        // advanceloads MarkStorehouse No needEachtemplateinusetagliblabelload buthave touseTag LibraryXMLPrefix
        if (C('TAGLIB_PRE_LOAD')) {
            $tagLibs = explode(',', C('TAGLIB_PRE_LOAD'));
            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }
        // InternalTag Library No needusetagliblabelImportingoncan use And withoutuseTag LibraryXMLPrefix
        $tagLibs = explode(',', C('TAGLIB_BUILD_IN'));
        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }
        //Resolve common template tag {tagName}
        $content = preg_replace('/(' . $this->config['tmpl_begin'] . ')([^\d\s' . $this->config['tmpl_begin'] . $this->config['tmpl_end'] . '].+?)(' . $this->config['tmpl_end'] . ')/eis', "\$this->parseTag('\\2')", $content);
        return $content;
    }

    // an examinationPHPgrammar
    protected function parsePhp($content)
    {
        if (ini_get('short_open_tag')) {
            // OpenshortlabelofTo case<?LabelsechoOutput way Otherwise, not outputxmlMark
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
        }
        // PHPSyntax checking
        if (C('TMPL_DENY_PHP') && false !== strpos($content, '<?php')) {
            throw_exception(L('_NOT_ALLOW_PHP_'));
        }
        return $content;
    }

    // ResolveTemplatelayoutlabel
    protected function parseLayout($content)
    {
        // ReadTemplatelayoutlabel
        $find = preg_match('/' . $this->config['taglib_begin'] . 'layout\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
        if ($find) {
            //replaceLayoutlabel
            $content = str_replace($matches[0], '', $content);
            //ResolveLayoutlabel
            $array = $this->parseXmlAttrs($matches[1]);
            if (!C('LAYOUT_ON') || C('LAYOUT_NAME') != $array['name']) {
                // Readlayouttemplate
                $layoutFile = THEME_PATH . $array['name'] . $this->config['template_suffix'];
                $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                // replacelayoutofmain bodycontent
                $content = str_replace($replace, $content, file_get_contents($layoutFile));
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
        return $content;
    }

    // ResolveTemplateincludelabel
    protected function parseInclude($content)
    {
        // Resolveinherit
        $content = $this->parseExtend($content);
        // Resolvelayout
        $content = $this->parseLayout($content);
        // ReadTemplateincludelabel
        $find = preg_match_all('/' . $this->config['taglib_begin'] . 'include\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
        if ($find) {
            for ($i = 0; $i < $find; $i++) {
                $include = $matches[1][$i];
                $array = $this->parseXmlAttrs($include);
                $file = $array['file'];
                unset($array['file']);
                $content = str_replace($matches[0][$i], $this->parseIncludeItem($file, $array), $content);
            }
        }
        return $content;
    }

    // ResolveTemplateextendlabel
    protected function parseExtend($content)
    {
        $begin = $this->config['taglib_begin'];
        $end = $this->config['taglib_end'];
        // ReadTemplateinheritlabel
        $find = preg_match('/' . $begin . 'extend\s(.+?)\s*?\/' . $end . '/is', $content, $matches);
        if ($find) {
            //replaceextendlabel
            $content = str_replace($matches[0], '', $content);
            // recordingpagemiddleblocklabel
            preg_replace('/' . $begin . 'block\sname=(.+?)\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/eis', "\$this->parseBlock('\\1','\\2')", $content);
            // Readinherittemplate
            $array = $this->parseXmlAttrs($matches[1]);
            $content = $this->parseTemplateName($array['name']);
            // replaceblocklabel
            $content = preg_replace('/' . $begin . 'block\sname=(.+?)\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/eis', "\$this->replaceBlock('\\1','\\2')", $content);
        } else {
            $content = preg_replace('/' . $begin . 'block\sname=(.+?)\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/eis', "stripslashes('\\2')", $content);
        }
        return $content;
    }

    /**
     * analysisXMLAttributes
     * @access private
     * @param string $attrs XMLAttribute string
     * @return array
     */
    private function parseXmlAttrs($attrs)
    {
        $xml = '<tpl><tag ' . $attrs . ' /></tpl>';
        $xml = simplexml_load_string($xml);
        if (!$xml)
            throw_exception(L('_XML_TAG_ERROR_'));
        $xml = (array)($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        return $array;
    }

    /**
     * Replace pageliterallabel
     * @access private
     * @param string $content Template content
     * @return string|false
     */
    private function parseLiteral($content)
    {
        if (trim($content) == '') return '';
        $content = stripslashes($content);
        $i = count($this->literal);
        $parseStr = "<!--###literal{$i}###-->";
        $this->literal[$i] = $content;
        return $parseStr;
    }

    /**
     * Restore replacedliterallabel
     * @access private
     * @param string $tag literalSerial number label
     * @return string|false
     */
    private function restoreLiteral($tag)
    {
        // reductionliterallabel
        $parseStr = $this->literal[$tag];
        // destroyliteralrecording
        unset($this->literal[$tag]);
        return $parseStr;
    }

    /**
     * Record current pageblocklabel
     * @access private
     * @param string $name blockname
     * @param string $content Template content
     * @return string
     */
    private function parseBlock($name, $content)
    {
        $this->block[$name] = $content;
        return '';
    }

    /**
     * Replaces an inherited templateblocklabel
     * @access private
     * @param string $name blockname
     * @param string $content Template content
     * @return string
     */
    private function replaceBlock($name, $content)
    {
        // replaceblocklabel NoAgaindefinitionthenuseoriginalof
        $replace = isset($this->block[$name]) ? $this->block[$name] : $content;
        return stripslashes($replace);
    }

    /**
     * search fortemplatepageContainsofTagLibStorehouse
     * And returns a list
     * @access public
     * @param string $content Template content
     * @return string|false
     */
    public function getIncludeTagLib(& $content)
    {
        //search forwhetherHaveTagLiblabel
        $find = preg_match('/' . $this->config['taglib_begin'] . 'taglib\s(.+?)(\s*?)\/' . $this->config['taglib_end'] . '\W/is', $content, $matches);
        if ($find) {
            //replaceTagLiblabel
            $content = str_replace($matches[0], '', $content);
            //ResolveTagLiblabel
            $array = $this->parseXmlAttrs($matches[1]);
            $this->tagLib = explode(',', $array['name']);
        }
        return;
    }

    /**
     * TagLibParsing library
     * @access public
     * @param string $tagLib wantResolves MarkStorehouse
     * @param string $content To be parsed template content
     * @param boolen $hide whetherhideTag LibraryPrefix
     * @return string
     */
    public function parseTagLib($tagLib, &$content, $hide = false)
    {
        $begin = $this->config['taglib_begin'];
        $end = $this->config['taglib_end'];
        $className = 'TagLib' . ucwords($tagLib);
        $tLib = Think::instance($className);
        foreach ($tLib->getTags() as $name => $val) {
            $tags = array($name);
            if (isset($val['alias'])) {// SlugSet up
                $tags = explode(',', $val['alias']);
                $tags[] = $name;
            }
            $level = isset($val['level']) ? $val['level'] : 1;
            $closeTag = isset($val['close']) ? $val['close'] : true;
            foreach ($tags as $tag) {
                $parseTag = !$hide ? $tagLib . ':' . $tag : $tag;// actualwantResolves Markname
                if (!method_exists($tLib, '_' . $tag)) {
                    // Aliases may not needdefinitionAnalytical method
                    $tag = $name;
                }
                $n1 = empty($val['attr']) ? '(\s*?)' : '\s([^' . $end . ']*)';
                if (!$closeTag) {
                    $patterns = '/' . $begin . $parseTag . $n1 . '\/(\s*?)' . $end . '/eis';
                    $replacement = "\$this->parseXmlTag('$tagLib','$tag','$1','')";
                    $content = preg_replace($patterns, $replacement, $content);
                } else {
                    $patterns = '/' . $begin . $parseTag . $n1 . $end . '(.*?)' . $begin . '\/' . $parseTag . '(\s*?)' . $end . '/eis';
                    $replacement = "\$this->parseXmlTag('$tagLib','$tag','$1','$2')";
                    for ($i = 0; $i < $level; $i++)
                        $content = preg_replace($patterns, $replacement, $content);
                }
            }
        }
    }

    /**
     * Resolution tag tag libraries
     * needtransfercorresponds MarkStorehousefileResolveclass
     * @access public
     * @param string $tagLib Name tag library
     * @param string $tag label Name
     * @param string $attr Tag attributes
     * @param string $content Label content
     * @return string|false
     */
    public function parseXmlTag($tagLib, $tag, $attr, $content)
    {
        //if (MAGIC_QUOTES_GPC) {
        $attr = stripslashes($attr);
        $content = stripslashes($content);
        //}
        if (ini_get('magic_quotes_sybase'))
            $attr = str_replace('\"', '\'', $attr);
        $tLib = Think::instance('TagLib' . ucwords(strtolower($tagLib)));
        $parse = '_' . $tag;
        $content = trim($content);
        return $tLib->$parse($attr, $content);
    }

    /**
     * Template tag resolution
     * format: {TagName:args [|content] }
     * @access public
     * @param string $tagStr Label content
     * @return string
     */
    public function parseTag($tagStr)
    {
        //if (MAGIC_QUOTES_GPC) {
        $tagStr = stripslashes($tagStr);
        //}
        //Reducing non-template tag
        if (preg_match('/^[\s|\d]/is', $tagStr))
            //Filtering spaceAnd begin with a number of labels
            return C('TMPL_L_DELIM') . $tagStr . C('TMPL_R_DELIM');
        $flag = substr($tagStr, 0, 1);
        $flag2 = substr($tagStr, 1, 1);
        $name = substr($tagStr, 1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) { //ResolveTemplate variables format {$varName}
            return $this->parseVar($name);
        } elseif ('-' == $flag || '+' == $flag) { // ExportCompute
            return '<?php echo ' . $flag . $name . ';?>';
        } elseif (':' == $flag) { // ExportAfunctionofresult
            return '<?php echo ' . $name . ';?>';
        } elseif ('~' == $flag) { // carried outAfunction
            return '<?php ' . $name . ';?>';
        } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr($tagStr, -2) == '*/')) {
            //Notelabel
            return '';
        }
        // unrecognizeds MarkDirect return
        return C('TMPL_L_DELIM') . $tagStr . C('TMPL_R_DELIM');
    }

    /**
     * Template multivariate analysis,It supports the use of functions
     * format: {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $varStr Variable data
     * @return string
     */
    public function parseVar($varStr)
    {
        $varStr = trim($varStr);
        static $_varParseList = array();
        //in casealreadyResolveThrough thevariableCharacter string,thenDirect returnvariablevalue
        if (isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr = '';
        $varExists = true;
        if (!empty($varStr)) {
            $varArray = explode('|', $varStr);
            //Obtainvariablename
            $var = array_shift($varArray);
            if ('Think.' == substr($var, 0, 6)) {
                // allWithThink.HeadingofSpecialvariableCorrectWait No needtemplateAssignment canExport
                $name = $this->parseThinkVar($var);
            } elseif (false !== strpos($var, '.')) {
                //stand by {$var.property}
                $vars = explode('.', $var);
                $var = array_shift($vars);
                switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    case 'array': // RecognitionforArray
                        $name = '$' . $var;
                        foreach ($vars as $key => $val)
                            $name .= '["' . $val . '"]';
                        break;
                    case 'obj':  // RecognitionforObjects
                        $name = '$' . $var;
                        foreach ($vars as $key => $val)
                            $name .= '->' . $val;
                        break;
                    default:  // Automatically determine theArrayorObjects Only supportsTwo-dimensional
                        $name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
                }
            } elseif (false !== strpos($var, '[')) {
                //stand by {$var['key']} Output wayArray
                $name = "$" . $var;
                preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
                $var = $match[1];
            } elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
                //stand by {$var:property} Output wayObjectsAttributes
                $vars = explode(':', $var);
                $var = str_replace(':', '->', $var);
                $name = "$" . $var;
                $var = $vars[0];
            } else {
                $name = "$$var";
            }
            //Correctvariableusefunction
            if (count($varArray) > 0)
                $name = $this->parseVarFunction($name, $varArray);
            $parseStr = '<?php echo (' . $name . '); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     * CorrectTemplate variablesusefunction
     * format {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name variable name
     * @param array $varArray Function List
     * @return string
     */
    public function parseVarFunction($name, $varArray)
    {
        //Correctvariableusefunction
        $length = count($varArray);
        //ObtaintemplateBanuseFunction List
        $template_deny_funs = explode(',', C('TMPL_DENY_FUNC_LIST'));
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //templatefunctionfilter
            $fun = strtolower(trim($args[0]));
            switch ($fun) {
                case 'default':  // specialtemplatefunction
                    $name = '(' . $name . ')?(' . $name . '):' . $args[1];
                    break;
                default:  // Commontemplatefunction
                    if (!in_array($fun, $template_deny_funs)) {
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name = "$fun($args[1])";
                            } else {
                                $name = "$fun($name,$args[1])";
                            }
                        } else if (!empty($args[0])) {
                            $name = "$fun($name)";
                        }
                    }
            }
        }
        return $name;
    }

    /**
     * specialTemplate multivariate analysis
     * format With $Think. HeadingofvariableIs a specialTemplate variables
     * @access public
     * @param string $varStr Variable string
     * @return string
     */
    public function parseThinkVar($varStr)
    {
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
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
                    } else {
                        $parseStr = 'cookie(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[3])) {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = 'session(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . strtoupper($vars[2]) . '\']';
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
                    if (isset($vars[3])) {
                        $vars[2] .= '.' . $vars[3];
                    }
                    $parseStr = 'C("' . $vars[2] . '")';
                    break;
                default:
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
                    $parseStr = "'" . $this->templateFile . "'";//'C("TEMPLATE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if (defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    /**
     * loadpublictemplateandCache withcurrenttemplatein path,otherwiseuserelativelypath
     * @access private
     * @param string $tmplPublicName publicTemplate filesname
     * @param array $vars List of variables to be passed
     * @return string
     */
    private function parseIncludeItem($tmplPublicName, $vars = array())
    {
        // analysisTemplate filesName andReadcontent
        $parseStr = $this->parseTemplateName($tmplPublicName);
        // replacevariable
        foreach ($vars as $key => $val) {
            $parseStr = str_replace('[' . $key . ']', $val, $parseStr);
        }
        // Again includedfileEnterRowtemplateanalysis
        return $this->parseInclude($parseStr);
    }

    /**
     * analysisloadofTemplate filesandReadcontent Support for multipleTemplate filesRead
     * @access private
     * @param string $tmplPublicName Template filesname
     * @return string
     */
    private function parseTemplateName($templateName)
    {
        if (substr($templateName, 0, 1) == '$')
            //stand byloadvariablefilename
            $templateName = $this->get(substr($templateName, 1));
        $array = explode(',', $templateName);
        $parseStr = '';
        foreach ($array as $templateName) {
            if (false === strpos($templateName, $this->config['template_suffix'])) {
                // Parsing rules Packet@Template Theme:Module:operating
                if (strpos($templateName, '@')) {
                    list($group, $templateName) = explode('@', $templateName);
                    if (1 == C('APP_GROUP_MODE')) {
                        $basePath = dirname(BASE_LIB_PATH) . '/' . $group . '/' . basename(TMPL_PATH) . '/' . (THEME_NAME ? THEME_NAME . '/' : '');
                    } else {
                        $basePath = TMPL_PATH . '/' . $group . '/' . (THEME_NAME ? THEME_NAME . '/' : '');
                    }
                } else {
                    $basePath = THEME_PATH;
                }
                $templateName = str_replace(':', '/', $templateName);
                $path = explode('/', $templateName);
                $action = array_pop($path);
                $module = !empty($path) ? array_pop($path) : MODULE_NAME;
                if (!empty($path)) {// Template Theme Set
                    $basePath = dirname($basePath) . '/' . array_pop($path) . '/';
                }
                $templateName = $basePath . $module . C('TMPL_FILE_DEPR') . $action . $this->config['template_suffix'];
            }
            // ObtainTemplate filescontent
            $parseStr .= file_get_contents($templateName);
        }
        return $parseStr;
    }
}