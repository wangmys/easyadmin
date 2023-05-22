<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpPurchaseReturnModel;
use app\api\model\kl\ErpPurchaseReturnGoodsModel;
use app\api\model\kl\ErpPurchaseReturnGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpWarehouseStockModel;
use app\api\model\kl\ErpWarehouseStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class PurchaseReturnService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpPurchaseReturnModel::where([['PurchaseReturnId', '=', $params['PurchaseReturnId']]])->field('PurchaseReturnId')->find()) {
            json_fail(400, 'PurchaseReturnId单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['PurchaseReturnId'] = $params['PurchaseReturnId'];#not null
            $arr['WarehouseId'] = $params['WarehouseId'];#not null
            $arr['SupplyId'] = $params['SupplyId'];#not null
            $arr['PurchaseID'] = $params['PurchaseID'];#not null
            $arr['PuReturnNoticeId'] = $params['PuReturnNoticeId'] ?? '';
            $arr['DeliveryId'] = $params['DeliveryId'] ?? '';
            $arr['ReturnId'] = $params['ReturnId'] ?? '';
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpPurchaseReturnModel::INSERT);
            $new['PurchaseReturnDate'] = $now;
            $new['ManualNo'] = $params['ManualNo']  ?? '';
            $new['Remark'] = $params['Remark']  ?? '';
            $new['WaitReceiptId'] = $params['WaitReceiptId']  ?? '';
            $new['CheckReceiptId'] = $params['CheckReceiptId']  ?? '';
            $new['BranchType'] = $params['BranchType']  ?? 0;
            $new['PaymentDate'] = $params['PaymentDate'] ?? '';
            $new['BillSource'] = $params['BillSource'] ?? '';
            $new['SalesItemId'] = $params['SalesItemId'] ?? '';
            $new['BusinessManId'] = $params['BusinessManId'] ?? '';
            $new['NatureId'] = $params['NatureId'] ?? '';

            $goods = $params['Goods'] ?? [];

            if ($params['CodingCode'] == ErpPurchaseReturnModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpPurchaseReturnModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpPurchaseReturnModel::create($new);

            //ErpPurchaseReturnGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addPurchaseReturnGoods($new['PurchaseReturnId'], $new['PurchaseReturnId'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addPurchaseReturnGoods($PurchaseReturnId, $PurchaseReturnGoodsId, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['PurchaseReturnGoodsId'] = $PurchaseReturnGoodsId;
        $arr['PurchaseReturnId'] = $PurchaseReturnId;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = $detail['Discount'];
        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice']  ?? '';
        $arr['PurchaseID'] = $params['PurchaseID']  ?? '';
        $arr['PuReturnNoticeId'] = $params['PuReturnNoticeId']  ?? '';
        $arr['DeliveryId'] = $params['DeliveryId']  ?? '';
        $arr['ReturnId'] = $params['ReturnId']  ?? '';

        //仓库库存处理 ErpWarehouseStock
        $WarehouseStockData = [
            'StockId' => $PurchaseReturnGoodsId,
            'WarehouseId' => $params['WarehouseId'],
            'WarehouseName' => $params['WarehouseName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpPurchaseReturn',
            'BillId' => $params['PurchaseReturnId'],
            'GoodsId' => $goodsId['GoodsId'],
            'Quantity' => $detail['Quantity'],
            'CreateTime' => date('Ymd H:i:s'),
            'UpdateTime' => date('Ymd H:i:s'),
            'Remark' => $params['Remark'],
            'Version' => time(),
        ];
        $WarehouseStockData = array_merge($WarehouseStockData, ErpWarehouseStockModel::INSERT);

//        Db::startTrans();
        try {
            ErpPurchaseReturnGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpWarehouseStockModel::create($WarehouseStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpPurchaseReturnGoodsDetail 处理
                $this->addPurchaseReturnGoodsDetail($PurchaseReturnGoodsId, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addPurchaseReturnGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['PurchaseReturnGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;

//        Db::startTrans();
        try {
            ErpPurchaseReturnGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['PurchaseReturnGoodsId']);
                $arr['StockId'] = $detailid;
                ErpWarehouseStockDetailModel::create($arr);
            }
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

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpPurchaseReturnModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpPurchaseReturnModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpPurchaseReturnModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpPurchaseReturnModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpPurchaseReturnModel::where([['PurchaseReturnId', '=', $params['PurchaseReturnId']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpPurchaseReturnModel::CodingCode['NOTCOMMIT']) {
                $PurchaseReturnGoodsId = ErpPurchaseReturnGoodsModel::where([['PurchaseReturnId', '=', $params['PurchaseReturnId']]])->column('PurchaseReturnGoodsId');
                ErpWarehouseStockModel::where([['StockId', 'in', $PurchaseReturnGoodsId]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //abort(0, '更新失败');
            return $e->getMessage();
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function delete($params) {

        $PurchaseReturnGoodsId = ErpPurchaseReturnGoodsModel::where([['PurchaseReturnId', '=', $params['PurchaseReturnId']]])->column('PurchaseReturnGoodsId');

        Db::startTrans();
        try {

            ErpPurchaseReturnModel::where([['PurchaseReturnId', '=', $params['PurchaseReturnId']]])->delete();
            //清理库存记录
            ErpWarehouseStockModel::where([['StockId', 'in', $PurchaseReturnGoodsId]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //abort(0, '删除失败');
            return $e->getMessage();
        }

    }

}

