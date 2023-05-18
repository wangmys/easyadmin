<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpSortingModel;
use app\api\model\kl\ErpSortingGoodsModel;
use app\api\model\kl\ErpSortingGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class SortingService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
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

            if ($params['CodingCode'] == ErpSortingModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpSortingModel::CodingCode_TEXT[$params['CodingCode']];
            }

            //出货指令单 处理
            ErpSortingModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpSortingGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addSortGoods($new['SortingID'], $new['SortingID'] . make_order_number($k, $k), $v);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addSortGoods($sortingid, $SortingGoodsID, $detail) {

        $arr['SortingGoodsID'] = $SortingGoodsID;
        $arr['SortingID'] = $sortingid;
        $arr['GoodsId'] = ErpGoodsModel::where([['GoodsNo', '=', $detail['GoodsNo']]])->value('GoodsId');
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);

//        Db::startTrans();
        try {
            ErpSortingGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpSortingGoodsDetail 处理
                $this->addSortGoodsDetail($SortingGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
//            Db::rollback(); // 回滚事务
        }

    }

    public function addSortGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['SortingGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpSortingGoodsDetailModel::create($arr);
        } catch (\Exception $e) {
            log_error($e);
//            Db::rollback(); // 回滚事务
        }

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function updateSorting($params) {

//        Db::startTrans();
        try {

            $new['CodingCode'] = $params['CodingCode'];
            if ($params['CodingCode'] == ErpSortingModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpSortingModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpSortingModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpSortingModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            //出货指令单 处理
            ErpSortingModel::where([['SortingID', '=', $params['SortingID']]])->update($new);

        } catch (\Exception $e) {
//            Db::rollback();
            log_error($e);
            abort(0, '更新失败');
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function deleteSorting($params) {

        Db::startTrans();
        try {

            ErpSortingModel::where([['SortingID', '=', $params['SortingID']]])->delete();
            $SortingGoodsID = ErpSortingGoodsModel::where([['SortingID', '=', $params['SortingID']]])->column('SortingGoodsID');
            ErpSortingGoodsModel::where([['SortingID', '=', $params['SortingID']]])->delete();
            if ($SortingGoodsID) {
                ErpSortingGoodsDetailModel::where([['SortingGoodsID', 'in', $SortingGoodsID]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '删除失败');
        }

    }

}

