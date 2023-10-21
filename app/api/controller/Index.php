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
use app\admin\model\code\SizeAllRatio;
use app\admin\model\code\SizeWarehouseRatio;

class Index
{
    public function index()
    {
        $url = "http://www.weather.com.cn/weather40d/101280101.shtml";
        $url = "https://tianqi.2345.com/wea_forty/57516.htm";
        $html = HtmlDomParser::file_get_html($url);
        $el = $html->find('ul[class="weeks-names"] li');
        echo '<pre>';
        print_r($el);
        die;
    }

    /**
     * 执行任务
     */
    public function rund()
    {
        $redis = new Redis;
        $model = (new \app\http\logic\AddHistoryData);
        while ($redis->llen('finish_task') <= 396){
            $model->run();
        }
        echo '<pre>';
        print_r(22);
        die;
    }

    /**
     * 更新天气温带+气温区域
     */
    public function updateWeatherInfo()
    {
        // 查询所有门店ID
        $ids = Customers::column('CustomerId');
        $ids = implode("','", $ids);
        $ids = "'".$ids."'";
        // 根据所有门店ID查询所属温带 + 气温区域
        // $data = Db::connect("sqlsrv")->table('ErpCustomer')->column('CustomItem30,CustomItem36,ShutOut','CustomerId');
        $data = Db::connect("sqlsrv")->query("select ec.CustomerId,ec.CustomItem30,ec.CustomItem36,ec.ShutOut,ebcm.Mathod,ec.CustomerGrade,ec.State,ec.City,ec.CustomItem17 from 
        ErpCustomer ec 
        left join ErpBaseCustomerMathod ebcm on ec.MathodId = ebcm.MathodId 
        where ec.CustomerId in ($ids);");
        $update_data = [];
        foreach ($data as $k => $v){
            $update_data[$v['CustomerId']] = [
                'CustomItem30' => $v['CustomItem30'],
                'CustomItem36' => $v['CustomItem36'],
                'ShutOut' => $v['ShutOut'],
                'Mathod' => $v['Mathod'],
                'CustomerGrade' => $v['CustomerGrade'] ?: '',
                'State' => $v['State'],
                'City' => $v['City'],
                'liable' => $v['CustomItem17'],
            ];
        }
        // print_r($update_data);die;
        Db::startTrans();
        $result = [];
        try {
            foreach ($update_data as $kk=>$vv){
                $result[$kk] =  Customers::where([
                    'CustomerId' => $kk
                ])->update($vv);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'msg' => $e->getMessage(),
                'code' => 0
            ]);
        }
        return json([
            'msg' => '成功',
            'code' => 1,
            'data' => $result
        ]);
    }
    
    public function pull()
    {
        $redis = new Redis(['password' => 'sg2023-07']);
        echo '<pre>';
        print_r($redis->set('x',111));
        die;
        echo '<pre>';
        print_r($redis);die;
//        echo phpinfo();die;
//        $res = SizeAllRatio::saveSizeRatio('B32502028');
//        $res = SizeWarehouseRatio::saveSizeRatio('B32502028');
        $res = SizeAllRatio::newSaveSizeRatio('B42513009');
//        echo '<pre>';
//        print_r($res);
//        die;
    }
}
