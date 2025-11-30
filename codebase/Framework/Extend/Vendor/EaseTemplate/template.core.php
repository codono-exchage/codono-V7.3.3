<?php
/* 
 * Edition:	ET080708
 * Desc:	Core Engine 3 (Memcache/Compile/Replace)
 * File:	template.core.php
 * Author:	David Meng
 * Site:	http://www.systn.com
 * Email:	mdchinese@gmail.com
 * 
 */
error_reporting(0);

define("ET3!", TRUE);

class ETCore
{
    var $ThisFile = '';                //currentfile
    var $IncFile = '';                //Introducedfile
    var $ThisValue = array();            //currentnumbervalue
    var $FileList = array();            //LoadingfileList
    var $IncList = array();            //IntroducedfileList
    var $ImgDir = array('images');    //imageaddresstable of Contents
    var $HtmDir = 'cache_htm/';        //Static stateDepositoftable of Contents
    var $HtmID = '';                //Static statefileID
    var $HtmTime = '180';            //secondforunit,defaultthreeminute
    var $AutoImage = 1;                //automaticResolveimagetable of ContentsswitchDefaults
    var $Hacker = "<?php if(!defined('ET3!')){die('You are Hacker!<br>Power by Ease Template!');}";
    var $Compile = array();
    var $Analysis = array();
    var $Emc = array();

    /**
     *    Statement template usage
     */
    function ETCoreStart(
        $set = array(
            'ID' => '1',                    //CacheID
            'TplType' => 'htm',                //templateformat
            'CacheDir' => 'cache',                //Cachetable of Contents
            'TemplateDir' => 'template',            //templateDeposittable of Contents
            'AutoImage' => 'on',                //automaticResolveimagetable of Contentsswitch onShowopen offShowshut down
            'LangDir' => 'language',            //LanguagefileDepositoftable of Contents
            'Language' => 'default',            //Languageofdefaultfile
            'Copyright' => 'off',                //Copyright Protection
            'MemCache' => '',                    //MemcacheSuch as server address:127.0.0.1:11211
        )
    )
    {

        $this->TplID = (defined('TemplateID') ? TemplateID : (((int)@$set['ID'] <= 1) ? 1 : (int)$set['ID'])) . '_';

        $this->CacheDir = (defined('NewCache') ? NewCache : ((trim($set['CacheDir']) != '') ? $set['CacheDir'] : 'cache')) . '/';

        $this->TemplateDir = (defined('NewTemplate') ? NewTemplate : ((trim($set['TemplateDir']) != '') ? $set['TemplateDir'] : 'template')) . '/';

        $this->Ext = (@$set['TplType'] != '') ? $set['TplType'] : 'htm';

        $this->AutoImage = (@$set['AutoImage'] == 'off') ? 0 : 1;

        $this->Copyright = (@$set['Copyright'] == 'off') ? 0 : 1;

        $this->Server = (is_array($GLOBALS['_SERVER'])) ? $GLOBALS['_SERVER'] : $_SERVER;
        $this->version = (trim($_GET['EaseTemplateVer'])) ? die('Ease Templae E3!') : '';

        //Loading languagefile
        $this->LangDir = (defined('LangDir') ? LangDir : (((@$set['LangDir'] != 'language' && @$set['LangDir']) ? $set['LangDir'] : 'language'))) . '/';
        if (is_dir($this->LangDir)) {
            $this->Language = (defined('Language') ? Language : ((($set['Language'] != 'default' && $set['Language']) ? $set['Language'] : 'default')));
            if (@is_file($this->LangDir . $this->Language . '.php')) {
                $lang = array();
                @include_once $this->LangDir . $this->Language . '.php';
                $this->LangData = $lang;
            }
        } else {
            $this->Language = 'default';
        }


        //Cachetable of ContentsDetectas well asRun mode
        if (@preg_match(':', $set['MemCache'])) {
            $this->RunType = 'MemCache';
            $memset = explode(":", $set['MemCache']);
            $this->Emc = memcache_connect($memset[0], $memset[1]) OR die("Could not connect!");
        } else {
            $this->RunType = (@substr(@sprintf('%o', @fileperms($this->CacheDir)), -3) == 777 && is_dir($this->CacheDir)) ? 'Cache' : 'Replace';
        }

        $CompileBasic = array(
            '/(\{\s*|<!--\s*)inc_php:([a-zA-Z0-9_\[\]\.\,\/\?\=\#\:\;\-\|\^]{5,200})(\s*\}|\s*-->)/eis',

            '/<!--\s*DEL\s*-->/is',
            '/<!--\s*IF(\[|\()(.+?)(\]|\))\s*-->/is',
            '/<!--\s*ELSEIF(\[|\()(.+?)(\]|\))\s*-->/is',
            '/<!--\s*ELSE\s*-->/is',
            '/<!--\s*END\s*-->/is',
            '/<!--\s*([a-zA-Z0-9_\$\[\]\'\"]{2,60})\s*(AS|as)\s*(.+?)\s*-->/',
            '/<!--\s*while\:\s*(.+?)\s*-->/is',

            '/(\{\s*|<!--\s*)lang\:(.+?)(\s*\}|\s*-->)/eis',
            '/(\{\s*|<!--\s*)row\:(.+?)(\s*\}|\s*-->)/eis',
            '/(\{\s*|<!--\s*)color\:\s*([\#0-9A-Za-z]+\,[\#0-9A-Za-z]+)(\s*\}|\s*-->)/eis',
            '/(\{\s*|<!--\s*)dir\:([^\{\}]{1,100})(\s*\}|\s*-->)/eis',
            '/(\{\s*|<!--\s*)run\:(\}|\s*-->)\s*(.+?)\s*(\{|<!--\s*)\/run(\s*\}|\s*-->)/is',
            '/(\{\s*|<!--\s*)run\:(.+?)(\s*\}|\s*-->)/is',
            '/\{([a-zA-Z0-9_\'\"\[\]\$]{1,100})\}/',
        );
        $this->Compile = (is_array($this->Compile)) ? array_merge($this->Compile, $CompileBasic) : $CompileBasic;

        $AnalysisBasic = array(
            '$this->inc_php("\\2")',

            '";if($ET_Del==true){echo"',
            '";if(\\2){echo"',
            '";}elseif(\\2){echo"',
            '";}else{echo"',
            '";}echo"',
            '";\$_i=0;foreach((array)\\1 AS \\3){\$_i++;echo"',
            '";\$_i=0;while(\\1){\$_i++;echo"',

            '$this->lang("\\2")',
            '$this->Row("\\2")',
            '$this->Color("\\2")',
            '$this->Dirs("\\2")',
            '";\\3;echo"',
            '";\\2;echo"',
            '";echo \$\\1;echo"',
        );
        $this->Analysis = (is_array($this->Analysis)) ? array_merge($this->Analysis, $AnalysisBasic) : $AnalysisBasic;

    }


    /**
     *    Setting values
     *    set_var(Or an array of variable names,Setting values[Array not set this value]);
     */
    function set_var(
        $name,
        $value = ''
    )
    {
        if (is_array($name)) {
            $this->ThisValue = @array_merge($this->ThisValue, $name);
        } else {
            $this->ThisValue[$name] = $value;
        }
    }


    /**
     *    Set template file
     *    set_file(filename,Set directory);
     */
    function set_file(
        $FileName,
        $NewDir = ''
    )
    {
        //currenttemplatename
        $this->ThisFile = $FileName . '.' . $this->Ext;

        //table of ContentsaddressDetect
        $this->FileDir[$this->ThisFile] = (trim($NewDir) != '') ? $NewDir . '/' : $this->TemplateDir;

        $this->IncFile[$FileName] = $this->FileDir[$this->ThisFile] . $this->ThisFile;

        if (!is_file($this->IncFile[$FileName]) && $this->Copyright == 1) {
            die('Sorry, The file <b>' . $this->IncFile[$FileName] . '</b> does not exist.');
        }


        //bug system
        $this->IncList[] = $this->ThisFile;
    }

    //Resolvereplaceprogram
    function ParseCode(
        $FileList = '',
        $CacheFile = ''
    )
    {
        //templatedata
        $ShowTPL = '';
        //ResolveContinued carrier
        if (@is_array($FileList) && $FileList != 'include_page') {
            foreach ($FileList AS $K => $V) {
                $ShowTPL .= $this->reader($V . $K);
            }
        } else {


            //in caseDesignationAddress fileThe load
            $SourceFile = ($FileList != '') ? $FileList : $this->FileDir[$this->ThisFile] . $this->ThisFile;

            if (!is_file($SourceFile) && $this->Copyright == 1) {
                die('Sorry, The file <b>' . $SourceFile . '</b> does not exist.');
            }

            $ShowTPL = $this->reader($SourceFile);
        }

        //Quotetemplatedeal with
        $ShowTPL = $this->inc_preg($ShowTPL);

        //Detectrunmethod
        $run = 0;
        if (preg_match("run:", $ShowTPL)) {
            $run = 1;
            //Fix =
            $ShowTPL = preg_replace('/(\{|<!--\s*)run:(\}|\s*-->)\s*=/', '{run:}echo ', $ShowTPL);
            $ShowTPL = preg_replace('/(\{|<!--\s*)run:\s*=/', '{run:echo ', $ShowTPL);
            //Fix Run 1
            $ShowTPL = preg_replace('/(\{|<!--\s*)run:(\}|\s*-->)\s*(.+?)\s*(\{|<!--\s*)\/run(\}|\s*-->)/is', '(T_T)\\3;(T_T!)', $ShowTPL);
        }

        //Fix XML
        if (preg_match("<?xml", $ShowTPL)) {
            $ShowTPL = @preg_replace('/<\?(xml.+?)\?>/is', '<ET>\\1</ET>', $ShowTPL);
        }

        //修复Codein\nWraperror
        $ShowTPL = str_replace('\\', '\\\\', $ShowTPL);
        //Repair of double quotesproblem
        $ShowTPL = str_replace('"', '\"', $ShowTPL);

        //CompileOperation
        $ShowTPL = @preg_replace($this->Compile, $this->Analysis, $ShowTPL);

        //Image analysis address
        $ShowTPL = $this->ImgCheck($ShowTPL);

        //Fix Money symbol template
        $ShowTPL = str_replace('$', '\$', $ShowTPL);

        //修复phprunerror
        $ShowTPL = @preg_replace("/\";(.+?)echo\"/e", '$this->FixPHP(\'\\1\')', $ShowTPL);

        //Fix Run 2
        if ($run == 1) {
            $ShowTPL = preg_replace("/\(T_T\)(.+?)\(T_T!\)/ise", '$this->FixPHP(\'\\1\')', $ShowTPL);
        }

        //reductionxml
        $ShowTPL = (strrpos($ShowTPL, '<ET>')) ? @preg_replace('/ET>(.+?)<\/ET/is', '?\\1?', $ShowTPL) : $ShowTPL;

        //修复"problem
        $ShowTPL = str_replace('echo ""', 'echo "\"', $ShowTPL);


        //FromArrayWillvariableImportingTocurrentofSymbol table
        @extract($this->Value());
        ob_start();
        ob_implicit_flush(0);
        @eval('echo "' . $ShowTPL . '";');
        $contents = ob_get_contents();
        ob_end_clean();

        //Cache htm
        if ($this->HtmID) {
            $this->writer($this->HtmDir . $this->HtmID, $this->Hacker . "?>" . $contents);
        }


        //Compiletemplate
        if ($this->RunType == 'Cache') {
            $this->CompilePHP($ShowTPL, $CacheFile);
        }


        //erroran examination
        if (strlen($contents) <= 0) {
            //echo $ShowTPL;
            die('<br>Sorry, Error or complicated syntax error exists in ' . $SourceFile . ' file.');
        }

        return $contents;
    }


    /**
     *  multi-language
     */
    function lang(
        $str = ''
    )
    {
        $lang=array();
        $docs=$etl="";

        if (is_dir($this->LangDir)) {

            //useMD5Efficacy
            $id = md5($str);

            //does not existdatathenWrite
            if ($this->LangData[$id] == '' && $this->Language == 'default') {

                //Language Packsfile
                if (@is_file($this->LangDir . $this->Language . '.php')) {
                    unset($lang);
                    @include($this->LangDir . $this->Language . '.php');
                }


                //in caseDetectTo havedatathenExport
                if ($lang[$id]) {
                    $out = str_replace('\\', '\\\\', $lang[$id]);
                    return str_replace('"', '\"', $out);
                }


                //repir  many\problem
                $str = str_replace("\\'", "'", $str);


                //LanguagefileTake too much time to create a newfile
                if (strlen($docs) > 400) {
                    $this->writer($this->LangDir . $this->Language . '.' . $id . '.php', '<? $etl = "' . $str . '";?>');
                    $docs = substr($str, 0, 40);        //A brief description
                    $docs = str_replace('\"', '"', $docs);
                    $docs = str_replace('\\\\', '\\', $docs);
                    $str = 'o(O_O)o.ET Lang.o(*_*)o';    //New Languagefile
                } else {
                    $docs = str_replace('\"', '"', $str);
                    $docs = str_replace('\\\\', '\\', $docs);
                }

                //fileSafetydeal with
                $data = (!is_file($this->LangDir . 'default.php')) ? "<?\n/**\n/* SYSTN ET Language For " . $this->Language . "\n*/\n\n\n" : '';


                if (trim($str)) {
                    //data input
                    $data .= "/**" . date("Y.m.d", time()) . "\n";
                    $data .= $docs . "\n";
                    $data .= "*/\n";
                    $data .= '$lang["' . $id . '"] = "' . $str . '";' . "\n\n";
                    $this->writer($this->LangDir . 'default.php', $data, 'a+');
                }
            }

            //Language alonefilepackage
            if ($this->LangData[$id] == 'o(O_O)o.ET Lang.o(*_*)o') {
                unset($etl);
                include($this->LangDir . $this->Language . "." . $id . ".php");
                $this->LangData[$id] = $etl;
            }

            $out = ($this->LangData[$id]) ? $this->LangData[$id] : $str;

            //ExportsectionTo dodeal with
            if (($this->RunType == 'Replace' || $this->RunType != 'Replace') && $data == '') {
                $out = str_replace('\\', '\\\\', $out);
                $out = str_replace('"', '\"', $out);
            }

            return $out;
        } else {
            return $str;
        }
    }

    /**
     *  incReference Functions
     */
    function inc_preg(
        $content
    )
    {
        return preg_replace('/<\!--\s*\#include\s*file\s*=(\"|\')([a-zA-Z0-9_\.\|]{1,100})(\"|\')\s*-->/eis', '$this->inc("\\2")', preg_replace('/(\{\s*|<!--\s*)inc\:([^\{\} ]{1,100})(\s*\}|\s*-->)/eis', '$this->inc("\\2")', $content));
    }


    /**
     *  Reference function operation
     */
    function inc(
        $Files = ''
    )
    {
        if ($Files) {
            if (!strrpos($Files, $this->Ext)) {
                $Files = $Files . "." . $this->Ext;
            }
            $FileLs = $this->TemplateDir . $Files;
            $contents = $this->ParseCode($FileLs, $Files);

            if ($this->RunType == 'Cache') {
                //Quotetemplate
                $this->IncList[] = $Files;
                $cache_file = $this->CacheDir . $this->TplID . $Files . "." . $this->Language . ".php";
                return "<!-- ET_inc_cache[" . $Files . "] -->
<!-- IF(@is_file('" . $cache_file . "')) -->{inc_php:" . $cache_file . "}
<!-- IF(\$EaseTemplate3_Cache) -->{run:@eval('echo \"'.\$EaseTemplate3_Cache.'\";')}<!-- END -->
<!-- END -->";
            } elseif ($this->RunType == 'MemCache') {
                //cache date
                memcache_set($this->Emc, $Files . '_date', time()) OR die("Failed to save data at the server.");
                memcache_set($this->Emc, $Files, $contents) OR die("Failed to save data at the server");
                return "<!-- ET_inc_cache[" . $Files . "] -->" . $contents;
            } else {
                //Quotetemplate
                $this->IncList[] = $Files;
                return $contents;
            }
        }
    }


    /**
     *  Compile analytical processing
     */
    function CompilePHP(
        $content = '',
        $cachename = ''
    )
    {
        if ($content) {
            //in caseNoSafetyfilethenautomaticcreate
            if ($this->RunType == 'Cache' && !is_file($this->CacheDir . 'index.htm')) {
                $Ease_name = 'Ease Template!';
                $Ease_base = "<title>$Ease_name</title><a href='http://www.systn.com'>$Ease_name</a>";
                $this->writer($this->CacheDir . 'index.htm', $Ease_base);
                $this->writer($this->CacheDir . 'index.html', $Ease_base);
                $this->writer($this->CacheDir . 'default.htm', $Ease_base);
            }


            //Compilerecording
            $content = str_replace("\\", "\\\\", $content);
            $content = str_replace("'", "\'", $content);
            $content = str_replace('echo"";', "", $content);        //replaceSurplusdata

            $wfile = ($cachename) ? $cachename : $this->ThisFile;
            $this->writer($this->FileName($wfile, $this->TplID), $this->Hacker . '$EaseTemplate3_Cache = \'' . $content . '\';');
        }
    }


    //修复PHPcarried outWhen generatingoferror
    function FixPHP(
        $content = ''
    )
    {
        $content = str_replace('\\\\', '\\', $content);
        return '";' . str_replace('\\"', '"', str_replace('\$', '$', $content)) . 'echo"';
    }


    /**
     *  DetectCachewhetherwantUpdate
     *    filename    Cachefilename
     *    settime        DesignationeventIt providesUpdate,onlyFormemcache
     */
    function FileUpdate($filname, $settime = 0)
    {

        //DetectSet template file
        if (is_array($this->IncFile)) {
            unset($k, $v);
            $update = 0;
            $settime = ($settime > 0) ? $settime : @filemtime($filname);
            foreach ($this->IncFile AS $k => $v) {
                if (@filemtime($v) > $settime) {
                    $update = 1;
                }
            }
            //UpdateCache
            if ($update == 1) {
                return false;
            } else {
                return $filname;
            }

        } else {
            return $filname;
        }
    }


    /**
     *    Output Operational
     *   Filename    Serial compilerExportfilename
     */
    function output(
        $Filename = ''
    )
    {
        switch ($this->RunType) {

            //MemCompilation mode
            case'MemCache':
                if ($Filename == 'include_page') {
                    //directExportfile
                    return $this->reader($this->FileDir[$this->ThisFile] . $this->ThisFile);
                } else {

                    $FileNames = ($Filename) ? $Filename : $this->ThisFile;
                    $CacheFile = $this->FileName($FileNames, $this->TplID);

                    //Detectrecordingtime
                    $updateT = memcache_get($this->Emc, $CacheFile . '_date');
                    $update = $this->FileUpdate($CacheFile, $updateT);

                    $CacheData = memcache_get($this->Emc, $CacheFile);

                    if (trim($CacheData) && $update) {
                        //obtainListfile
                        unset($ks, $vs);
                        preg_match_all('/<\!-- ET\_inc\_cache\[(.+?)\] -->/', $CacheData, $IncFile);
                        if (is_array($IncFile[1])) {
                            foreach ($IncFile[1] AS $ks => $vs) {
                                $this->IncList[] = $vs;
                                $listDate = memcache_get($this->Emc, $vs . '_date');

                                echo @filemtime($this->TemplateDir . $vs) . ' - ' . $listDate . '<br>';

                                //UpdateincCache
                                if (@filemtime($this->TemplateDir . $vs) > $listDate) {
                                    $update = 1;
                                    $this->inc($vs);
                                }
                            }

                            //Updatedata
                            if ($update == 1) {
                                $CacheData = $this->ParseCode($this->FileList, $Filename);
                                //cache date
                                @memcache_set($this->Emc, $CacheFile . '_date', time()) OR die("Failed to save data at the server.");
                                @memcache_set($this->Emc, $CacheFile, $CacheData) OR die("Failed to save data at the server.");
                            }
                        }
                        //Close
                        memcache_close($this->Emc);
                        return $CacheData;
                    } else {
                        if ($Filename) {
                            $CacheData = $this->ParseCode($this->FileList, $Filename);
                            //cache date
                            @memcache_set($this->Emc, $CacheFile . '_date', time()) OR die("Failed to save data at the server.");
                            @memcache_set($this->Emc, $CacheFile, $CacheData) OR die("Failed to save data at the server.");
                            //Close
                            memcache_close($this->Emc);
                            return $CacheData;
                        } else {
                            $CacheData = $this->ParseCode();
                            //cache date
                            @memcache_set($this->Emc, $CacheFile . '_date', time()) OR die("Failed to save data at the server.");
                            @memcache_set($this->Emc, $CacheFile, $CacheData) OR die("Failed to save data at the server2");
                            //Close
                            memcache_close($this->Emc);
                            return $CacheData;
                        }
                    }
                }
                break;


            //Compilation mode
            case'Cache':
                if ($Filename == 'include_page') {
                    //directExportfile
                    return $this->reader($this->FileDir[$this->ThisFile] . $this->ThisFile);
                } else {

                    $FileNames = ($Filename) ? $Filename : $this->ThisFile;
                    $CacheFile = $this->FileName($FileNames, $this->TplID);

                    $CacheFile = $this->FileUpdate($CacheFile);

                    if (@is_file($CacheFile)) {
                        @extract($this->Value());
                        ob_start();
                        ob_implicit_flush(0);
                        include $CacheFile;

                        //obtainListfile
                        if ($EaseTemplate3_Cache != '') {
                            unset($ks, $vs);
                            preg_match_all('/<\!-- ET\_inc\_cache\[(.+?)\] -->/', $EaseTemplate3_Cache, $IncFile);

                            if (is_array($IncFile[1])) {
                                foreach ($IncFile[1] AS $ks => $vs) {
                                    $this->IncList[] = $vs;
                                    //UpdateincCache
                                    if (@filemtime($this->TemplateDir . $vs) > @filemtime($this->CacheDir . $this->TplID . $vs . '.' . $this->Language . '.php')) {
                                        $this->inc($vs);
                                    }
                                }
                            }

                            @eval('echo "' . $EaseTemplate3_Cache . '";');
                            $contents = ob_get_contents();
                            ob_end_clean();
                            return $contents;
                        }
                    } else {
                        if ($Filename) {
                            return $this->ParseCode($this->FileList, $Filename);
                        } else {
                            return $this->ParseCode();
                        }
                    }
                }
                break;


            //replaceengine
            default:
                if ($Filename) {
                    if ($Filename == 'include_page') {
                        //directExportfile
                        return $this->reader($this->FileDir[$this->ThisFile] . $this->ThisFile);
                    } else {
                        return $this->ParseCode($this->FileList);
                    }
                } else {
                    return $this->ParseCode();
                }
        }
    }


    /**
     *  Serial function
     */
    function n()
    {
        //Serializetemplate
        $this->FileList[$this->ThisFile] = $this->FileDir[$this->ThisFile];
    }


    /**
     *    Output template content
     *   Filename    Serial compilerExportfilename
     */
    function r(
        $Filename = ''
    )
    {
        return $this->output($Filename);
    }


    /**
     *    Print template content
     *   Filename    Serial compilerExportfilename
     */
    function p(
        $Filename = ''
    )
    {
        echo $this->output($Filename);
    }


    /**
     *    Image analysis address
     */
    function ImgCheck(
        $content
    )
    {
        //Check Image Dir
        if ($this->AutoImage == 1) {
            $NewFileDir = $this->FileDir[$this->ThisFile];

            //FIX img
            if (is_array($this->ImgDir)) {
                foreach ($this->ImgDir AS $rep) {
                    $rep = trim($rep);
                    //Detecting whethercarried outreplace
                    if (strrpos($content, $rep . "/")) {
                        if (substr($rep, -1) == '/') {
                            $rep = substr($rep, 0, strlen($rep) - 1);
                        }
                        $content = str_replace($rep . '/', $NewFileDir . $rep . '/', $content);
                    }
                }
            }

            //FIX Dir
            $NewFileDirs = $NewFileDir . $NewFileDir;
            if (strrpos($content, $NewFileDirs)) {
                $content = str_replace($NewFileDirs, $NewFileDir, $content);
            }
        }
        return $content;
    }


    /**
     *    obtainallSet upversuspublicvariable
     */
    function Value()
    {
        return (is_array($this->ThisValue)) ? array_merge($this->ThisValue, $GLOBALS) : $GLOBALS;
    }


    /**
     *    Clear settings
     */
    function clear()
    {
        $this->RunType = 'Replace';
    }


    /**
     *  Static stateFile Write
     */
    function htm_w(
        $w_dir = '',
        $w_filename = '',
        $w_content = ''
    )
    {

        $dvs = '';
        if ($w_dir && $w_filename && $w_content) {
            //table of ContentsDetectQuantity
            $w_dir_ex = explode('/', $w_dir);
            $w_new_dir = '';    //deal withAfterWritetable of Contents
            unset($dvs, $fdk, $fdv, $w_dir_len);
            foreach ((array)$w_dir_ex AS $dvs) {
                if (trim($dvs) && $dvs != '..') {
                    $w_dir_len .= '../';
                    $w_new_dir .= $dvs . '/';
                    if (!@is_dir($w_new_dir)) @mkdir($w_new_dir, 0777);
                }
            }


            //obtainneedchangeoftable of Contentsnumber
            foreach ((array)$this->FileDir AS $fdk => $fdv) {
                $w_content = str_replace($fdv, $w_dir_len . str_replace('../', '', $fdv), $w_content);
            }

            $this->writer($w_dir . $w_filename, $w_content);
        }
    }


    /**
     *  Change the static refresh time
     */
    function htm_time($times = 0)
    {
        if ((int)$times > 0) {
            $this->HtmTime = (int)$times;
        }
    }


    /**
     *  Static statefileDepositofabsolutetable of Contents
     */
    function htm_dir($Name = '')
    {
        if (trim($Name)) {
            $this->HtmDir = trim($Name) . '/';
        }
    }


    /**
     *  Generate static file output
     */
    function HtmCheck(
        $Name = ''
    )
    {
        $this->HtmID = md5(trim($Name) ? trim($Name) . '.php' : $this->Server['REQUEST_URI'] . '.php');
        //Detecttime
        if (is_file($this->HtmDir . $this->HtmID) && (time() - @filemtime($this->HtmDir . $this->HtmID) <= $this->HtmTime)) {
            ob_start();
            ob_implicit_flush(0);
            include $this->HtmDir . $this->HtmID;
            $HtmContent = ob_get_contents();
            ob_end_clean();
            return $HtmContent;
        }
    }


    /**
     *  Print static content
     */
    function htm_p(
        $Name = ''
    )
    {
        $output = $this->HtmCheck($Name);
        if ($output) {
            die($this->HtmCheck($Name));
        }
    }


    /**
     *  Output static content
     */
    function htm_r(
        $Name = ''
    )
    {
        return $this->HtmCheck($Name);
    }


    /**
     *    analyse file
     */
    function FileName(
        $name,
        $id = '1'
    )
    {
        $extdir = explode("/", $name);
        $dircnt = @count($extdir) - 1;
        $extdir[$dircnt] = $id . $extdir[$dircnt];

        return $this->CacheDir . implode("_", $extdir) . "." . $this->Language . '.php';
    }


    /**
     *  Detection import documents
     */
    function inc_php(
        $url = ''
    )
    {
        $parse = parse_url($url);
        unset($vals, $code_array);
        foreach ((array)explode('&', $parse['query']) AS $vals) {
            $code_array .= preg_replace('/(.+)=(.+)/', "\$_GET['\\1']= \$\\1 ='\\2';", $vals);
        }
        return '";' . $code_array . ' @include(\'' . $parse['path'] . '\');echo"';
    }


    /**
     *    Wrap function
     *    Row(Change the number of rows,Wrap color);
     *    Row("5,#ffffff:#e1e1e1");
     */
    function Row(
        $Num = ''
    )
    {
        $Num = trim($Num);
        if ($Num != '') {
            $Nums = explode(",", $Num);
            $Numr = ((int)$Nums[0] > 0) ? (int)$Nums[0] : 2;
            $input = (trim($Nums[1]) == '') ? '</tr><tr>' : $Nums[1];

            if (trim($Nums[1]) != '') {
                $Co = explode(":", $Nums[1]);
                $OutStr = "if(\$_i%$Numr===0){\$row_count++;echo(\$row_count%2===0)?'</tr><tr bgcolor=\"$Co[0]\">':'</tr><tr bgcolor=\"$Co[1]\">';}";
            } else {
                $OutStr = "if(\$_i%$Numr===0){echo '$input';}";
            }
            return '";' . $OutStr . 'echo "';
        }
    }


    /**
     *    Interval discoloration
     *    Color(Two color code);
     *    Color('#FFFFFF,#DCDCDC');
     */
    function Color(
        $color = ''
    )
    {
        if ($color != '') {
            $OutStr = preg_replace("/(.+),(.+)/", "_i%2===0)?'\\1':'\\2';", $color);
            if (strrpos($OutStr, "%2")) {
                return '";echo(\$' . $OutStr . 'echo "';
            }
        }
    }


    /**
     *    Picture Address Mapping
     */
    function Dirs(
        $adds = ''
    )
    {
        $adds_ary = explode(",", $adds);
        if (is_array($adds_ary)) {
            $this->ImgDir = (is_array($this->ImgDir)) ? @array_merge($adds_ary, $this->ImgDir) : $adds_ary;
        }
    }


    /**
     *    Read function
     *    reader(filename);
     */
    function reader(
        $filename
    )
    {
        $get_fun = @get_defined_functions();
        return (in_array('file_get_contents', $get_fun['internal'])) ? @file_get_contents($filename) : @implode("", @file($filename));
    }


    /**
     *    Write function
     *    writer(filename,data input, Write mode);
     */
    function writer(
        $filename,
        $data = '',
        $mode = 'w'
    )
    {
        if (trim($filename)) {
            $file = @fopen($filename, $mode);
            $filedata = @fwrite($file, $data);
            @fclose($file);
        }
        if (!is_file($filename)) {
            die('Sorry,' . $filename . ' file write in failed!');
        }
    }


    /**
     *    Template system introduced
     *    察看currentuseoftemplateas well asdebugginginformation
     */
    function inc_list()
    {
        if (is_array($this->IncList)) {
            $EXTS = explode("/", $this->Server['REQUEST_URI']);
            $Last = count($EXTS) - 1;
            //deal withCleanup START
            if (strrpos($EXTS[$Last], 'Ease_Templatepage=Clear') && trim($EXTS[$Last]) != '') {
                $dir_name = $this->CacheDir;
                if (file_exists($dir_name)) {
                    $handle = @opendir($dir_name);
                    while ($tmp_file = @readdir($handle)) {
                        if (@file_exists($dir_name . $tmp_file)) {
                            @unlink($dir_name . $tmp_file);
                        }
                    }
                    @closedir($handle);
                }
                $GoURL = urldecode(preg_replace("/.+?REFERER=(.+?)!!!/", "\\1", $EXTS[$Last]));

                die('<script language="javascript" type="text/javascript">location="' . urldecode($GoURL) . '";</script>');
            }
            //deal withCleanup END

            $list_file = array();
            $file_nums = count($this->IncList);
            $AllSize = 0;
            foreach ($this->IncList AS $Ks => $Vs) {
                $FSize[$Ks] = @filesize($this->TemplateDir . $Vs);
                $AllSize += $FSize[$Ks];
            }

            foreach ($this->IncList AS $K => $V) {
                $File_Size = @round($FSize[$K] / 1024 * 100) / 100 . 'KB';
                $Fwidth = @floor(100 * $FSize[$K] / $AllSize);
                $list_file[] = "<tr><td colspan=\"2\" bgcolor=\"#F7F7F7\" title='" . $Fwidth . "%'><a href='" . $this->TemplateDir . $V . "' target='_blank'><font color='#6F7D84' style='font-size:14px;'>" . $this->TemplateDir . $V . "</font></a>
				<font color='#B4B4B4' style='font-size:10px;'>" . $File_Size . "</font>
				<table border='1' width='" . $Fwidth . "%' style='border-collapse: collapse' bordercolor='#89A3ED' bgcolor='#4886B3'><tr><td></td></tr></table></td></tr>";
            }

            //connectionaddress
            $BackURL = preg_replace("/.+\//", "\\1", $this->Server['REQUEST_URI']);
            $NowPAGE = 'http://' . $this->Server['HTTP_HOST'] . $this->Server['SCRIPT_NAME'];
            $clear_link = $NowPAGE . "?Ease_Templatepage=Clear&REFERER=" . urlencode($BackURL) . "!!!";
            $sf13 = ' style="font-size:13px;color:#666666"';
            echo '<br><table border="1" width="100%" cellpadding="3" style="border-collapse: collapse" bordercolor="#DCDCDC">
<tr bgcolor="#B5BDC1"><td><font color=#000000 style="font-size:16px;"><b>Include Templates (Num:' . count($this->IncList) . ')</b></font></td>
<td align="right">';

            if ($this->RunType == 'Cache') {
                echo '[<a onclick="alert(\'Cache file is successfully deleted\');location=\'' . $clear_link . '\';return false;" href="' . $clear_link . '"><font' . $sf13 . '>Clear Cache</font></a>]';
            }

            echo '</td></tr><tr><td colspan="2" bgcolor="#F7F7F7"><table border="0" width="100%" cellpadding="0" style="border-collapse: collapse">
<tr><td' . $sf13 . '>Cache File ID: <b>' . substr($this->TplID, 0, -1) . '</b></td>
<td' . $sf13 . '>Index: <b>' . ((count($this->FileList) == 0) ? 'False' : 'True') . '</b></td>
<td' . $sf13 . '>Format: <b>' . $this->Ext . '</b></td>
<td' . $sf13 . '>Cache: <b>' . ($this->RunType == 'MemCache' ? 'Memcache Engine' : ($this->RunType == 'Replace' ? 'Replace Engine' : $this->CacheDir)) . '</b></td>
<td' . $sf13 . '>Template: <b>' . $this->TemplateDir . '</b></td></tr>
</table></td></tr>' . implode("", $list_file) . "</table>";
        }
    }

}

?>