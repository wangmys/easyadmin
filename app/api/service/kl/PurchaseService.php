<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpPurchaseModel;
use app\api\model\kl\ErpPurchaseGoodsModel;
use app\api\model\kl\ErpPurchaseGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class PurchaseService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpPurchaseModel::where([['PurchaseID', '=', $params['PurchaseID']]])->field('PurchaseID')->find()) {
            json_fail(400, 'PurchaseID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['PurchaseID'] = $params['PurchaseID'];
            $arr['SupplyId'] = $params['SupplyId'];
            $arr['ReceiptWareId'] = $params['ReceiptWareId'];
            $arr['NatureName'] = $params['NatureName'];
            $arr['BillType'] = $params['BillType'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpPurchaseModel::INSERT);
            $new['PurchaseDate'] = $now;
            $new['Remark'] = $params['Remark'] ?? '';
            $new['ManualNo'] = $params['ManualNo'] ?? '';

            if ($params['CodingCode'] == ErpPurchaseModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpPurchaseModel::CodingCode_TEXT[$params['CodingCode']];
            }

            ErpPurchaseModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpPurchaseGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addPurchaseGoods($new['PurchaseID'], $new['PurchaseID'] . make_order_number($k, $k), $v);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addPurchaseGoods($PurchaseID, $PurchaseGoodsID, $detail) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['PurchaseGoodsID'] = $PurchaseGoodsID;
        $arr['PurchaseID'] = $PurchaseID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = $detail['Discount'];

        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['OrderID'] = $detail['OrderID'] ?? '';
        $arr['DeliveryDate'] = $detail['DeliveryDate'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice'] ?? '';
        $arr['CurrDeliveryDate'] = $detail['CurrDeliveryDate'] ?? '';
        $arr['ExchangeRate'] = $detail['ExchangeRate'] ?? '';
        $arr['TaxRate'] = $detail['TaxRate'] ?? '';
        $arr['fcPrice'] = $detail['fcPrice'] ?? '';
        $arr['NoTaxRatePrice'] = $detail['NoTaxRatePrice'] ?? '';
        $arr['IsCompleted'] = $detail['IsCompleted'] ?? '';
        $arr['CompletedUserId'] = $detail['CompletedUserId'] ?? '';
        $arr['CompletedUserName'] = $detail['CompletedUserName'] ?? '';
        $arr['CompletedTime'] = $detail['CompletedTime'] ?? '';
        $arr['TrialCostPrice'] = $detail['TrialCostPrice'] ?? '';

//        Db::startTrans();
        try {
            ErpPurchaseGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpPurchaseGoodsDetail 处理
                $this->addPurchaseGoodsDetail($PurchaseGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addPurchaseGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['PurchaseGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpPurchaseGoodsDetailModel::create($arr);
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function update($params) {

//        Db::startTrans();
        try {

            $new['CodingCode'] = $params['CodingCode'];
            if ($params['CodingCode'] == ErpPurchaseModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpPurchaseModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpPurchaseModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpPurchaseModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            ErpPurchaseModel::where([['PurchaseID', '=', $params['PurchaseID']]])->update($new);

        } catch (\Exception $e) {
//            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function delete($params) {

        Db::startTrans();
        try {

            ErpPurchaseModel::where([['PurchaseID', '=', $params['PurchaseID']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

