<?php

namespace Admin\Controller;

class MiscController extends AdminController
{
    public function roadmap()
    {

        $Roadmaps = M('Roadmap')->order('id desc')->select();
        $builder = new BuilderList();


        $builder->title('Roadmap');
        $builder->titleList('Roadmap List', U('Misc/roadmap'));
        $builder->button('add', 'Add', U('Misc/editRoadmap'));


        $builder->keyText('id', 'id');
        $builder->keyText('year', 'quarter');
        $builder->keyText('date', 'Date');
        $builder->keyText('text', 'Content');
        $builder->keyStatus('status', 'Status', array('Yet to come', 'Finished', 'Running'));
        $builder->keyDoAction('Misc/editRoadmap?id=###', 'Edit', 'Option');
        $builder->keyDoAction('Misc/deleteRoadmap?id=###', 'Delete', 'Option');
        $builder->data($Roadmaps);
        //$builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function deleteRoadmap($id = NULL)
    {
        $where['id'] = $id;
        if (M('Roadmap')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }

    }

    public function editRoadmap($id = NULL)
    {
        if (!empty($_POST)) {

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!check($_POST['year'], 'a')) {
            }
            if (!check($_POST['date'], 'a')) {
            }

            if (!check($_POST['text'], 'a')) {
                $this->error('Sort malformed');
            }

            if (check($_POST['id'], 'd')) {
                $rs = M('Roadmap')->save($_POST);
            } else {
                $rs = M('Roadmap')->add($_POST);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('Operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Roadmap');
            $builder->titleList('Roadmap List', U('Misc/roadmap'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'Roadmap ID');
                $data = M('Roadmap')->where(array('id' => $id))->find();
                $builder->data($data);
            }

            $builder->keyText('year', 'Year', 'Q2 2020	');
            $builder->keyText('date', 'Date', 'Apr-Jun 2021');
            $builder->keyText('text', 'Content', 'Some Content');
            $builder->keyStatus('status', 'Status', 'Status', array('Yet to come', 'Finished', 'Running'));
            $builder->savePostUrl(U('Misc/editRoadmap'));
            $builder->display();
        }
    }


    public function bonus()
    {

        $Bonus = M('Bonus')->order('id desc')->select();
        $builder = new BuilderList();


        $builder->title('Bonus');
        $builder->titleList('Bonus List', U('Misc/bonus'));
        $builder->button('add', 'Add', U('Misc/editBonus'));


        $builder->keyText('id', 'id');
        $builder->keyText('type', 'Type');
        $builder->keyText('uidstart', 'uidstart');
        $builder->keyText('uidend', 'uidend');
        $builder->keyText('coin', 'Bonus Coin');
        $builder->keyText('amount', 'Bonus Amount');
        $builder->keyStatus('status', 'Status', array('Disabled', 'Active'));
        $builder->keyDoAction('Misc/editBonus?id=###', 'Edit', 'Option');
        $builder->keyDoAction('Misc/deleteBonus?id=###', 'Delete', 'Option');
        $builder->data($Bonus);
        //$builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function deleteBonus($id = NULL)
    {
        $where['id'] = $id;
        if (M('Bonus')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }

    }

    public function editBonus($id = NULL)
    {
        if (!empty($_POST)) {

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!check($_POST['type'], 'a')) {
            }
            if (!check($_POST['uidstart'], 'd')) {
                $this->error('User Seq Start Should be number only');
            }

            if (!check($_POST['uidend'], 'd')) {
                $this->error('User Seq Start Should be number only');
            }
            $_POST['addtime'] = strtotime($_POST['addtime']);
            $_POST['endtime'] = strtotime($_POST['endtime']);

            if (check($_POST['id'], 'd')) {
                $rs = M('Bonus')->save($_POST);
            } else {
                $rs = M('Bonus')->add($_POST);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('Operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Bonus');
            $builder->titleList('Bonus List', U('Misc/Bonus'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'Bonus ID');
                $data = M('Bonus')->where(array('id' => $id))->find();

                $data['addtime'] = addtime($data['addtime']);
                $data['endtime'] = addtime($data['endtime']);

                $builder->data($data);
            }
            $coin_list = D('Coin')->get_all_name_list();

            $builder->keySelect('coin', 'Bonus Coin Name', 'Votes need to deduct currency', $coin_list);
            $builder->keyText('amount', 'Bonus Amount', 'Amount');
            $builder->keySelect('type', 'Type', 'Select Bonus Type', array('kyc' => 'KYC Bonus', 'other' => 'Others'));

            $builder->keyText('uidstart', 'Userid Start', 'UID seq Start');
            $builder->keyText('uidend', 'UserId End', 'UID seq end');

            $builder->keyText('total', 'total', 'Total Amount');
            $builder->keyText('title', 'Title', 'KYC PACK 123');
            $builder->keyText('description', 'Description', 'Some Content');
            $builder->keySelect('status', 'Status', '1= active', array(0, 1));
            //          $builder->keyAddTime();
            $builder->keyTime('addtime', 'Bonus time');
            $builder->keyTime('endtime', 'Bonus End');
            $builder->savePostUrl(U('Misc/editBonus'));
            $builder->display();
        }
    }


}