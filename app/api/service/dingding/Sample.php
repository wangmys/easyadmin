<?php

namespace app\api\service\dingding;


use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetAccessTokenRequest;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\SetRobotPluginHeaders;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\SetRobotPluginRequest\pluginInfoList;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\SetRobotPluginRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;


class Sample
{

    /**
     * 使用 Token 初始化账号Client
     * @return Dingtalk Client
     */
    public static function createClient()
    {
        $config = new Config([]);
        $config->protocol = "https";
        $config->regionId = "central";
        return new Dingtalk($config);
    }
    
    public function getToekn()
    {
        if (empty(cache('access_token'))) {
            $AppKey = 'dingepkj0zauvbccggha';
            $AppSecret = 'WDJFBx1neOcadWdg_uwjTIG2S2yw-aHtvhvVpGSjvpI9T2Etw9CiJMqNm5jFFWcD';
            $url = "https://oapi.dingtalk.com/gettoken?appkey=" . $AppKey . "&appsecret=" . $AppSecret;
            $re = file_get_contents($url);
            $obj = json_decode($re);
            $access_token = $obj->access_token;
            cache('access_token', $access_token, 7200);
        } else {
            $access_token = cache('access_token');
        }
        return $access_token;
    }

    public function send($parms = [])
    {
        $token = $this->getToekn();
        $msg = [
                "msgtype" => "link",
                "link"=>[
                    "messageUrl"=>"http://im.babiboy.com/admin/system.dress.inventory/index?商品负责人=曹太阳",
                    "picUrl"=>"@lALOACZwe2Rk",
                    "title"=>"配饰库存预警提示",
                    "text"=>"点击查看"
                ]
        ];
        $arr = [
            'agent_id'=> 2476262581,
            'to_all_user'=>false,
            'userid_list' => '293746204229278162',
            "msg" => $msg
        ];
        $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=" . $token;
        $jsonStr = json_encode($arr); //转换为json格式
        $result = curl_post($url, $jsonStr);
        $json = json_decode($result, false);
        return $json->errmsg;
    }

    /**
     * 推送结果
     * @param array $parms
     * @return mixed
     */
    public function main($parms = [])
    {
        $token = $this->getToekn();
        $msg = [
                "msgtype" => "link",
                "link"=>[
                    "messageUrl"=>"http://im.babiboy.com/admin/system.dress.inventory/finish_rate?start_date=2023-03-20&end_date=2023-03-21",
                    "picUrl"=>"@lALOACZwe2Rk",
                    "title"=>"配饰库存完成进度",
                    "text"=>"点击查看"
                ]
        ];
        $arr = [
            'agent_id'=> 2476262581,
            'to_all_user'=>false,
            'userid_list' => '293746204229278162',
            "msg" => $msg
        ];
        $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=" . $token;
        $jsonStr = json_encode($arr); //转换为json格式
        $result = curl_post($url, $jsonStr);
        $json = json_decode($result, false);
        return $json->errmsg;
    }
}

