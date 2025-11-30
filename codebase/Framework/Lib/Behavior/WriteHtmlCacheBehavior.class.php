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
 * System behavior extension：Static stateCacheWrite
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class WriteHtmlCacheBehavior extends Behavior
{

    // Behavior extensionofExecution entrymust berun
    public function run(&$content)
    {
        if (C('HTML_CACHE_ON') && defined('HTML_FILE_NAME')) {
            //Static stateFile Write
            // If you turnHTMLFeatures Check and rewriteHTMLfile
            // Nostencilofoperating不FormStatic statefile
            if (!is_dir(dirname(HTML_FILE_NAME)))
                mkdir(dirname(HTML_FILE_NAME), 0755, true);
            if (false === file_put_contents(HTML_FILE_NAME, $content))
                throw_exception(L('_CACHE_WRITE_ERROR_') . ':' . HTML_FILE_NAME);
        }
    }
}