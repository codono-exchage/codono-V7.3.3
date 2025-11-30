<?php

namespace Admin\Controller;

class MenuController extends AdminController
{
    public function index()
    {
        $pid = I('get.pid', 0);

        if ($pid) {
            $data = M('Menu')->where('id=' . $pid)->field(true)->find();
            $this->assign('data', $data);
        }

        $title = trim(I('get.title'));
        $type = C('CONFIG_GROUP_LIST');
        $all_menu = M('Menu')->getField('id,title');
        $map['pid'] = $pid;

        if ($title) {
            $map['title'] = array('like', '%' . $title . '%');
        }

        $list = M('Menu')->where($map)->field(true)->order('sort asc,id asc')->select();
        int_to_string($list, array(
            'hide' => array(1 => 'Yes', 0 => 'no'),
            'is_dev' => array(1 => 'Yes', 0 => 'no')
        ));

        if ($list) {
            foreach ($list as &$key) {
                if ($key['pid']) {
                    $key['up_title'] = $all_menu[$key['pid']];
                }
            }

            $this->assign('list', $list);
        }

        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->meta_title = 'Menu List';
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $Menu = D('Menu');
            $data = $Menu->create();

            if ($data) {
                $id = $Menu->add();

                if ($id) {
                    //      action_log('update_menu', 'Menu', $id, UID);
                    $this->success('added successfully', Cookie('__forward__'));
                } else {
                    $this->error('Add failed');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $this->assign('info', array('pid' => I('pid')));
            $menus = M('Menu')->field(true)->select();
            $menus = D('Tree')->toFormatTree($menus);
            $menus = array_merge(array(
                array('id' => 0, 'title_show' => 'Top Menu')
            ), $menus);
            $this->assign('Menus', $menus);
            $this->meta_title = 'New menu';
            $this->display('edit');
        }
    }

    public function edit($id = 0)
    {
        if (IS_POST) {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $Menu = D('Menu');
            $data = $Menu->create();

            if ($data) {
                if ($Menu->save() !== false) {
                    action_log('update_menu', 'Menu', $data['id'], UID);
                    $this->success('update completed', Cookie('__forward__'));
                } else {
                    $this->error('Update failed');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $info = array();
            $info = M('Menu')->field(true)->find($id);
            $menus = M('Menu')->field(true)->select();
            $menus = D('Tree')->toFormatTree($menus);
            $menus = array_merge(array(
                array('id' => 0, 'title_show' => 'Top Menu')
            ), $menus);
            $this->assign('Menus', $menus);

            if (false === $info) {
                $this->error('Obtaining background information about the error menu');
            }

            $this->assign('info', $info);
            $this->meta_title = 'Edit menu background';
            $this->display();
        }
    }

    public function del()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        $id = array_unique((array)I('id', 0));

        if (empty($id)) {
            $this->error('please chooseData to be operated!');
        }

        $map = array(
            'id' => array('in', $id)
        );

        if (M('Menu')->where($map)->delete()) {
            action_log('update_menu', 'Menu', $id, UID);
            $this->success('successfully deleted');
        } else {
            $this->error('failed to delete!');
        }
    }

    public function toogleHide($id, $value = 1)
    {
        $this->editRow('Menu', array('hide' => $value), array('id' => $id));
    }

    public function toogleDev($id, $value = 1)
    {
        $this->editRow('Menu', array('is_dev' => $value), array('id' => $id));
    }

    public function importFile($tree = NULL, $pid = 0)
    {
        if ($tree == null) {
            $file = APP_PATH . 'Admin/Conf/Menu.php';
            $tree = require_once $file;
        }

        $menuModel = D('Menu');

        foreach ($tree as $value) {
            $add_pid = $menuModel->add(array('title' => $value['title'], 'url' => $value['url'], 'pid' => $pid, 'hide' => isset($value['hide']) ? (int)$value['hide'] : 0, 'tip' => $value['tip'] ?? '', 'group' => $value['group']));

            if ($value['operator']) {
                $this->import($value['operator'], $add_pid);
            }
        }
    }

    public function import()
    {
        if (IS_POST) {
            $tree = I('post.tree');
            $lists = explode(PHP_EOL, $tree);
            $menuModel = M('Menu');

            if ($lists == array()) {
                $this->error('Please fill out the form as bulk import menu, at least one menu');
            } else {
                $pid = I('post.pid');

                foreach ($lists as $key => $value) {
                    $record = explode('|', $value);

                    if (count($record) == 2) {
                        $menuModel->add(array('title' => $record[0], 'url' => $record[1], 'pid' => $pid, 'sort' => 0, 'hide' => 0, 'tip' => '', 'is_dev' => 0, 'group' => ''));
                    }
                }

                $this->success('Import success', U('index?pid=' . $pid));
            }
        } else {
            $this->meta_title = 'Batch Import menu background';
            $pid = (int)I('get.pid');
            $this->assign('pid', $pid);
            $data = M('Menu')->where('id=' . $pid)->field(true)->find();
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function sort()
    {
        if (IS_GET) {
            $ids = I('get.ids');
            $pid = I('get.pid');
            $map = array(
                'status' => array('gt', -1)
            );

            if (!empty($ids)) {
                $map['id'] = array('in', $ids);
            } else if ($pid !== '') {
                $map['pid'] = $pid;
            }

            $list = M('Menu')->where($map)->field('id,title')->order('sort asc,id asc')->select();
            $this->assign('list', $list);
            $this->meta_title = 'Sort menu';
            $this->display();
        } else if (IS_POST) {
            $ids = I('post.ids');
            $ids = explode(',', $ids);

            foreach ($ids as $key => $value) {
                $res = M('Menu')->where(array('id' => $value))->setField('sort', $key + 1);
            }

            if ($res !== false) {
                $this->success('Sort success!');
            } else {
                $this->error('Sort failed!');
            }
        } else {
            $this->error('Illegal request!');
        }
    }
}