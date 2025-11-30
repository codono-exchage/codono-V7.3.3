<?php

namespace Home\Controller;

class ToolsController extends HomeController
{

    
    public function __construct()
    {
        parent::__construct();
        checkcronkey();
    }
	public function restartSocket(){
		cronLastRun(__FUNCTION__);	
		exec('sudo supervisorctl restart all', $output, $return_var);

		// Output the result
		echo implode("\n", $output);
		if ($return_var !== 0) {
        echo "<span style='color:red'>Error executing command!!</span><br/>";
		echo "<small>This command didnt not work <br/>
		Suggested Solution: <br/>
		Goto your ubuntu ssh cli 
		<br/>
		Edit the sudoers file using visudo and add a line like: 
		<br/>
		<i>www ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart all</i>
		<br/></small>";
		}
		
	}

	public function cacheClear()
    {
        cronLastRun(__FUNCTION__);
        $runtimePath = RUNTIME_PATH;
        $dirs = array($runtimePath);
        if (!file_exists($runtimePath)) {
            @(mkdir($runtimePath, 0755, true));
        }
        foreach ($dirs as $value) {
            $this->rmdirr($value);
        }

        @(mkdir($runtimePath, 0755, true));
        if (REDIS_ENABLED == 1) {
            redisAllClear();
        }
        echo '<br/>Clear System Cache success!';
    }
	protected function rmdirr($dirname)
    {
        if (!file_exists($dirname)) {
            return false;
        }

        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        $dir = dir($dirname);

        if ($dir) {
            while (false !== $entry = $dir->read()) {
                if (($entry == '.') || ($entry == '..')) {
                    continue;
                }

                $this->rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
            }
        }

        $dir->close();
        return rmdir($dirname);
    }
}