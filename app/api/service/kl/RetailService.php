<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpRetailModel;
use app\api\model\kl\ErpRetailGoodsModel;
use app\api\model\kl\ErpRetailGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\api\model\kl\ErpRetailPayModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class RetailService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpRetailModel::where([['RetailID', '=', $params['RetailID']]])->field('RetailID')->find()) {
            json_fail(400, 'RetailID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['RetailID'] = $params['RetailID'];
            $arr['CustomerId'] = $params['CustomerId'];
            $arr['CustomerName'] = $params['CustomerName'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpRetailModel::INSERT);
            $new['RetailDate'] = $now;
            $new['Remark'] = $params['Remark'] ?? '';
            $new['ManualNo'] = $params['ManualNo'] ?? '';
            $new['ClassName'] = $params['ClassName'] ?? '';
            $new['VIPNo'] = $params['VIPNo'] ?? '';
            $new['SalesmanID'] = $params['SalesmanID'] ?? '';
            $new['SalesmanName'] = $params['SalesmanName'] ?? '';
            $new['BillType'] = $params['BillType'];
            $new['PrintNum'] = $params['PrintNum'];
            $new['BillStatus'] = $params['BillStatus'] ?? NULL;

            if ($params['CodingCode'] == ErpRetailModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpRetailModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpRetailModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpRetailGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addRetailGoods($new['RetailID'], $new['RetailID'] . make_order_number($k, $k), $v, $params);
                }
            }

            //ErpRetailPay 处理
            $RetailPayInfo = $params['RetailPayInfo'] ?? [];
            if ($RetailPayInfo) {
                $this->addRetailPay($RetailPayInfo, $params['RetailID']);
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //事务回滚失败，执行删除操作处理多余数据
            $this->delete($params);
            abort(0, $e->getMessage());
        }

    }

    public function addRetailGoods($RetailID, $RetailGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['RetailGoodsID'] = $RetailGoodsID;
        $arr['RetailID'] = $RetailID;
        $arr['SalesmanID'] = $params['SalesmanID'] ?? '';
        $arr['SalesmanName'] = $params['SalesmanName'] ?? '';
        $arr['Status'] = $detail['Status'];
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['DiscountPrice'] = $detail['DiscountPrice'];
        $arr['Discount'] = $detail['Discount'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['PromotionId'] = $detail['PromotionId'] ?? null;
        $arr['CostPrice'] = $detail['CostPrice'] ?? null;
        $arr['GUnitPrice'] = $detail['GUnitPrice'] ?? null;
        $arr['GDiscount'] = $detail['GDiscount'] ?? null;
        $arr['DzUnitPrice'] = $detail['DzUnitPrice'] ?? null;
        $arr['RetailPrice'] = $detail['RetailPrice'] ?? null;
        $arr['ReturnRetailID'] = $detail['ReturnRetailID'] ?? null;
        $arr['SalesPromotionId'] = $detail['SalesPromotionId'] ?? null;

        //库存处理
        $CustomerStockData = [
            'StockId' => $RetailGoodsID,
            'CustomerId' => $params['CustomerId'],
            'CustomerName' => $params['CustomerName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpRetail',
            'BillId' => $params['RetailID'],
            'GoodsId' => $goodsId['GoodsId'],
            'Quantity' => $detail['Quantity'],
            'CreateTime' => date('Ymd H:i:s'),
            'UpdateTime' => date('Ymd H:i:s'),
            'Remark' => $detail['Remark'],
            'Version' => time(),
        ];
        $CustomerStockData = array_merge($CustomerStockData, ErpCustomerStockModel::INSERT);

//        Db::startTrans();
        try {
            ErpRetailGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpCustomerStockModel::create($CustomerStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpRetailGoodsDetail 处理
                $this->addRetailGoodsDetail($RetailGoodsID, $v);
            }
        } catch (\Exception $e) {
//            Db::rollback(); // 回滚事务
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addRetailGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['RetailGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpRetailGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['RetailGoodsID']);
                $arr['StockId'] = $detailid;
                ErpCustomerStockDetailModel::create($arr);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addRetailPay($RetailPayInfo, $RetailID) {

        foreach ($RetailPayInfo as $v_pay) {

            $arr['RetailID'] = $RetailID;
            $arr['PaymentID'] = $v_pay['PaymentID'];
            $arr['PaymentName'] = $v_pay['PaymentName'];
            $arr['PayMoney'] = $v_pay['PayMoney'];
            $arr['Balance'] = $v_pay['Balance'];
            $arr['Remark'] = $v_pay['Remark'] ?? null;
            $arr['PayBillId'] = $v_pay['PayBillId'] ?? null;
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $arr = array_merge($arr, ErpRetailPayModel::INSERT);
    //        Db::startTrans();
            try {
                ErpRetailPayModel::create($arr);
            } catch (\Exception $e) {
                log_error($e);
                abort(0, $e->getMessage());
    //            Db::rollback(); // 回滚事务
            }

        }

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function update($params) {

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpRetailModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpRetailModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpRetailModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpRetailModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpRetailModel::where([['RetailID', '=', $params['RetailID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpRetailModel::CodingCode['NOTCOMMIT']) {
                $RetailGoodsID = ErpRetailGoodsModel::where([['RetailID', '=', $params['RetailID']]])->column('RetailGoodsID');
                ErpCustomerStockModel::where([['StockId', 'in', $RetailGoodsID]])->delete();
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

        $RetailGoodsID = ErpRetailGoodsModel::where([['RetailID', '=', $params['RetailID']]])->column('RetailGoodsID');

        Db::startTrans();
        try {

            ErpRetailModel::where([['RetailID', '=', $params['RetailID']]])->delete();
            ErpRetailGoodsModel::where([['RetailID', '=', $params['RetailID']]])->delete();
            ErpRetailGoodsDetailModel::where([['RetailGoodsID', 'in', $RetailGoodsID]])->delete();

            //清理库存记录
            ErpCustomerStockModel::where([['StockId', 'in', $RetailGoodsID]])->delete();

            ErpRetailPayModel::where([['RetailID', '=', $params['RetailID']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

