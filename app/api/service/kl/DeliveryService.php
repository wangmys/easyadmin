<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpDeliveryModel;
use app\api\model\kl\ErpDeliveryGoodsModel;
use app\api\model\kl\ErpDeliveryGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpWarehouseStockModel;
use app\api\model\kl\ErpWarehouseStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class DeliveryService
{

    use Singleton;

    protected $is_commit = 0;

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


            $goods = $params['Goods'] ?? [];

            if ($params['CodingCode'] == ErpDeliveryModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            //出货指令单 处理
            ErpDeliveryModel::create($new);

            //ErpDeliveryGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addDeliveryGoods($new['DeliveryID'], $new['DeliveryID'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addDeliveryGoods($deliveryid, $DeliveryGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['DeliveryGoodsID'] = $DeliveryGoodsID;
        $arr['DeliveryID'] = $deliveryid;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);
        $arr['SortingID'] = $params['SortingID'];

        //仓库库存处理 ErpWarehouseStock
        $WarehouseStockData = [
            'StockId' => $DeliveryGoodsID,
            'WarehouseId' => $params['WarehouseId'],
            'WarehouseName' => $params['WarehouseName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpDelivery',
            'BillId' => $params['DeliveryID'],
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
            ErpDeliveryGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpWarehouseStockModel::create($WarehouseStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpDeliveryGoodsDetail 处理
                $this->addDeliveryGoodsDetail($DeliveryGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            //abort(0, '保存失败2');
            return $e->getMessage();
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
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['DeliveryGoodsID']);
                $arr['StockId'] = $detailid;
                ErpWarehouseStockDetailModel::create($arr);
            }
        } catch (\Exception $e) {
            log_error($e);
            //abort(0, '保存失败3');
            return $e->getMessage();
//            Db::rollback(); // 回滚事务
        }

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function updateDelivery($params) {

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpDeliveryModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpDeliveryModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpDeliveryModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpDeliveryModel::where([['DeliveryID', '=', $params['DeliveryID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpDeliveryModel::CodingCode['NOTCOMMIT']) {
                $DeliveryGoodsID = ErpDeliveryGoodsModel::where([['DeliveryID', '=', $params['DeliveryID']]])->column('DeliveryGoodsID');
                ErpWarehouseStockModel::where([['StockId', 'in', $DeliveryGoodsID]])->delete();
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
    public function deleteDelivery($params) {

        $DeliveryGoodsID = ErpDeliveryGoodsModel::where([['DeliveryID', '=', $params['DeliveryID']]])->column('DeliveryGoodsID');

        Db::startTrans();
        try {

            ErpDeliveryModel::where([['DeliveryID', '=', $params['DeliveryID']]])->delete();
            //清理库存记录
            ErpWarehouseStockModel::where([['StockId', 'in', $DeliveryGoodsID]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //abort(0, '删除失败');
            return $e->getMessage();
        }

    }

}

