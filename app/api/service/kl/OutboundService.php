<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpOutboundModel;
use app\api\model\kl\ErpOutboundGoodsModel;
use app\api\model\kl\ErpOutboundGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpWarehouseStockModel;
use app\api\model\kl\ErpWarehouseStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class OutboundService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['OutboundId'] = $params['OutboundId'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['InWarehouseId'] = $params['InWarehouseId'];
            $arr['Version'] = time();
            $new = array_merge($arr, ErpOutboundModel::INSERT);
            $new['OutboundDate'] = $now;
            $new['Remark'] = $params['Remark'];

            $goods = $params['Goods'] ?? [];

            if ($params['CodingCode'] == ErpOutboundModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpOutboundModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpOutboundModel::create($new);

            //ErpOutboundGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addOutboundGoods($new['OutboundId'], $new['OutboundId'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addOutboundGoods($OutboundId, $OutboundGoodsId, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['OutboundGoodsId'] = $OutboundGoodsId;
        $arr['OutboundId'] = $OutboundId;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);
        $arr['InstructionId'] = $params['InstructionId'];

        //仓库库存处理 ErpWarehouseStock
        $WarehouseStockData = [
            'StockId' => $OutboundGoodsId,
            'WarehouseId' => $params['WarehouseId'],
            'WarehouseName' => $params['WarehouseName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpOutbound',
            'BillId' => $params['OutboundId'],
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
            ErpOutboundGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpWarehouseStockModel::create($WarehouseStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpOutboundGoodsDetail 处理
                $this->addOutboundGoodsDetail($OutboundGoodsId, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            //abort(0, '保存失败2');
            return $e->getMessage();
//            Db::rollback(); // 回滚事务
        }

    }

    public function addOutboundGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['OutboundGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;

//        Db::startTrans();
        try {
            ErpOutboundGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['OutboundGoodsId']);
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
    public function update($params) {

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpOutboundModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpOutboundModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpOutboundModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpOutboundModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpOutboundModel::where([['OutboundId', '=', $params['OutboundId']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpOutboundModel::CodingCode['NOTCOMMIT']) {
                $OutboundGoodsId = ErpOutboundGoodsModel::where([['OutboundId', '=', $params['OutboundId']]])->column('OutboundGoodsId');
                ErpWarehouseStockModel::where([['StockId', 'in', $OutboundGoodsId]])->delete();
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

        $OutboundGoodsId = ErpOutboundGoodsModel::where([['OutboundId', '=', $params['OutboundId']]])->column('OutboundGoodsId');

        Db::startTrans();
        try {

            ErpOutboundModel::where([['OutboundId', '=', $params['OutboundId']]])->delete();
            //清理库存记录
            ErpWarehouseStockModel::where([['StockId', 'in', $OutboundGoodsId]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //abort(0, '删除失败');
            return $e->getMessage();
        }

    }

}

