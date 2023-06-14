<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpCustReceiptModel;
use app\api\model\kl\ErpCustReceiptGoodsModel;
use app\api\model\kl\ErpCustReceiptGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\api\model\kl\ErpDeliveryModel;
use app\api\model\kl\ErpDeliveryGoodsModel;
use app\api\model\kl\ErpSortingModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class ReceiptService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpCustReceiptModel::where([['ReceiptID', '=', $params['ReceiptID']]])->field('ReceiptID')->find()) {
            json_fail(400, 'ReceiptID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['ReceiptID'] = $params['ReceiptID'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            // $arr['WarehouseId'] = $params['WarehouseId'];
            // $arr['WarehouseName'] = $params['WarehouseName'];
            $arr['Version'] = time();
            $arr['CustomerId'] = $params['CustomerId'];
            $arr['CustomerName'] = $params['CustomerName'];
            $new = array_merge($arr, ErpCustReceiptModel::INSERT);
            $new['ReceiptDate'] = $now;
            $new['Remark'] = $params['Remark'];
            $new['Type'] = $params['Type'];
            $new['CustOutID'] = $params['CustOutID'] ?? '';
            $new['FromCustomerId'] = $params['FromCustomerId'] ?? '';
            $new['FromCustomerName'] = $params['FromCustomerName'] ?? '';
            $new['DeliveryId'] = $params['DeliveryID'] ?? '';
            $new['WarehouseId'] = $params['WarehouseId'] ?? '';
            $new['WarehouseName'] = $params['WarehouseName'] ?? '';

            if ($params['CodingCode'] == ErpCustReceiptModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpCustReceiptModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpCustReceiptModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpCustReceiptGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addCustReceiptGoods($new['ReceiptID'], $new['ReceiptID'] . make_order_number($k, $k), $v, $params);
                }
            }

            //处理 标记完成 问题
            if ($new['Type']==1 && $this->is_commit == 1) {
                $this->dealIsCompleted($new['DeliveryId']);
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //事务回滚失败，执行删除操作处理多余数据
            $this->delete($params);
            //处理 标记完成 问题
            if ($new['Type']==1 && $this->is_commit == 1) {
                $this->dealIsCompleted($new['DeliveryId'], 0);
            }
            abort(0, $e->getMessage());
        }

    }

    public function dealIsCompleted($DeliveryID, $IsCompleted=1) {

        if ($DeliveryID) {

            ErpDeliveryModel::where([['DeliveryID', '=', $DeliveryID]])->update(['IsCompleted' => $IsCompleted]);
            $SortingID = ErpDeliveryGoodsModel::where([['DeliveryID', '=', $DeliveryID]])->field('SortingID')->find();
            $SortingID = $SortingID ? $SortingID['SortingID'] : '';
            if ($SortingID) {
                ErpSortingModel::where([['SortingID', '=', $SortingID]])->update(['IsCompleted' => $IsCompleted]);
            }

        }

    }


    public function addCustReceiptGoods($ReceiptID, $ReceiptGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['ReceiptGoodsID'] = $ReceiptGoodsID;
        $arr['ReceiptID'] = $ReceiptID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);
        $arr['DeliveryID'] = $params['DeliveryID'];
        $arr['PurchaseID'] = $params['PurchaseID'] ?? '';
        $arr['CustOutboundId'] = $params['CustOutboundId'] ?? '';
        $arr['InstructionId'] = $params['InstructionId'] ?? '';
        $arr['ReceiptNoticeId'] = $params['ReceiptNoticeId'] ?? '';

        //库存处理
        $CustomerStockData = [
            'StockId' => $ReceiptGoodsID,
            'CustomerId' => $params['CustomerId'],
            'CustomerName' => $params['CustomerName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpCustReceipt',
            'BillId' => $params['ReceiptID'],
            'GoodsId' => $goodsId['GoodsId'],
            'Quantity' => $detail['Quantity'],
            'CreateTime' => date('Ymd H:i:s'),
            'UpdateTime' => date('Ymd H:i:s'),
            'Remark' => $params['Remark'],
            'Version' => time(),
        ];
        $CustomerStockData = array_merge($CustomerStockData, ErpCustomerStockModel::INSERT);

//        Db::startTrans();
        try {
            ErpCustReceiptGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpCustomerStockModel::create($CustomerStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpCustReceiptGoodsDetail 处理
                $this->addCustReceiptGoodsDetail($ReceiptGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addCustReceiptGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['ReceiptGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpCustReceiptGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['ReceiptGoodsID']);
                $arr['StockId'] = $detailid;
                ErpCustomerStockDetailModel::create($arr);
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
        if ($params['CodingCode'] == ErpCustReceiptModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpCustReceiptModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpCustReceiptModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpCustReceiptModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpCustReceiptModel::where([['ReceiptID', '=', $params['ReceiptID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpCustReceiptModel::CodingCode['NOTCOMMIT']) {
                $ReceiptGoodsID = ErpCustReceiptGoodsModel::where([['ReceiptID', '=', $params['ReceiptID']]])->column('ReceiptGoodsID');
                ErpCustomerStockModel::where([['StockId', 'in', $ReceiptGoodsID]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
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

        $ReceiptGoodsID = ErpCustReceiptGoodsModel::where([['ReceiptID', '=', $params['ReceiptID']]])->column('ReceiptGoodsID');

        Db::startTrans();
        try {

            //ErpCustReceipt没有外键，要一个个删除关联表
            ErpCustReceiptModel::where([['ReceiptID', '=', $params['ReceiptID']]])->delete();
            ErpCustReceiptGoodsModel::where([['ReceiptID', '=', $params['ReceiptID']]])->delete();
            ErpCustReceiptGoodsDetailModel::where([['ReceiptGoodsID', 'in', $ReceiptGoodsID]])->delete();

            //清理库存记录
            ErpCustomerStockModel::where([['StockId', 'in', $ReceiptGoodsID]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

