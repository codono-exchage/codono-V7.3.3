<?php
namespace Admin\Model;
use Think\Model;

class ActionModel extends Model {

    /* Automatic verification rules */
    protected $_validate = array(
        array('name', 'require', 'Behavior identification must', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('name', '/^[a-zA-Z]\w{0,39}$/', 'Identify illegal', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('name', '', 'name already exists', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
        array('title', 'require', 'The title can not be blank', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('title', '1,80', 'Title length cannot exceed 80 characters', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
        array('remark', 'require', 'Behavior description cannot be empty', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('remark', '1,140', 'Behavior description cannot exceed 140 characters', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
    );

    /* Auto-complete rules */
    protected $_auto = array(
        array('status', 1, self::MODEL_INSERT, 'string'),
        array('update_time', 'time', self::MODEL_BOTH, 'function'),
    );

    /**
     * Add or update a behavior
     * @return boolean false fail ， int  Success returns complete data
     */
    public function update(){
        /* Get data object*/
        $data = $this->create($_POST);
        if(empty($data)){
            return false;
        }

        /* Add or add behavior */
        if(empty($data['id'])){ //Add data
            $id = $this->add(); //Add behavior
            if(!$id){
                $this->error = 'Error in adding behavior!';
                return false;
            }
        } else { //update data
            $status = $this->save(); //Update basic content
            if(false === $status){
                $this->error = 'Error in update behavior!';
                return false;
            }
        }
        //Delete cache
        S('action_list', null);

        //Content addition or update completed
        return $data;

    }

}