<?php
declare (strict_types = 1);

namespace app\api\controller;
use app\admin\model\dress\YinliuStore;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\cache\driver\Redis;
use think\facade\Db;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\Yinliu;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Customers;
use app\api\service\ratio\CodeService;
use think\facade\Log;
use app\admin\model\code\SizeWarehouseRatio;
use app\admin\model\code\SizeRanking;

/**
 * 码比-偏码数据处理
 * 1.拉取码比数据源储存在redis中
 * 2.从缓存中将码比数据源同步至MySQL
 * 3.根据据源统计全体偏码数据
 * 4.根据数据源统计云仓偏码数据
 * Class SizeRatio
 * @package app\api\controller
 */
class SizeRatio
{

    /**
     * 服务
     * @var CodeService|null
     */
    protected $service = null;
    // 日期
    protected $Date = '';

    /**
     * 从康雷查询排名数据并保存到当前数据库
     * @return \think\response\Json
     */
    public function pullData()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullData();
        // 记录执行
        pullLog($code,$model,'排名数据');
        return $model->getError($code);
    }

    /**
     * 拉取7天周销数据到缓存
     * @return \think\response\Json
     */
    public function pull7DaySale()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pull7DaySale();
        // 记录执行
        pullLog($code,$model,'周销数据');
        return $model->getError($code);
    }

    /**
     * 拉取累销数据保存到缓存
     * @return \think\response\Json
     */
    public function pullAccumulatedSale()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullAccumulatedSale();
        // 记录执行
        pullLog($code,$model,'累销数据');
        return $model->getError($code);
    }

    /**
     * 拉取店铺预计库存到缓存
     * @return \think\response\Json
     */
    public function pullShopEstimatedStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullShopEstimatedStock();
        // 记录执行
        pullLog($code,$model,'店铺预计库存数据');
        return $model->getError($code);
    }

    /**
     * 拉取云仓可用库存到缓存
     * @return \think\response\Json
     */
    public function pullWarehouseAvailableStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullWarehouseAvailableStock();
        // 记录执行
        pullLog($code,$model,'云仓可用库存数据');
        return $model->getError($code);
    }

    /**
     * 拉取云仓在途库存保存到缓存
     * @return \think\response\Json
     */
    public function pullWarehouseTransitStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullWarehouseTransitStock();
        // 记录执行
        pullLog($code,$model,'云仓在途库存数据');
        return $model->getError($code);
    }

    /**
     * 拉取仓库采购库存到缓存
     * @return \think\response\Json
     */
    public function pullPurchaseStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullPurchaseStock();
        // 记录执行
        pullLog($code,$model,'仓库采购库存数据');
        return $model->getError($code);
    }

    /**
     * 1.拉取偏码数据源保存至Redis缓存
     */
    public function pullDataToSaveCache()
    {
        ini_set("memory_limit", "512M");
        // 执行结果集
        $result = [];
        // 排名数据源
        $result['排名数据源'] = $this->pullData();
        // 周销数据源
        $result['周销数据源'] = $this->pull7DaySale();
        // 累销数据源
        $result['累销数据源'] = $this->pullAccumulatedSale();
        // 店铺预计库存数据源
        $result['店铺预计库存数据源'] = $this->pullShopEstimatedStock();
        // 云仓可用库存数据源
        $result['云仓可用库存数据源'] = $this->pullWarehouseAvailableStock();
        // 云仓在途库存数据源
        $result['云仓在途库存数据源'] = $this->pullWarehouseTransitStock();
        // 仓库采购数据源
        $result['仓库采购数据源'] = $this->pullPurchaseStock();
        echo '<pre>';
        print_r($result);
        die;
    }


    /**
     * 2.从缓存里把码比数据源同步至数据库
     * @return false|string
     */
    public function saveSaleData()
    {
        // 执行结果集
        $result = [];
        $server = new CodeService;
        $model = $server;
        foreach (ApiConstant::RATIO_PULL_REDIS_KEY as $k => $v){
            $tablename = '';
            switch ($v){
                // 同步周销数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[0]:
                    $tablename = 'ea_size_7day_sale';
                    break;
                // 同步累销数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[1]:
                    $tablename = 'ea_size_accumulated_sale';
                    break;
                // 同步店铺预计库存数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[2]:
                    $tablename = 'ea_size_shop_estimated_stock';
                    break;
                // 同步云仓可用库存数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[3]:
                    $tablename = 'ea_size_warehouse_available_stock';
                    break;
                // 同步云仓在途库存数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[4]:
                    $tablename = 'ea_size_warehouse_transit_stock';
                    break;
                // 同步仓库采购库存数据
                case ApiConstant::RATIO_PULL_REDIS_KEY[5]:
                    $tablename = 'ea_size_purchase_stock';
                    break;
            }
            // 清空历史数据
            if($tablename){
               Db::execute("truncate table $tablename");
            }
            // 从缓存同步到MYSQL数据库
            $code = $model->saveSaleData($v);
            // 记录执行
            pullLog($code,$model,$v);
            $result[$v] = $model->getError();
        }
        echo '<pre>';
        print_r($result);
        die;
    }

    /**
     * 3.根据数据源计算并保存全体总体偏码数据
     */
    public function saveRatio()
    {
        $res = \app\admin\model\code\SizeAllRatio::saveData();
        print_r($res);die;
    }

    /**
     * 4.根据数据源计算并保存云仓偏码数据
     */
    public function selectRationData()
    {
        $res = \app\admin\model\code\SizeWarehouseRatio::saveData();
        print_r($res);die;
    }

    /**
     * 同步至BI云仓偏码
     */
    public function syntoBiYuncangOffsetCode()
    {
        $list = new SizeRanking;
        $field = array_column(Db::connect("mysql2")->query("SHOW FULL COLUMNS FROM yuncang_offset_code;"),'Field');
        // 查询货号列表排名
        $list = $list->where(['Date' => date('Y-m-d')])->order('日均销','desc')->select();
        $allList = [];
        $size = [
            '00/28/37/44/100/160/S',
            '29/38/46/105/165/M',
            '30/39/48/110/170/L',
            '31/40/50/115/175/XL',
            '32/41/52/120/180/2XL',
            '33/42/54/125/185/3XL',
            '34/43/56/190/4XL',
            '35/44/58/195/5XL',
            '36/6XL',
            '38/7XL',
            '40/8XL',
            '总计'
        ];
        foreach ($list as $key => &$value){
              $value['近三天折率'] = '100%';
              $value['图片'] = $value['图片']?$value['图片']."?id={$value['id']}":'https://ff211-1254425741.cos.ap-guangzhou.myqcloud.com/B31101454.jpg'."?id={$value['id']}";
              $value['全国排名'] = $key+1;
              // 查询码比数据
              $item = SizeWarehouseRatio::selectWarehouseRatio($value['货号'],[]);

              foreach ($item as $k =>$v){

                  $item2 = $value->toArray() + $v;
                  foreach ($item2 as $vk => $val){
                      if(!in_array($vk,$field)){
                         unset($item2[$vk]);
                         continue;
                      }
                      $vk_key = mb_substr($vk , 3);
                      if(empty($val) || $val == '0.00'){
                          $item2[$vk] = '';
                      }else if(!empty($val) && is_numeric($val) && in_array($vk_key,$size) && in_array($item2['广州_字段'],['单码售罄比','当前库存尺码比','总库存尺码比','累销尺码比','单码售罄'])){
                          $item2[$vk] = $val.'%';
                      }
                  }
                  $allList[] = $item2;
              }
        }
        try {
            $code_count = Db::connect("mysql2")->table('yuncang_offset_code')->count();
            if($code_count > 0){
                Db::connect("mysql2")->query("truncate table yuncang_offset_code");
            }
            if(count($allList) > 0){
                $data_list = array_chunk($allList,200);
                foreach ($data_list as $k=>$v){
                    Db::connect("mysql2")->table('yuncang_offset_code')->insertAll($v);
                }
            }
            // 提交
            Db::commit();
        }catch (\Exception $e){
            // 回滚
            Db::rollback();
            return $e->getMessage();
        }
        return 'success';
    }

    /**
     * 获取所有key
     */
    public function getKeys()
    {
        $this->redis = new Redis(['password' => 'sg2023-07']);
        echo '<pre>';
        print_r($this->redis->handler()->keys('*'));
        die;
    }

    /**
     * 清除所有缓存数据
     */
    public function clearAll()
    {
        $this->redis = new Redis(['password' => 'sg2023-07']);
        $all_keys = $this->redis->handler()->keys('*');
        $this->redis->set('wx56a4463e942a299c_yuege_access_token',6666);
        $not_list = ['wx56a4463e942a299c_yuege_access_token','wx56a4463e942a299c_yuege_expires_time','wx56a4463e942a299c_yuege_set_name'];
        foreach ($all_keys as $k => $v){
            if(!in_array($v,$not_list)){
                $this->redis->delete($v);
            }
        }
        echo '<pre>';
        print_r($this->redis->handler()->keys('*'));
        die;
    }
}
