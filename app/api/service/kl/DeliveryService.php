<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpDeliveryModel;
use app\api\model\kl\ErpDeliveryGoodsModel;
use app\api\model\kl\ErpDeliveryGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class DeliveryService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function createDelivery($params) {

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['DeliveryID'] = $params['DeliveryID'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['Version'] = time();
            $arr['CustomerId'] = $params['CustomerId'];
            $new = array_merge($arr, ErpDeliveryModel::INSERT);
            $new['DeliveryDate'] = $now;
            $new['Remark'] = $params['Remark'];

            if ($params['CodingCode'] == ErpDeliveryModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
            }

            //出货指令单 处理
            ErpDeliveryModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpDeliveryGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addDeliveryGoods($new['DeliveryID'], $new['DeliveryID'] . make_order_number($k, $k), $v);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addDeliveryGoods($deliveryid, $DeliveryGoodsID, $detail) {

        $arr['DeliveryGoodsID'] = $DeliveryGoodsID;
        $arr['DeliveryID'] = $deliveryid;
        $arr['GoodsId'] = ErpGoodsModel::where([['GoodsNo', '=', $detail['GoodsNo']]])->value('GoodsId');
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);

//        Db::startTrans();
        try {
            ErpDeliveryGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpDeliveryGoodsDetail 处理
                $this->addDeliveryGoodsDetail($DeliveryGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
//            Db::rollback(); // 回滚事务
        }

    }

    public function addDeliveryGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['DeliveryGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpDeliveryGoodsDetailModel::create($arr);
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
    public function updateDelivery($params) {

        try {

            $new['CodingCode'] = $params['CodingCode'];
            if ($params['CodingCode'] == ErpDeliveryModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpDeliveryModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            //出货指令单 处理
            ErpDeliveryModel::where([['DeliveryID', '=', $params['DeliveryID']]])->update($new);

        } catch (\Exception $e) {
            log_error($e);
            abort(0, '更新失败');
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function deleteDelivery($params) {

        Db::startTrans();
        try {

            ErpDeliveryModel::where([['DeliveryID', '=', $params['DeliveryID']]])->delete();
            $DeliveryGoodsID = ErpDeliveryGoodsModel::where([['DeliveryID', '=', $params['DeliveryID']]])->column('DeliveryGoodsID');
            ErpDeliveryGoodsModel::where([['DeliveryID', '=', $params['DeliveryID']]])->delete();
            if ($DeliveryGoodsID) {
                ErpDeliveryGoodsDetailModel::where([['DeliveryGoodsID', 'in', $DeliveryGoodsID]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '删除失败');
        }

    }

}

