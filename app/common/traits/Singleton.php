<?php


namespace app\common\traits;


use app\utils\ResponseCode;
use think\Response;

trait Singleton
{
    public static $instance;
    public function __construct(){}

    public static function getInstance()
    {
        if( !(self::$instance instanceof static) ){
            self::$instance = new static();
        }
        return self::$instance;
    }

}