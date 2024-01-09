<?php


namespace app\common\service\command;


use think\Exception;
use think\facade\Db;

/**
 * Class MqxWeatherService
 * @package app\common\service\command
 */
class MqxWeatherService
{


    protected $mysql;

    public function __construct()
    {

        $this->mysql = Db::connect('mysql');


    }

    public function test()
    {
        $customer = $this->mysql->table('customer')->select()->toArray();
        $codeArr = $this->mysql->table('mqx_weather_code')->column('code', 'area');
        $arr = [];
        foreach ($customer as $item) {
            $newCity = mb_substr($item['CustomerName'], 0, 2);
            $code = $codeArr[$newCity] ?? '';
            if ($code) {
                $arr[] = [
                    'CustomerId' => $item['CustomerId'],
                    'CustomerName' => $item['CustomerName'],
                    'code' => $code,
                ];
            }
        }
//        $this->mysql->table('mqx_weather_customer')->insertAll($arr);


    }


    public function update_weather($weather_code = '', $date_Ym = '')
    {

        if (empty($date_Ym)) {
            $date_Ym = date('Ym');
            $Y = date('Y');
        } else {
            $Y = date('Y', strtotime($date_Ym));
        }
        $now = date('Ymd');
        $header = [
            "Referer:'http://www.weather.com.cn/'",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.64 Safari/537.36"
        ];

        $where = [];
        if (!empty($weather_code)) {
            $where = ['code' => $weather_code];
        }
        $code = $this->mysql->table('mqx_weather_customer')->where($where)->group('code')->select()->toArray();
        foreach ($code as $code_v) {
            try {

                $str = $Y . '/' . $code_v['code'] . '_' . $date_Ym;
                $url = "http://d1.weather.com.cn/calendar_new/$str.html?_=" . rand(1000, 99999999);
                $data = mqx_curl($url, '', false, $header, false);
                $data = substr($data, 11);
                $newData = json_decode($data, true);
                $newData = $newData ?: [];
                $insertAll = [];
                $oldData = $this->mysql->table('mqx_weather')->where('code', '=', $code_v['code'])->where('date', '>=', $now - 60)->select()->toArray();
                $oldTimeKeyV = array_column($oldData, null, 'date');
                $oldTime = array_keys($oldTimeKeyV);

                foreach ($newData as $item) {
                    $dbData = [
                        'code' => $code_v['code'],
                        'date' => $item['date'],
                        'max_c' => $item['max'] ?: ($item['maxobs'] ?: $item['hmax']),
                        'min_c' => $item['min'] ?: ($item['minobs'] ?: $item['hmin']),
                    ];
                    if ($item['date'] >= $now) {
                        $dbData['desc'] = $item['w1'];
                    } else { //历史描述保留
                        $dbData['desc'] = $oldTimeKeyV[$item['date']]['desc'] ?? '';
                    }
                    if (in_array($item['date'], $oldTime)) { //修改
                        $dbData['update_time'] = date('Y-m-d H:i:s');
                        $this->mysql->table('mqx_weather')->where('code', '=', $code_v['code'])->where('date', '=', $item['date'])->update($dbData);
                    } else {
                        $dbData['create_time'] = date('Y-m-d H:i:s');
                        $dbData['update_time'] = date('Y-m-d H:i:s');
                        $insertAll[] = $dbData;
                    }
                }
                if (!empty($insertAll)) {
                    $this->mysql->table('mqx_weather')->strict(false)->insertAll($insertAll);
                }
                sleep(1);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }

        return true;

    }


}