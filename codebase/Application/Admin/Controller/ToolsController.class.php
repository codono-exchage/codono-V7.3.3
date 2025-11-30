<?php

namespace Admin\Controller;
use Think\Page;
class ToolsController extends AdminController
{
    public function index()
    {
        $this->title='Tools';
        $size = $this->getDirSize(RUNTIME_PATH);
        $caching = $this->cacheInfo();
        $this->assign('caching', $caching);
        $this->assign('cacheSize', round($size / pow(1024, $i = floor(log($size, 1024))), 2));

        $this->display();
    }


    public function debug($name = NULL, $field = NULL, $status = NULL,$type=0,$order='dsc')
    {

        $p = 1; $r = 15;
        $parameter=array();
        $input=I('get.');


        if ($type) {
            $map['type'] = $type;
        }

        if ($field && $name) {
                $map[$field] = $name;
        }


        $parameter['p'] = $p;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $count = M('Debug')->count();
        $Page = new Page($count, 15);
        $show = $Page->show();

        $safety = M('Debug')->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $builder = new BuilderList();


        $builder->title('Debug Log');
        $builder->titleList('Debug Log', U('Tools/debug'));
        $builder->button('del', 'Delete', U('Tools/debugDel'));

        $builder->button('delete', 'Delete', U('User/status', array('model' => 'Debug','type'=>'del')));
        $builder->keyText('id', 'id');
        $builder->keyText('title', 'Keyword');
        $builder->keyTime('addtime', 'Time');
        $builder->keyText('code', 'Error');
        $builder->keyText('status', 'Status');
        $builder->keyDoAction('Tools/debugDel?type=delete&&id=###', 'Delete', 'Option');
        $builder->data($safety);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function debugDel()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please choose log to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('is_bad' => 0);
                break;

            case 'resume':
                $data = array('is_bad' => 1);
                break;

            case 'delete':
                $rs = M('Debug')->where($where)->select();

                if (M('Debug')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Debug')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function safety($p = 1, $r = 15)
{
    $p = I('get.p', 1, 'intval'); // Get current page number, default is 1
    $r = I('get.r', 15, 'intval');; // Number of records per page

    $count = M('Safety')->count(); // Total number of records in Safety
    $Page = new \Think\Page($count, $r); // Create a new Page instance
    $show = $Page->show(); // Generate pagination links

    // Fetch subset of records for current page
    $safety = M('Safety')->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

    $builder = new BuilderList();
    $builder->title('Activity Screen');
    $builder->titleList('Activity Screen', U('Tools/safety'));
    $builder->button('del', 'Delete', U('Tools/safetyDel'));

    $builder->keyText('id', 'id');
    $builder->keyText('email', 'email');
    $builder->keyTime('addtime', 'Time');
    $builder->keyText('is_bad', 'Bad Detection');
    $builder->keyText('comment', 'Comment');
    $builder->keyDoAction('Tools/safetyDel?type=delete&&id=###', 'Delete', 'Option');

    $builder->data($safety);
    $builder->pagination($count, $r, []);; // Add pagination

    $builder->display();
								
							
}

    public function safetyDel()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please choose log to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('is_bad' => 0);
                break;

            case 'resume':
                $data = array('is_bad' => 1);
                break;

            case 'delete':
                $rs = M('Safety')->where($where)->select();

                if (M('Safety')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Safety')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function register()
    {
        $p = I('get.p', 1, 'intval');
        $r = I('get.r', 15, 'intval');
        $count = M('Signup')->count();
        $Page = new Page($count, 15);
        $show = $Page->show();

        $safety = M('Signup')->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $builder = new BuilderList();


        $builder->title('Signup Activity');
        $builder->titleList('Signup Activity', U('Tools/delRegister'));
        $builder->button('del', 'Delete', U('Tools/delRegister'));


        $builder->keyText('id', 'id');
        $builder->keyText('email', 'email');
        $builder->keyText('username', 'Username');
        $builder->keyTime('addtime', 'Time');
        $builder->keyText('addip', 'IP');

        $builder->keyDoAction('Tools/delRegister?type=delete&&id=###', 'Delete', 'Option');
        $builder->data($safety);
        $builder->pagination($count, $r, []);; // Add pagination
        $builder->display();
    }
    public function delRegister()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please choose log to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('is_bad' => 0);
                break;

            case 'resume':
                $data = array('is_bad' => 1);
                break;

            case 'delete':
                $rs = M('Signup')->where($where)->select();

                if (M('Signup')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Signup')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function cacheInfo()
    {
        S('rediscaching', 'working');
        F('filecaching', 'working');

        $redis = S('rediscaching') ? S('rediscaching') : 'Not working';
        $file = F('filecaching') ? F('filecaching') : 'Not working';
        return array('redis' => $redis, 'file' => $file);

    }

    public function delcahe()
    {
        $size = $this->getDirSize(RUNTIME_PATH);
        $this->assign('cacheSize', round($size / pow(1024, $i = floor(log($size, 1024))), 2));
        $this->display();
    }

    public function ReadConfig()
    {
        exit("Enable it from File:" . __FILE__ . ' and line:' . __LINE__);
        $i = 1;
        $constarray = get_defined_constants(true);
        $defined = $constarray['user'];

        foreach ($defined as $key => $value) {
            if ($key == 'THINK_VERSION') {
                break;
            }
            switch ($key) {
                case 'DB_PWD':
                    break;
                default:
                    $data[$i] = array('name' => $key, 'value' => json_encode($value));
            }
            $i++;
        }
        $builder = new BuilderList();

        $builder->keyText('name', 'Name');
        $builder->keyText('value', 'Value');
        $builder->data($data);
        $builder->display();
    }

    protected function getDirSize($dir)
    {
        $sizeResult = 0;
        $handle = opendir($dir);

        while (false !== $FolderOrFile = readdir($handle)) {
            if (($FolderOrFile != '.') && ($FolderOrFile != '..')) {
                if (is_dir($dir . '/' . $FolderOrFile)) {
                    $sizeResult += $this->getDirSize($dir . '/' . $FolderOrFile);
                } else {
                    $sizeResult += filesize($dir . '/' . $FolderOrFile);
                }
            }
        }

        closedir($handle);
        return $sizeResult;
    }

    public function delcache()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
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
        $this->success('Clear System Cache success!');
    }

    public function invoke()
    {

        $dirs = array(RUNTIME_PATH);
        @(mkdir(RUNTIME_PATH, 511, true));

        foreach ($dirs as $value) {
            $this->rmdirr($value);
        }

        @(mkdir(RUNTIME_PATH, 511, true));
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

    public function dataExport()
    {

        redirect('/Admin/Tools/database?type=export');
    }

    public function dataImport()
    {

        redirect('/Admin/Tools/database?type=import');
    }

    public function database($type = NULL)
    {
        exit(json_encode(array('msg' => "Comment Line:" . __LINE__ . ' to start this tool , Use only if you know what it does ', 'status' => 0)));

        switch ($type) {
            case 'import':
                $path = realpath(DATABASE_PATH);
                $glob = self::FilesystemIterator($path);
                $list = array();
                for ($i = 0; $i < count($glob); $i++) {
                    $name = $glob[$i];
                    $a = str_replace(".sql.gz", "", $glob[$i]);
                    $lv = explode("-", $a);
                    if (preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql(?:\\.gz)?$/', $name)) {
                        $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                        $date = $name[0] . '-' . $name[1] . '-' . $name[2];
                        $time = $name[3] . ':' . $name[4] . ':' . $name[5];
                        $part = $name[6];

                        $list[$i]['time'] = strtotime($date . " " . $time);
                        $list[$i]['part'] = $lv[2];
                        $list[$i]['size'] = filesize($path . "/" . $glob[$i]) . "B";
                        $list[$i]['key'] = $date . " " . $time;
                    }
                }
                break;

            case 'export':
                $Db = \Think\Db::getInstance();
                $list = $Db->query('SHOW TABLE STATUS');
                $list = array_map('array_change_key_case', $list);
                $title = 'data backup';
                break;

            default:
                $this->error(L('INCORRECT_REQ'));
        }

        $this->assign('meta_title', $title);
        $this->assign('list', $list);
        $this->display($type);
    }

    function FilesystemIterator($dir)
    {
        //all files under the PHP Traversal file folder
        $handle = opendir($dir . ".");
//definitionForstoragefilenameArray 
        $array_file = array();
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $array_file[] = $file; //Exportfilename
            }
        }
        closedir($handle);
        for ($i = 0; $i < count($array_file); $i++) {
            if (strstr($array_file[$i], '.sql.gz')) {
                $date[] = $array_file[$i];
            }
        }

        return $date;
    }

    public function optimize($tables = NULL)
    {
        die(__LINE__);
        if ($tables) {
            $Db = \Think\Db::getInstance();

            if (is_array($tables)) {
                $tables = implode('`,`', $tables);
                $list = $Db->query('OPTIMIZE TABLE `' . $tables . '`');

                if ($list) {
                    $this->success('Data Sheet optimization is complete!');
                } else {
                    $this->error('Data table optimization error. Please try again!');
                }
            } else {
                $list = $Db->query('OPTIMIZE TABLE `' . $tables . '`');

                if ($list) {
                    $this->success('data sheet\'' . $tables . '\'Optimization is complete!');
                } else {
                    $this->error('data sheet\'' . $tables . '\'Optimization error. Please try again!');
                }
            }
        } else {
            $this->error('Please specify the optimization of the table!');
        }
    }

    public function repair($tables = NULL)
    {
        die();
        if ($tables) {
            $Db = \Think\Db::getInstance();

            if (is_array($tables)) {
                $tables = implode('`,`', $tables);
                $list = $Db->query('REPAIR TABLE `' . $tables . '`');

                if ($list) {
                    $this->success('Data sheet repair is complete!');
                } else {
                    $this->error('Data Sheet fix the error. Please try again!');
                }
            } else {
                $list = $Db->query('REPAIR TABLE `' . $tables . '`');

                if ($list) {
                    $this->success('data sheet\'' . $tables . '\'Repair is complete!');
                } else {
                    $this->error('data sheet\'' . $tables . '\'Repair Error Please try again!');
                }
            }
        } else {
            $this->error('Please specify the table to be repaired!');
        }
    }

    public function del($time = 0)
    {
        die();
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if ($time) {
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR . $name;
            array_map('unlink', glob($path));

            if (count(glob($path))) {
                $this->success('Backup file deletion failed, please check permissions!');
            } else {
                $this->success('Backup file deleted successfully!');
            }
        } else {
            $this->error(L('INCORRECT_REQ'));
        }
    }

    public function export($tables = NULL, $id = NULL, $start = NULL)
    {
        exit(json_encode(array('msg' => "Comment Line:" . __LINE__ . ' to start this tool , Use only if you know what it does ', 'status' => 0)));
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (C('web_close')) {
            $this->error('Please close the site and then back up the database!');
        }

        if (IS_POST && !empty($tables) && is_array($tables)) {
            $config = array('path' => realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR, 'part' => 20971520, 'compress' => 1, 'level' => 9);
            $lock = $config['path'] . 'backup.lock';

            if (is_file($lock)) {
                $this->error('Detects that there is a backup task is being executed, please try again later!');
            } else {
                file_put_contents($lock, NOW_TIME);
            }

            is_writeable($config['path']) || $this->error('Backup directory does not exist or is not writable. Please check retry!');
            session('backup_config', $config);
            $file = array('name' => date('Ymd-His', NOW_TIME), 'part' => 1);
            session('backup_file', $file);
            session('backup_tables', $tables);
            $Database = new \OT\Database($file, $config);

            if (false !== $Database->create()) {
                $tab = array('id' => 0, 'start' => 0);
                $this->success('Initialization successful!', '', array('tables' => $tables, 'tab' => $tab));
            } else {
                $this->error('Failed to initialize the backup file creation failed!');
            }
        } else if (IS_GET && is_numeric($id) && is_numeric($start)) {
            $tables = session('backup_tables');
            $Database = new \OT\Database(session('backup_file'), session('backup_config'));
            $start = $Database->backup($tables[$id], $start);

            if (false === $start) {
                $this->error('Backup Error!');
            } else if (0 === $start) {
                if (isset($tables[++$id])) {
                    $tab = array('id' => $id, 'start' => 0);
                    $this->success('The backup is complete!', '', array('tab' => $tab));
                } else {
                    unlink(session('backup_config.path') . 'backup.lock');
                    session('backup_tables', null);
                    session('backup_file', null);
                    session('backup_config', null);
                    $this->success('The backup is complete!');
                }
            } else {
                $tab = array('id' => $id, 'start' => $start[0]);
                $rate = floor(100 * ($start[0] / $start[1]));
                $this->success('They are backed up...(' . $rate . '%)', '', array('tab' => $tab));
            }
        } else {
            $this->error(L('INCORRECT_REQ'));
        }
    }

    public function import($time = 0, $part = NULL, $start = NULL)
    {
        die();
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (C('web_close')) {
            $this->error('Turn off the Web site and then restore the database!');
        }

        if (is_numeric($time) && is_null($part) && is_null($start)) {
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR . $name;
            $files = glob($path);
            $list = array();

            foreach ($files as $name) {
                $basename = basename($name);
                $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                $gz = preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql.gz$/', $basename);
                $list[$match[6]] = array($match[6], $name, $gz);
            }

            ksort($list);
            $last = end($list);

            if (count($list) === $last[0]) {
                session('backup_list', $list);
                $this->success('loading finished!', '', array('part' => 1, 'start' => 0));
            } else {
                $this->error('Backup files may be damaged, please check!');
            }
        } else if (is_numeric($part) && is_numeric($start)) {
            $list = session('backup_list');
            $db = new \OT\Database($list[$part], array('path' => realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR, 'compress' => 1, 'level' => 9));
            $start = $db->import($start);

            if (false === $start) {
                $this->error('Restore data error!');
            } else if (0 === $start) {
                if (isset($list[++$part])) {
                    $data = array('part' => $part, 'start' => 0);
                    $this->success('Restoring...#' . $part, '', $data);
                } else {
                    session('backup_list', null);
                    $this->success('Restore complete!');
                }
            } else {
                $data = array('part' => $part, 'start' => $start[0]);

                if ($start[1]) {
                    $rate = floor(100 * ($start[0] / $start[1]));
                    $this->success('Restoring...#' . $part . ' (' . $rate . '%)', '', $data);
                } else {
                    $data['gz'] = 1;
                    $this->success('Restoring...#' . $part, '', $data);
                }
            }
        } else {
            $this->error(L('INCORRECT_REQ'));
        }
    }

    public function excel($tables = NULL)
    {
        exit(json_encode(array('msg' => "Comment Line:" . __LINE__ . ' to start this tool , Use only if you know what it does ', 'status' => 0)));

        if ($tables) {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $mo = M();

            $mo->startTrans();
            $rs = $mo->table($tables)->select();
            $zd = $mo->table($tables)->getDbFields();

            if ($rs) {
                $mo->commit();
                // removed unlock/lock
            } else {
                $mo->rollback();
            }

            $xlsName = $tables;
            $xls = array();

            foreach ($zd as $k => $v) {
                $xls[$k][0] = $v;
                $xls[$k][1] = $v;
            }
            /*  echo $xlsName;
              echo '<pre>';
              print_r($xls);
              echo '</pre><pre>';
              print_r($rs);
              echo "</pre>";
              exit();*/
            return $this->exportExcel($xlsName, $xls, $rs);
        } else {
            $this->error('Please specify a table to export!');
        }
    }

    public function exportExcel($expTitle, $expCellName, $expTableData)
    {

        import('Org.Util.PHPExcel') or die('22222');
        import('Org.Util.PHPExcel.Writer.Excel5');
        import('Org.Util.PHPExcel.IOFactory');
        $xlsTitle = iconv('utf-8', 'utf-8', $expTitle);
        $fileName = $_SESSION['loginAccount'] . date('_YmdHis');
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time:' . date('Y-m-d H:i:s'));
        $i = 0;

        for (; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }

        $i = 0;

        for (; $i < $dataNum; $i++) {
            $j = 0;

            for (; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }

        ob_end_clean();
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    public function importExecl($file)
    {
        die();
        import('Org.Util.PHPExcel');
        import('Org.Util.PHPExcel.Writer.Excel5');
        import('Org.Util.PHPExcel.IOFactory.php');

        if (!file_exists($file)) {
            return array('error' => 0, 'message' => 'file not found!');
        }

        $objReader = \PHPExcel_IOFactory::createReader('Excel5');

        try {
            $PHPReader = $objReader->load($file);
        } catch (\Exception $e) {
        }

        if (!file_exists($file)) {
            return array('error' => 0, 'message' => 'read error!');
        }

        $allWorksheets = $PHPReader->getAllSheets();
        $i = 0;

        foreach ($allWorksheets as $objWorksheet) {
            $sheetname = $objWorksheet->getTitle();
            $allRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $allColumn = \PHPExcel_Cell::columnIndexFromString($highestColumn);
            $array[$i]['Title'] = $sheetname;
            $array[$i]['Cols'] = $allColumn;
            $array[$i]['Rows'] = $allRow;
            $arr = array();
            $isMergeCell = array();

            foreach ($objWorksheet->getMergeCells() as $cells) {
                foreach (\PHPExcel_Cell::extractAllCellReferencesInRange($cells) as $cellReference) {
                    $isMergeCell[$cellReference] = true;
                }
            }

            $currentRow = 1;

            for (; $currentRow <= $allRow; $currentRow++) {
                $row = array();
                $currentColumn = 0;

                for (; $currentColumn < $allColumn; $currentColumn++) {
                    $cell = $objWorksheet->getCellByColumnAndRow($currentColumn, $currentRow);
                    $afCol = \PHPExcel_Cell::stringFromColumnIndex($currentColumn + 1);
                    $bfCol = \PHPExcel_Cell::stringFromColumnIndex($currentColumn - 1);
                    $col = \PHPExcel_Cell::stringFromColumnIndex($currentColumn);
                    $address = $col . $currentRow;
                    $value = $objWorksheet->getCell($address)->getValue();

                    if (substr($value, 0, 1) == '=') {
                        return array('error' => 0, 'message' => 'can not use the formula!');
                        exit();
                    }

                    if ($cell->getDataType() == \PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                        $cellstyleformat = $cell->getParent()->getStyle($cell->getCoordinate())->getNumberFormat();
                        $formatcode = $cellstyleformat->getFormatCode();

                        if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
                            $value = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($value));
                        } else {
                            $value = \PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
                        }
                    }

                    if ($isMergeCell[$col . $currentRow] && $isMergeCell[$afCol . $currentRow] && !empty($value)) {
                        $temp = $value;
                    } else if ($isMergeCell[$col . $currentRow] && $isMergeCell[$col . ($currentRow - 1)] && empty($value)) {
                        $value = $arr[$currentRow - 1][$currentColumn];
                    } else if ($isMergeCell[$col . $currentRow] && $isMergeCell[$bfCol . $currentRow] && empty($value)) {
                        $value = '';
                    }

                    $row[$currentColumn] = $value;
                }

                $arr[$currentRow] = $row;
            }

            $array[$i]['Content'] = $arr;
            $i++;
        }

        spl_autoload_register(array('Think', 'autoload'));
        unset($objWorksheet);
        unset($PHPReader);
        unset($PHPExcel);
        unlink($file);
        return array('error' => 1, 'data' => $array);
    }

   

    public function wallet($id = NULL)
    {
        $s_key = md5('ToolsWallet');
        $qb_list = S($s_key);

        if (!$qb_list) {
            $qb_list = M('Coin')->where(array('type' => 'qbb'))->select();
            S($s_key, $qb_list);
        }

        if ($id === null) {
            S($s_key, null);
            $this->assign('list_len', count($qb_list));
            $this->display();
            exit();
        }

        if ($id == -1) {
            $dirs = array(RUNTIME_PATH);
            @(mkdir(RUNTIME_PATH, 511, true));

            foreach ($dirs as $value) {
                $this->rmdirr($value);
            }

            @(mkdir(RUNTIME_PATH, 511, true));
            echo json_encode(array('status' => 1, 'info' => 'Cache successfully cleared'));
            exit();
        }

        $update_str = '&nbsp;&nbsp;&nbsp;<a href="' . U('Coin/edit', array('id' => $qb_list[$id]['id'])) . '" color="green" target="_black">Immediately to modify<a>';

        if (isset($qb_list[$id])) {
            if ($qb_list[$id]['status']) {
                if ($qb_list[$id]['zr_dz'] <= 0) {
                    echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . 'Confirm the number can not be empty wallet' . $update_str));
                    exit();
                }

                if ($qb_list[$id]['zc_zd'] <= 10) {
                    echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . 'wallet automatic withdraw Limit is too small,Suggest with 10 More' . $update_str));
                    exit();
                }

                //zj  IP  Dk  port    yh user  mm password

                $CoinClient = CoinClient($qb_list[$id]['dj_yh'], $qb_list[$id]['dj_mm'], $qb_list[$id]['dj_zj'], $qb_list[$id]['dj_dk'], 3, array(), 1);
                $json = $CoinClient->getinfo();

                if ($json) {
                    if ($tmp = json_decode($json, true)) {
                        $json = $tmp;
                    }
                }

                if (!isset($json['version']) || !$json['version']) {
                    echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . 'Server returned an error:' . $json['data'] . $update_str));
                    exit();
                } else {
                    echo json_encode(array('status' => 1, 'info' => $qb_list[$id]['title'] . 'Operating normally'));
                    exit();
                }
            } else {
                echo json_encode(array('status' => -1, 'info' => $qb_list[$id]['title'] . ' have disabled,Without checking'));
            }
        } else {
            echo json_encode(array('status' => 100, 'info' => 'All checked and'));
            exit();
        }
    }

  
	public function restartSocket(){
		
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
}