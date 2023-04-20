<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\common\logic\inventory;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use app\admin\model\dress\YinliuStore;
use app\admin\model\dress\YinliuProblemLog;
use app\admin\model\dress\DressHead;
use app\admin\model\dress\DressWarStock;

/**
 * 逻辑层
 * Class InventoryLogic
 * @package app\common\logic
 */
class InventoryLogic
{
    
    /***
     * 构造方法
     * DressLogic constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        $this->dressHead = new DressHead;
        $this->warStock = new DressWarStock;
    }

    /**
     * 统计引流款问题进度
     */
    public function yinliuProblemTotal($name)
    {
        // 周一问题数量
        $total = YinliuProblemLog::where([
            '商品负责人' => $name,
            'Date' => getThisDayToStartDate()[0]
        ])->group('商品负责人')->column('count(*) as num','商品负责人');

        // 周一未完成数量
        $not_total = YinliuProblemLog::where([
            '商品负责人' => $name,
            'Date' => getThisDayToStartDate()[0],
            'is_qualified' => 0
        ])->group('商品负责人')->column('count(*) as num','商品负责人');

        // 周一完成数量
        $ok_total = YinliuProblemLog::where([
            'Date' => getThisDayToStartDate()[0]
        ])->where(['is_qualified' => 1])->group('商品负责人')->column('count(*) as num','商品负责人');

        // 今日问题数量
        $this_num = YinliuProblemLog::where([
            '商品负责人' => $name,
            'Date' => getThisDayToStartDate()[1],
            'is_qualified' => 0
        ])->group('商品负责人')->column('count(*) as num','商品负责人');



        $item = [
            'name' => '引流款库存不足',
            // 问题总数
            'total' => $total,
            'not_total' => $not_total,
            'this_num' => $this_num,
            'ok_total' => $ok_total,
            'time' => $not_total>0?getIntervalDays():'',
            'type' => 'yinliu'
        ];
        return $item;
    }
}