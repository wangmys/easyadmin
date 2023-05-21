<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpStockAdjustModel;
use app\api\model\kl\ErpStockAdjustGoodsModel;
use app\api\model\kl\ErpStockAdjustGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpWarehouseStockModel;
use app\api\model\kl\ErpWarehouseStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class StockAdjustService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->field('StockAdjustID')->find()) {
            json_fail(400, 'StockAdjustID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['StockAdjustID'] = $params['StockAdjustID'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['Version'] = time();
            $new = array_merge($arr, ErpStockAdjustModel::INSERT);
            $new['AdjustDate'] = $now;
            $new['ManualNo'] = $params['ManualNo']  ?? '';
            $new['Remark'] = $params['Remark']  ?? '';
            $new['BillSource'] = $params['BillSource'] ?? '';

            if ($params['StockAdjustAttributesId']) {
                $new['StockAdjustAttributesId'] = $params['StockAdjustAttributesId'];
            }

            $goods = $params['Goods'] ?? [];

            if ($params['CodingCode'] == ErpStockAdjustModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpStockAdjustModel::create($new);

            //ErpStockAdjustGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addStockAdjustGoods($new['StockAdjustID'], $new['StockAdjustID'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addStockAdjustGoods($StockAdjustID, $StockAdjustGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['StockAdjustGoodsID'] = $StockAdjustGoodsID;
        $arr['StockAdjustID'] = $StockAdjustID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice']  ?? '';

        //仓库库存处理 ErpWarehouseStock
        $WarehouseStockData = [
            'StockId' => $StockAdjustGoodsID,
            'WarehouseId' => $params['WarehouseId'],
            'WarehouseName' => $params['WarehouseName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpStockAdjust',
            'BillId' => $params['StockAdjustID'],
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
            ErpStockAdjustGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpWarehouseStockModel::create($WarehouseStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpStockAdjustGoodsDetail 处理
                $this->addStockAdjustGoodsDetail($StockAdjustGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addStockAdjustGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['StockAdjustGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;

//        Db::startTrans();
        try {
            ErpStockAdjustGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['StockAdjustGoodsID']);
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
        if ($params['CodingCode'] == ErpStockAdjustModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpStockAdjustModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpStockAdjustModel::CodingCode['NOTCOMMIT']) {
                $StockAdjustGoodsID = ErpStockAdjustGoodsModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->column('StockAdjustGoodsID');
                ErpWarehouseStockModel::where([['StockId', 'in', $StockAdjustGoodsID]])->delete();
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

        $StockAdjustGoodsID = ErpStockAdjustGoodsModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->column('StockAdjustGoodsID');

        Db::startTrans();
        try {

            ErpStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->delete();
            //清理库存记录
            ErpWarehouseStockModel::where([['StockId', 'in', $StockAdjustGoodsID]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //abort(0, '删除失败');
            return $e->getMessage();
        }

    }

}

