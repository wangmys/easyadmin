<?php

namespace app\admin\model;


use app\common\model\TimeModel;

class SjpGoodsModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sjp_goods';

}