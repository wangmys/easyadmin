<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpReceiptModel;
use app\api\model\kl\ErpReceiptGoodsModel;
use app\api\model\kl\ErpReceiptGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpWarehouseStockModel;
use app\api\model\kl\ErpWarehouseStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class ReceiptinService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpReceiptModel::where([['ReceiptId', '=', $params['ReceiptId']]])->field('ReceiptId')->find()) {
            json_fail(400, 'ReceiptId单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['ReceiptId'] = $params['ReceiptId'];#NOT NULL
            $arr['WarehouseId'] = $params['WarehouseId'];#NOT NULL
            $arr['ReceiptDate'] = $now;#NOT NULL
            $arr['ManualNo'] = $params['WarehouseId'];
            $arr['Type'] = $params['Type'];#NOT NULL
            $arr['WaitReceiptId'] = $params['WaitReceiptId'];
            $arr['CheckReceiptId'] = $params['CheckReceiptId'];
//            $arr['SupplyId'] = $params['SupplyId'] ?? null;
            $arr['DeliveryId'] = $params['DeliveryId'];
            $arr['OutboundId'] = $params['OutboundId'];
            $arr['FromWarehouseId'] = $params['FromWarehouseId'];
            $arr['ReturnId'] = $params['ReturnId'];
//            $arr['CustomerId'] = $params['CustomerId'] ?? null;
            $arr['Remark'] = $params['Remark'];
            $arr['Version'] = time();
            $arr['IsDiff'] = $params['IsDiff'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['PaymentDate'] = $params['PaymentDate'];
            $new = array_merge($arr, ErpReceiptModel::INSERT);

            if ($params['SupplyId']) {
                $new['SupplyId'] = $params['SupplyId'];
            }
            if ($params['CustomerId']) {
                $new['CustomerId'] = $params['CustomerId'];
            }

            $goods = $params['Goods'] ?? [];

            if ($params['CodingCode'] == ErpReceiptModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpReceiptModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpReceiptModel::create($new);

            //ErpReceiptGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addReceiptGoods($new['ReceiptId'], $new['ReceiptId'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //事务回滚失败，执行删除操作处理多余数据
            $this->delete($params);
            abort(0, $e->getMessage());
            // return $e->getMessage();
        }

    }

    public function addReceiptGoods($ReceiptId, $ReceiptGoodsId, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['ReceiptGoodsId'] = $ReceiptGoodsId;#NOT NULL
        $arr['ReceiptId'] = $ReceiptId;#NOT NULL
        $arr['GoodsId'] = $goodsId['GoodsId'];#NOT NULL
        $arr['UnitPrice'] = $detail['UnitPrice'];#NOT NULL
        $arr['Price'] = $detail['Price'];#NOT NULL
        $arr['Quantity'] = $detail['Quantity'];#NOT NULL
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);#NOT NULL
        $arr['Remark'] = $detail['Remark'];
        $arr['PurchaseID'] = $params['PurchaseID'];
        $arr['DeliveryId'] = $detail['DeliveryId'];
        $arr['ReturnId'] = $detail['ReturnId'];
        $arr['InstructionId'] = $params['InstructionId'];
        $arr['ReceiptNoticeId'] = $detail['ReceiptNoticeId'];
        $arr['CostPrice'] = $detail['CostPrice'];
        $arr['JUnitPrice'] = $detail['JUnitPrice'];
        $arr['JDiscount'] = $detail['JDiscount'];
        $arr['ReferCostPrice'] = $detail['ReferCostPrice'];
        $arr['ReferCostAmount'] = $detail['ReferCostAmount'];

        //仓库库存处理 ErpWarehouseStock
        $WarehouseStockData = [
            'StockId' => $ReceiptGoodsId,
            'WarehouseId' => $params['WarehouseId'],
            'WarehouseName' => $params['WarehouseName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpReceipt',
            'BillId' => $params['ReceiptId'],
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
            ErpReceiptGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpWarehouseStockModel::create($WarehouseStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpReceiptGoodsDetail 处理
                $this->addReceiptGoodsDetail($ReceiptGoodsId, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
            // return $e->getMessage();
//            Db::rollback(); // 回滚事务
        }

    }

    public function addReceiptGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['ReceiptGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;

//        Db::startTrans();
        try {
            ErpReceiptGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['ReceiptGoodsId']);
                $arr['StockId'] = $detailid;
                ErpWarehouseStockDetailModel::create($arr);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
            // return $e->getMessage();
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
        if ($params['CodingCode'] == ErpReceiptModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpReceiptModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpReceiptModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpReceiptModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpReceiptModel::where([['ReceiptId', '=', $params['ReceiptId']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpReceiptModel::CodingCode['NOTCOMMIT']) {
                $ReceiptGoodsId = ErpReceiptGoodsModel::where([['ReceiptId', '=', $params['ReceiptId']]])->column('ReceiptGoodsId');
                ErpWarehouseStockModel::where([['StockId', 'in', $ReceiptGoodsId]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
            // return $e->getMessage();
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function delete($params) {

        $ReceiptGoodsId = ErpReceiptGoodsModel::where([['ReceiptId', '=', $params['ReceiptId']]])->column('ReceiptGoodsId');

        Db::startTrans();
        try {

            ErpReceiptModel::where([['ReceiptId', '=', $params['ReceiptId']]])->delete();
            //清理库存记录
            ErpWarehouseStockModel::where([['StockId', 'in', $ReceiptGoodsId]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
            // return $e->getMessage();
        }

    }

}

