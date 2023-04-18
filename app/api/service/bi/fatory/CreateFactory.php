<?php


namespace app\api\service\bi\fatory;
use app\api\service\bi\yinliu\YinliuDataService;

class CreateFactory
{
    public static function createService($type, $args = []) {
        switch ($type) {
            case 'yinliu':
                return new YinliuDataService($args);
            default:
                throw new \InvalidArgumentException('Invalid shape type');
        }
    }
}