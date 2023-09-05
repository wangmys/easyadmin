<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class CwlDaxiaoHandleModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'cwl_daxiao_handle';

    const store_type = [
        'big'    => '建议偏大', 
        'normal'    => '正常店',
        'small'    => '建议偏小',
    ];

    const store_type_text = [
        self::store_type['big']    => '1', 
        self::store_type['normal']    => '2',
        self::store_type['small']    => '3',
    ];

}
