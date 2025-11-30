<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Behavior;
/**
 * Automate tasks
 */
class CronRunBehavior
{

    public function run(&$params)
    {
        // lockingautomaticcarried out
        $lockfile = RUNTIME_PATH . 'cron.lock';
        if (is_writable($lockfile) && filemtime($lockfile) > $_SERVER['REQUEST_TIME'] - C('CRON_MAX_TIME', null, 60)) {
            return;
        } else {
            touch($lockfile);
        }
        set_time_limit(1000);
        ignore_user_abort(true);

        // Loadingcron Configuration file 
        // format return array(
        // 'cronname'=>array('filename',intervals,nextruntime),...
        // );
        if (is_file(RUNTIME_PATH . '~crons.php')) {
            $crons = include RUNTIME_PATH . '~crons.php';
        } elseif (is_file(COMMON_PATH . 'Conf/crons.php')) {
            $crons = include COMMON_PATH . 'Conf/crons.php';
        }
        if (isset($crons) && is_array($crons)) {
            $update = false;
            $log = array();
            foreach ($crons as $key => $cron) {
                if (empty($cron[2]) || $_SERVER['REQUEST_TIME'] >= $cron[2]) {
                    // Arrivalstime carried outcronfile
                    G('cronStart');
                    include COMMON_PATH . 'Cron/' . $cron[0] . '.php';
                    G('cronEnd');
                    $_useTime = G('cronStart', 'cronEnd', 6);
                    // Updatecronrecording
                    $cron[2] = $_SERVER['REQUEST_TIME'] + $cron[1];
                    $crons[$key] = $cron;
                    $log[] = "Cron:$key Runat " . date('Y-m-d H:i:s') . " Use $_useTime s\n";
                    $update = true;
                }
            }
            if ($update) {
                // recordingCroncarried outJournal
                \Think\Log::write(implode('', $log));
                // Updatecronfile
                $content = "<?php\nreturn " . var_export($crons, true) . ";\n?>";
                file_put_contents(RUNTIME_PATH . '~crons.php', $content);
            }
        }
        // unlock
        unlink($lockfile);
        return;
    }
}