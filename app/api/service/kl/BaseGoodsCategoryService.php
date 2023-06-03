<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpBaseGoodsCategoryModel;
use app\api\model\kl\ErpBaseGoodsTimeCategoryModel;
use app\api\model\kl\ErpBaseGoodsStyleCategoryModel;
use think\facade\Db;

class BaseGoodsCategoryService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        $arr['CreateTime'] = date('Ymd H:i:s');
        $arr['UpdateTime'] = date('Ymd H:i:s');
        $sql = '';

        $Type = $params['Type'];
        switch ($Type) {

            case 0: 
                if (ErpBaseGoodsCategoryModel::where([['CategoryId', '=', $params['CategoryId']]])->field('CategoryId')->find()) {
                    json_fail(400, 'CategoryId已存在');
                }
                $arr = array_merge($arr, $params, ErpBaseGoodsCategoryModel::INSERT);
                unset($arr['Type']);
                $sql = generate_sql($arr, 'ErpBaseGoodsCategory');
                break;

            case 1: //风格
                if (ErpBaseGoodsStyleCategoryModel::where([['StyleCategoryId', '=', $params['CategoryId']]])->field('StyleCategoryId')->find()) {
                    json_fail(400, 'StyleCategoryId已存在');
                }
                $arr['StyleCategoryId'] = $params['CategoryId'];
                $arr['ParentId'] = $params['ParentId'];
                $arr['StyleCategoryName'] = $params['CategoryName'];
                $arr['ViewOrder'] = $params['ViewOrder'];
                $arr['Level'] = $params['Level'];
                $arr = array_merge($arr, ErpBaseGoodsStyleCategoryModel::INSERT);
                $sql = generate_sql($arr, 'ErpBaseGoodsStyleCategory');
                break;

            case 2: //时间
                if (ErpBaseGoodsTimeCategoryModel::where([['TimeCategoryId', '=', $params['CategoryId']]])->field('TimeCategoryId')->find()) {
                    json_fail(400, 'TimeCategoryId已存在');
                }
                $arr['TimeCategoryId'] = $params['CategoryId'];
                $arr['ParentId'] = $params['ParentId'];
                $arr['TimeCategoryName'] = $params['CategoryName'];
                $arr['ViewOrder'] = $params['ViewOrder'];
                $arr['Level'] = $params['Level'];
                $arr = array_merge($arr, ErpBaseGoodsTimeCategoryModel::INSERT);
                $sql = generate_sql($arr, 'ErpBaseGoodsTimeCategory');
                break;

        }
        // echo $sql;die;
        Db::connect("sqlsrv2")->Query($sql);

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function update($params) {

        $arr['UpdateTime'] = date('Ymd H:i:s');
        $Type = $params['Type'];

        Db::startTrans();
        try {

            switch ($Type) {

                case 0: 
                    $arr = array_merge($arr, $params, ErpBaseGoodsCategoryModel::INSERT);
                    unset($arr['Type']);
                    unset($arr['CategoryId']);
                    ErpBaseGoodsCategoryModel::where([['CategoryId', '=', $params['CategoryId']]])->update($arr);
                    break;

                case 1: //风格
                    $arr['ParentId'] = $params['ParentId'];
                    $arr['StyleCategoryName'] = $params['CategoryName'];
                    $arr['ViewOrder'] = $params['ViewOrder'];
                    $arr = array_merge($arr, ErpBaseGoodsStyleCategoryModel::INSERT);
                    ErpBaseGoodsStyleCategoryModel::where([['StyleCategoryId', '=', $params['CategoryId']]])->update($arr);
                    break;

                case 2: //时间
                    $arr['ParentId'] = $params['ParentId'];
                    $arr['TimeCategoryName'] = $params['CategoryName'];
                    $arr['ViewOrder'] = $params['ViewOrder'];
                    $arr['Level'] = $params['Level'] ?? null;
                    $arr = array_merge($arr, ErpBaseGoodsTimeCategoryModel::INSERT);
                    ErpBaseGoodsTimeCategoryModel::where([['TimeCategoryId', '=', $params['CategoryId']]])->update($arr);
                    break;

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

        $Type = $params['Type'];

        Db::startTrans();
        try {

            switch ($Type) {

                case 0: 
                    ErpBaseGoodsCategoryModel::where([['CategoryId', '=', $params['CategoryId']]])->delete();
                    break;

                case 1: //风格
                    ErpBaseGoodsStyleCategoryModel::where([['StyleCategoryId', '=', $params['CategoryId']]])->delete();
                    break;

                case 2: //时间
                    ErpBaseGoodsTimeCategoryModel::where([['TimeCategoryId', '=', $params['CategoryId']]])->delete();
                    break;

            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

