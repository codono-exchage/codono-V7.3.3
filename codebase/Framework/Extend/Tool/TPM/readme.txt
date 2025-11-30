ConfigurationprojectConfigurationfile：  
'LAYOUT_ON'=>true

In the projectConfAdd Files directory tags.php
<?php
return array(
 'action_begin'=>array('SwitchMobileTpl')
)


willTplfilefoldercopy toprojectin，As aprojectoftemplate。

willSwitchMobileTplBehavior.class.php copy to projecttable of Contents下 Lib/Behavior Under contents.

willTemplateMobile.class.php Copy the file to ThinkPHP/Extend/Driver/Template under. 


stand byMobile clientJump，needmodifycorefile ThinkPHP/Common/functions.php Get inredirectfunction，
Modify as follows:


function redirect($url, $time=0, $msg='') {
    //Multi-lineURLAddress Support
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "System will{$time}After the second jump to automatically{$url}!";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
           //Mobile client sends Jumpredirectofheader
            if(defined('IS_CLIENT') && IS_CLIENT){
                if(''!==__APP__){
                    $url=substr($url,strlen(__APP__));
                }
                header('redirect:'.$url);
            }else{
                header('Location: ' . $url);
            }
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

