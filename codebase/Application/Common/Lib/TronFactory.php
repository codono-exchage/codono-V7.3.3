<?php
namespace Application\Common\Lib;

class TronHandler {
    private $message;

    public function __construct($msg) {
        $this->message = $msg;
    }

    public function handle() {
        return "Hello " . $this->message;
    }
}
