<?php
namespace Application\Common\Lib;

class CoinFactory {
    public static function getHandler($coinType,$msg) {
        switch ($coinType) {
            case 'tron':
                return new TronHandler($msg);
            // other cases...
            default:
                // Handle unknown type or throw an exception
        }
    }
}