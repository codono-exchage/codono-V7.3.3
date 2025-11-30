<?php

namespace Home\Controller;

class EmptyController extends HomeController
{

    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo L('Module does not exist!');
        die();

    }


}