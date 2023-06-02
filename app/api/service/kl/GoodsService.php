<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpGoodsColorModel;
use app\api\model\kl\ErpGoodsSizeModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpBaseGoodsColorModel;
use app\api\model\kl\ErpBaseGoodsSizeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class GoodsService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->field('GoodsId')->find()) {
            json_fail(400, 'GoodsId单号已存在');
        }

        $arr['CreateTime'] = date('Ymd H:i:s');
        $arr['UpdateTime'] = date('Ymd H:i:s');
        $arr['Version'] = time();

        $arr = array_merge($arr, $params);
        $arr = array_merge($arr, ErpGoodsModel::INSERT);
        $arr['BrandId'] = '100';
        $arr['BrandName'] = 'BABIBOY';
        $arr['DiscountTypeName'] = '默认';

        unset($arr['Version']);
        unset($arr['BarCodeInfo']);

        $sql = $this->generate_sql($arr);
        // echo $sql;die;
        Db::connect("sqlsrv2")->Query($sql);
        

    }

    /**
     * 单独处理 ErpBarCode、ErpGoodsColor、ErpGoodsSize表
     */
    public function deal_barcode($params) {

        $BarCodeInfo = $params['BarCodeInfo'];

        //ErpBarCode、ErpGoodsColor、ErpGoodsSize表处理
        if ($BarCodeInfo) {
                    
            $init_arr['CreateTime'] = date('Ymd H:i:s');
            $init_arr['UpdateTime'] = date('Ymd H:i:s');
            $init_arr['Version'] = time();

            try {

                foreach ($BarCodeInfo as $v_barcode) {

                    //ErpBarCode
                    $BarCodeData = [
                        'BarCode' => $v_barcode['BarCode'],
                        'GoodsId' => $params['GoodsId'],
                        'ColorId' => $v_barcode['ColorId'],
                        'SizeId' => $v_barcode['SizeId'],
                        'SpecId' => '1',
                    ];
                    $BarCodeData = array_merge($BarCodeData, $init_arr, ErpBarCodeModel::INSERT);
                    ErpBarCodeModel::create($BarCodeData);

                    //ErpGoodsColor
                    $GoodsColorInfo = ErpBaseGoodsColorModel::where([['ColorId', '=', $v_barcode['ColorId']]])->find();
                    $GoodsColorData = [
                        'GoodsId' => $params['GoodsId'],
                        'ColorId' => $v_barcode['ColorId'],
                        'ColorGroup' => $GoodsColorInfo['ColorGroup'],
                        'ColorCode' => $GoodsColorInfo['ColorCode'],
                        'ColorDesc' => $GoodsColorInfo['ColorDesc'],
                    ];
                    $GoodsColorData = array_merge($GoodsColorData, $init_arr, ErpGoodsColorModel::INSERT);
                    ErpGoodsColorModel::create($GoodsColorData);

                    //ErpGoodsSize
                    $GoodsSizeInfo = ErpBaseGoodsSizeModel::where([['SizeId', '=', $v_barcode['SizeId']]])->find();
                    $GoodsSizeData = [
                        'GoodsId' => $params['GoodsId'],
                        'SizeId' => $v_barcode['SizeId'],
                        'SizeClass' => $GoodsSizeInfo['SizeClass'],
                        'Size' => $GoodsSizeInfo['Size'],
                    ];
                    $GoodsSizeData = array_merge($GoodsSizeData, $init_arr, ErpGoodsSizeModel::INSERT);
                    ErpGoodsSizeModel::create($GoodsSizeData);

                }
            } catch (\Exception $e) {
                log_error($e);
                abort(0, json_encode(['abort_code'=>'goods_error_001', 'abort_msg'=>$e->getMessage()]));
            }

        }

    }

    protected function generate_sql($arr) {

        $sql_str = 'set identity_insert ErpGoods ON; insert into [ErpGoods] (';
        $key = '';
        $value = ' VALUES (';
        foreach ($arr as $k_arr => $v_arr) {
            $key .= '['.$k_arr.'],';
            $value .= "'".$v_arr."',";
        }
        $key = substr($key, 0, -1);
        $value = substr($value, 0, -1);
        $sql_str = $sql_str.$key.')'.$value.');';
        // echo $sql_str;die;
        return $sql_str;

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function update($params) {

       Db::startTrans();
        try {
            $new['UpdateTime'] = date('Ymd H:i:s');
            $new = array_merge($new, $params);
            $BarCodeInfo = $new['BarCodeInfo'];

            unset($new['GoodsId']);
            unset($new['BarCodeInfo']);
            $new['BrandId'] = '100';
            $new['BrandName'] = 'BABIBOY';
            $new['DiscountTypeName'] = '默认';
            ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->update($new);

            //删除旧数据，重新插入新数据
            ErpBarCodeModel::where([['GoodsId', '=', $params['GoodsId']]])->delete();
            ErpGoodsColorModel::where([['GoodsId', '=', $params['GoodsId']]])->delete();
            ErpGoodsSizeModel::where([['GoodsId', '=', $params['GoodsId']]])->delete();
            $this->deal_barcode($params);

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

        Db::startTrans();
        try {

            ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

