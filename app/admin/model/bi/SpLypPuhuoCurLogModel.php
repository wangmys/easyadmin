<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoCurLogModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_cur_log';

    const RULE_Type = [
        'type_a'    => 1, #铺货规则类型A
        'type_b'    => 2 #铺货规则类型B
    ];

    const RULE_Type_TEXT = [
        self::RULE_Type['type_a']  => '铺货规则类型A',
        self::RULE_Type['type_b']  => '铺货规则类型B'
    ];
}
