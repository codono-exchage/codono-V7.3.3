<?php
/* 
 * Edition:	ET080708
 * Desc:	ET Template
 * File:	template.ease.php
 * Author:	David Meng
 * Site:	http://www.systn.com
 * Email:	mdchinese@gmail.com
 * 
 */

//Introducedcorefile
if (is_file(dirname(__FILE__) . '/template.core.php')) {
    include dirname(__FILE__) . '/template.core.php';
} else {
    die('Sorry. Not load core file.');
}

Class template extends ETCore
{

    /**
     *    Statement template usage
     */
    function template(
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

        parent::ETCoreStart($set);
    }

}

?>