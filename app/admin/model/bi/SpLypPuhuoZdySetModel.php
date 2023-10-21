<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoZdySetModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_zdy_set';

    const SELECT_TYPE = [
        'much_store'    => 1, #多店
        'much_province'    => 2, #多省
        'much_goods_manager'    => 3, #商品专员
        'much_mathod'    => 4, #经营模式
    ];

    const SELECT_TYPE_TEXT = [
        self::SELECT_TYPE['much_store']  => '多店',
        self::SELECT_TYPE['much_province']  => '多省',
        self::SELECT_TYPE['much_goods_manager']  => '商品专员',
        self::SELECT_TYPE['much_mathod']  => '经营模式',
    ];

    const IF_TAOZHUANG = [
        'is_taozhuang'    => 1, #是套装
        'not_taozhuang'    => 2, #非套装
    ];

    const IF_TAOZHUANG_TEXT = [
        self::IF_TAOZHUANG['is_taozhuang']  => '是套装',
        self::IF_TAOZHUANG['not_taozhuang']  => '非套装',
    ];

}
