<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpSortingModel;
use app\api\model\kl\ErpSortingGoodsModel;
use app\api\model\kl\ErpSortingGoodsDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class SortingService
{

    use Singleton;

    public function createSorting($params) {

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['SortingID'] = $params['SortingID'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['Version'] = time();
            $arr['CustomerId'] = $params['CustomerId'];
            $new = array_merge($arr, ErpSortingModel::INSERT);
            $new['SortingDate'] = $now;
            $new['Remark'] = $params['Remark'];

            //出货指令单 处理
            ErpSortingModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpSortingGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addSortGoods($new['SortingID'], $new['SortingID'] . make_order_number($k, $k), $v);
                }
            }

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addSortGoods($sortingid, $SortingGoodsID, $detail) {

        $arr['SortingGoodsID'] = $SortingGoodsID;
        $arr['SortingID'] = $sortingid;
        $arr['GoodsId'] = $detail['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);

        Db::startTrans();
        try {
            ErpSortingGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpSortingGoodsDetail 处理
                $this->addSortGoodsDetail($SortingGoodsID, $detail['ColorId'], $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            Db::rollback(); // 回滚事务
        }

    }

    public function addSortGoodsDetail($detailid, $colorid, $detail) {

        $arr['SortingGoodsID'] = $detailid;
        $arr['ColorId'] = $colorid;
        $arr['SizeId'] = $detail['SizeId'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
        Db::startTrans();
        try {
            ErpSortingGoodsDetailModel::create($arr);
        } catch (\Exception $e) {
            log_error($e);
            Db::rollback(); // 回滚事务
        }

    }



}

