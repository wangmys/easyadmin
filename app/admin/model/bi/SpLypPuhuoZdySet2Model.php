<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoZdySet2Model extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_zdy_set2';

    const SELECT_TYPE = [
        'much_merge'    => 1, #组合
        'much_store'    => 2, #单店
    ];

    const SELECT_TYPE_TEXT = [
        self::SELECT_TYPE['much_merge']  => '组合',
        self::SELECT_TYPE['much_store']  => '单店',
    ];

    const RULE_TYPE = [
        'type_a'    => 1, #A方案
        'type_b'    => 2 #B方案
    ];

    const RULE_TYPE_TEXT = [
        self::RULE_TYPE['type_a']  => 'A方案',
        self::RULE_TYPE['type_b']  => 'B方案'
    ];

    const REMAIN_STORE = [
        'puhuo'    => 1, #铺
        'no_puhuo'    => 2 #不铺
    ];

    const REMAIN_STORE_TEXT = [
        self::REMAIN_STORE['puhuo']  => '铺',
        self::REMAIN_STORE['no_puhuo']  => '不铺',
    ];

    const REMAIN_RULE_TYPE = [
        'no_select'    => 0, #不选
        'type_a'    => 1, #A方案
        'type_b'    => 2 #B方案
    ];

    const REMAIN_RULE_TYPE_TEXT = [
        self::REMAIN_RULE_TYPE['no_select']  => '不选',
        self::REMAIN_RULE_TYPE['type_a']  => 'A方案',
        self::REMAIN_RULE_TYPE['type_b']  => 'B方案'
    ];

    const IF_TAOZHUANG = [
        'is_taozhuang'    => 1, #是套装
        'not_taozhuang'    => 2, #非套装
    ];

    const IF_TAOZHUANG_TEXT = [
        self::IF_TAOZHUANG['is_taozhuang']  => '是套装',
        self::IF_TAOZHUANG['not_taozhuang']  => '非套装',
    ];

    const IF_ZDMD = [
        'is_zhiding'    => 1, #是
        'not_zhiding'    => 2, #否
    ];

    const IF_ZDMD_TEXT = [
        self::IF_ZDMD['is_zhiding']  => '是',
        self::IF_ZDMD['not_zhiding']  => '否',
    ];

}
