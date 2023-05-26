<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpReceiptNoticeModel;
use app\api\model\kl\ErpReceiptNoticeGoodsModel;
use app\api\model\kl\ErpReceiptNoticeGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class ReceiptNoticeService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpReceiptNoticeModel::where([['ReceiptNoticeId', '=', $params['ReceiptNoticeId']]])->field('ReceiptNoticeId')->find()) {
            json_fail(400, 'ReceiptNoticeId单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['ReceiptNoticeId'] = $params['ReceiptNoticeId'];
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['SupplyId'] = $params['SupplyId'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpReceiptNoticeModel::INSERT);
            $new['ReceiptNoticeDate'] = $now;
            $new['ManualNo'] = $params['ManualNo'] ?? '';
            $new['Remark'] = $params['Remark'] ?? '';
            $new['IntType'] = $params['IntType'] ?? 0;
            $new['CustomerId'] = $params['CustomerId'] ?? '';
            $new['InstructionId'] = $params['InstructionId'] ?? '';

            if ($params['CodingCode'] == ErpReceiptNoticeModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpReceiptNoticeModel::CodingCode_TEXT[$params['CodingCode']];
            }

            ErpReceiptNoticeModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpReceiptNoticeGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addReceiptNoticeGoods($new['ReceiptNoticeId'], $new['ReceiptNoticeId'] . make_order_number($k, $k), $v, $params);
                }
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

    public function addReceiptNoticeGoods($ReceiptNoticeId, $ReceiptNoticeGoodsId, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['ReceiptNoticeGoodsId'] = $ReceiptNoticeGoodsId;
        $arr['ReceiptNoticeId'] = $ReceiptNoticeId;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = $detail['Discount'];

        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice'] ?? '';
        $arr['PurchaseID'] = $params['PurchaseID'] ?? '';
        $arr['ROutboundID'] = $params['ROutboundID'] ?? '';

//        Db::startTrans();
        try {
            ErpReceiptNoticeGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpReceiptNoticeGoodsDetail 处理
                $this->addReceiptNoticeGoodsDetail($ReceiptNoticeGoodsId, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addReceiptNoticeGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['ReceiptNoticeGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpReceiptNoticeGoodsDetailModel::create($arr);
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
            if ($params['CodingCode'] == ErpReceiptNoticeModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpReceiptNoticeModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpReceiptNoticeModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpReceiptNoticeModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            ErpReceiptNoticeModel::where([['ReceiptNoticeId', '=', $params['ReceiptNoticeId']]])->update($new);

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

            ErpReceiptNoticeModel::where([['ReceiptNoticeId', '=', $params['ReceiptNoticeId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

