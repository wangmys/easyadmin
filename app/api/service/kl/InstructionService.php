<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpInstructionModel;
use app\api\model\kl\ErpInstructionGoodsModel;
use app\api\model\kl\ErpInstructionGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class InstructionService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpInstructionModel::where([['InstructionId', '=', $params['InstructionId']]])->field('InstructionId')->find()) {
            json_fail(400, 'InstructionId单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['InstructionId'] = $params['InstructionId'];//.'xcb' . make_order_number(rand(0, 99)) . time();
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpInstructionModel::INSERT);
            $new['InstructionDate'] = $now;
            $new['Remark'] = $params['Remark'];
            $new['Type'] = $params['Type'];
            $new['OutItemId'] = $params['OutItemId'];
            $new['InItemId'] = $params['InItemId'];
            $new['IsJizhang'] = $params['IsJizhang'];
            $new['JizhangTime'] = date('Ymd H:i:s');
            $new['OutBillId'] = $params['OutBillId'];
            $new['InBillId'] = $params['InBillId'];

            if ($params['CodingCode'] == ErpInstructionModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpInstructionModel::CodingCode_TEXT[$params['CodingCode']];
            }

            ErpInstructionModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpInstructionGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addInstructionGoods($new['InstructionId'], $new['InstructionId'] . make_order_number($k, $k), $v);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addInstructionGoods($InstructionId, $InstructionGoodsId, $detail) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['InstructionGoodsId'] = $InstructionGoodsId;
        $arr['InstructionId'] = $InstructionId;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['OutPrice'] = $detail['OutPrice'];
        $arr['OutDiscount'] = $detail['OutDiscount'];
        $arr['InPrice'] = $detail['InPrice'];
        $arr['InDiscount'] = $detail['InDiscount'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Remark'] = $detail['Remark'];
        $arr['ReturnApplyID'] = $detail['ReturnApplyID'];
        $arr['JUnitPrice'] = $detail['JUnitPrice'];
        $arr['JDiscount'] = $detail['JDiscount'];
        $arr['InJUnitPrice'] = $detail['InJUnitPrice'];
        $arr['InJDiscount'] = $detail['InJDiscount'];
        $arr['InstructionApplyId'] = $detail['InstructionApplyId'];

//        Db::startTrans();
        try {
            ErpInstructionGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpInstructionGoodsDetail 处理
                $this->addInstructionGoodsDetail($InstructionGoodsId, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, '保存失败2');
//            Db::rollback(); // 回滚事务
        }

    }

    public function addInstructionGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['InstructionGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpInstructionGoodsDetailModel::create($arr);
        } catch (\Exception $e) {
            log_error($e);
            abort(0, '保存失败3');
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
            if ($params['CodingCode'] == ErpInstructionModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpInstructionModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpInstructionModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpInstructionModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            ErpInstructionModel::where([['InstructionId', '=', $params['InstructionId']]])->update($new);

        } catch (\Exception $e) {
//            Db::rollback();
            log_error($e);
            abort(0, '更新失败');
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

            ErpInstructionModel::where([['InstructionId', '=', $params['InstructionId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '删除失败');
        }

    }

}

