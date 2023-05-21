<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpCustOutboundModel;
use app\api\model\kl\ErpCustOutboundGoodsModel;
use app\api\model\kl\ErpCustOutboundGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class CustOutboundService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpCustOutboundModel::where([['CustOutboundId', '=', $params['CustOutboundId']]])->field('CustOutboundId')->find()) {
            json_fail(400, 'CustOutboundId单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['CustOutboundId'] = $params['CustOutboundId'];
            $arr['CustomerId'] = $params['CustomerId'];
            $arr['CustomerName'] = $params['CustomerName'];
            $arr['InCustomerId'] = $params['InCustomerId'];
            $arr['InCustomerName'] = $params['InCustomerName'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpCustOutboundModel::INSERT);
            $new['CustOutboundDate'] = $now;
            $new['Remark'] = $params['Remark'];
            $new['ManualNo'] = $params['ManualNo'];

            if ($params['CodingCode'] == ErpCustOutboundModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpCustOutboundModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpCustOutboundModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpCustOutboundGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addCustOutboundGoods($new['CustOutboundId'], $new['CustOutboundId'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    public function addCustOutboundGoods($CustOutboundId, $CustOutboundGoodsId, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['CustOutboundGoodsId'] = $CustOutboundGoodsId;
        $arr['CustOutboundId'] = $CustOutboundId;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);
        $arr['Remark'] = $detail['Remark'];
        $arr['InstructionId'] = $params['InstructionId'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice'] ?? '';

        //库存处理
        $CustomerStockData = [
            'StockId' => $CustOutboundGoodsId,
            'CustomerId' => $params['CustomerId'],
            'CustomerName' => $params['CustomerName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpCustOutbound',
            'BillId' => $params['CustOutboundId'],
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
            ErpCustOutboundGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpCustomerStockModel::create($CustomerStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpCustOutboundGoodsDetail 处理
                $this->addCustOutboundGoodsDetail($CustOutboundGoodsId, $v);
            }
        } catch (\Exception $e) {
//            Db::rollback(); // 回滚事务
            log_error($e);
            abort(0, '保存失败2');
        }

    }

    public function addCustOutboundGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['CustOutboundGoodsId'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpCustOutboundGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['CustOutboundGoodsId']);
                $arr['StockId'] = $detailid;
                ErpCustomerStockDetailModel::create($arr);
            }
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

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpCustOutboundModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpCustOutboundModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpCustOutboundModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpCustOutboundModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpCustOutboundModel::where([['CustOutboundId', '=', $params['CustOutboundId']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpCustOutboundModel::CodingCode['NOTCOMMIT']) {
                $CustOutboundGoodsId = ErpCustOutboundGoodsModel::where([['CustOutboundId', '=', $params['CustOutboundId']]])->column('CustOutboundGoodsId');
                ErpCustomerStockModel::where([['StockId', 'in', $CustOutboundGoodsId]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
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

        $CustOutboundGoodsId = ErpCustOutboundGoodsModel::where([['CustOutboundId', '=', $params['CustOutboundId']]])->column('CustOutboundGoodsId');

        Db::startTrans();
        try {

            ErpCustOutboundModel::where([['CustOutboundId', '=', $params['CustOutboundId']]])->delete();

            //清理库存记录
            ErpCustomerStockModel::where([['StockId', 'in', $CustOutboundGoodsId]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '删除失败');
        }

    }

}

