<?php

namespace Api\Controller;

class KycController extends CommonController
{
    public function profile()
    {
        // checks if user is logged in 
        $uid = $this->userid();
        if (IS_POST) {
            $input = I('request.');
            $update['firstname'] = $input['firstname'];
            $update['lastname'] = $input['lastname'];
            $update['country'] = $input['country'];
            $update['state'] = $input['state'];
            $update['city'] = $input['city'];
            $update['address'] = $input['address'];
            $update['zip'] = $input['zip'];
            $update['gender'] = (int)$input['gender'];
            $update['dob'] = $input['dob'];
            if (!check($update['address'], 'mostregex')) {
                $this->error('Please check if address was provided');
            }
            if (!check($update['firstname'], 'english')) {
                $this->error('Please check if first name was provided');
            }
            if (!check($update['lastname'], 'english')) {
                $this->error('Please check if lastname was provided');
            }
            if (!check($update['country'], 'english')) {
                $this->error('Please check if country was provided');
            }
            if (!check($update['state'], 'english')) {
                $this->error('Please check if state was provided');
            }
            if (!check($update['city'], 'english')) {
                $this->error('Please check if city was provided');
            }
            if (!check($update['zip'], 'idcard')) {
                $this->error('Please check if zip was provided');
            }

            if (!in_array($update['gender'], [0,1,2,3])) {
                $this->error('Please check if gender was provided:'.$update['gender']);
            }

            $pattern = "/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d\d$/";
            if (!preg_match($pattern, $update['dob'])) {
            $this->error('Please check if dob was provided');
            } 
            $update['truename']  =$update['firstname'].' '.$update['lastname'];
            $save = M('User')->where(array('id' => $uid))->save($update);

            if ($save) {
                $resp['status'] = 1;
            } else {
                $resp['status'] = 0;
            }
            $this->ajaxShow($resp);
        } else {
            //@todo: show profile information
            $user_all_info = M('User')->where(array('id' => $uid))->find();
            $show['firstname'] = $user_all_info['firstname'];
            $show['lastname'] = $user_all_info['lastname'];
            $show['country'] = $user_all_info['country'];
            $show['state'] = $user_all_info['state'];
            $show['city'] = $user_all_info['city'];
            $show['address'] = $user_all_info['address'];
            $show['zip'] = $user_all_info['zip'];
            $show['gender'] = $user_all_info['gender'];
            $show['dob'] = $user_all_info['dob'];
            $show['truename']  =$user_all_info['firstname'].' '.$user_all_info['lastname'];
            $resp['status'] = 1;
            $resp['data'] = $show;
            $this->ajaxShow($resp);
        }
    }
    
    public function docupload()
    {
        // checks if user is logged in 
        $uid = $this->userid();
        if (IS_POST) {
            $input = I('request.');
            $update['frontphoto'] = $input['frontphoto'];
            $update['backphoto'] = $input['backphoto'];
            $update['selfiephoto'] = $input['selfiephoto'];
            $update['idcard'] = $input['idcard'];
            $update['idtype'] = $input['idtype'];

            if (!$update['frontphoto']) {
                $this->error('We had issues receiving your id front photo');
            }
            if (!$update['backphoto']) {
                $this->error('We had issues receiving your id back photo');
            }
            if (!$update['selfiephoto']) {
                $this->error('We had issues receiving your selfie with id proof');
            }

            if (!check($update['idcard'], 'mostregex')) {
                $this->error('Please check if id number was provided');
            }

            if (!check($update['idcard'], 'mostregex')) {
                $this->error('Please check if id type was provided');
            }
            $img1 = pathinfo($update['frontphoto'])['basename'];
            $img2 = pathinfo($update['backphoto'])['basename'];

            $img3 = pathinfo($$update['selfiephoto'])['basename'];
            $img_path = $img1 . '_' . $img2 . '_' . $img3;

            $save = M('User')->where(array('id' => $uid))->save(array('idcardimg1' => $img_path, 'idcardinfo' => $update['idtype'], 'idcardauth' => 2, 'idcard' => $update['idcard']));

            if ($save) {
                $resp['status'] = 1;
            } else {
                $resp['status'] = 0;
            }

            $this->ajaxShow($resp);
        } else {
            $this->error('Invalid request');
        }
    }

    public function status()
    {
        $uid =$this->userid();
        $user = M('User')->where(array('id' => $uid))->find();
        $res['idcard'] = mb_substr($user['idcard'], 0, 6) . '******' . mb_substr($user['idcard'], -4);
        $res['addtime'] = addtime($user['addtime']);
        $res['kyc_status'] = $user['idcardauth'];
        $res['kyc_comment'] = $user['kyc_comment'];
        $this->ajaxShow($res);
    }
    public function imageupload($type = 'default', $base64image)
    {
        $uid = $this->userid();

        $path = './Upload/idcard/';
        $info = $this->uploadImage($base64image, $path);
        //$exts = array('jpg', 'gif', 'png', 'jpeg','pdf');
        $this->ajaxShow($info);
    }

    private function uploadImage($base64img, $path)
    {
        $uid = $this->userid();
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64img));
        $time = time();

        $filepath = $path . $uid . '-' . $time . '.' . $this->getFileType($base64img);

        if (file_put_contents($filepath, $data)) {
            $resp['status'] = 1;
            $resp['path'] = SITE_URL . $filepath;
        } else {
            $resp['status'] = 0;
            $resp['path'] = '';
        }

        return $resp;
    }

    private function getFileType($data)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'pdf'])) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            } else {
                return $type;
            }
        } else {
            throw new \Exception('did not match data URI with image data');
            return false;
        }
    }
}
