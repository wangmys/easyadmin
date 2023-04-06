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
use app\common\constants\AdminConstant;


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

    /**
     * 推送商品专员
     * @param array $parms
     * @return mixed
     */
    public function send($parms = [])
    {
        $token = $this->getToekn();
        $name = (!empty($parms['name'])?$parms['name']:'曹太阳');
        $msg = [
                "msgtype" => "link",
                "link"=>[
                    "messageUrl"=>"http://im.babiboy.com/admin/system.dress.inventory/gather?name=".$name,
                    "picUrl"=>"@lALOACZwe2Rk",
                    "title"=>"商品问题预警提示 ",
                    "text"=>"点击查看"
                ]
        ];
        $arr = [
            'agent_id'=> 2476262581,
            'to_all_user'=>false,
            'userid_list' => $parms['userid'],
            "msg" => $msg
        ];
        $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=" . $token;
        $jsonStr = json_encode($arr); //转换为json格式
        $result = curl_post($url, $jsonStr);
        $json = json_decode($result, false);
        return $json->errmsg;
    }

    /**
     * 推送管理者
     * @param array $parms
     * @return mixed
     */
    public function main($parms = [])
    {
        $token = $this->getToekn();
        $msg = [
                "msgtype" => "link",
                "link"=>[
                    "messageUrl"=>"http://im.babiboy.com/admin/system.dress.inventory/task_overview",
                    "picUrl"=>"@lALOACZwe2Rk",
                    "title"=>"商品问题完成进度",
                    "text"=>"点击查看"
                ]
        ];
        $arr = [
            'agent_id'=> 2476262581,
            'to_all_user'=>false,
            'userid_list' => AdminConstant::ID_WV,
            "msg" => $msg
        ];
        $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=" . $token;
        $jsonStr = json_encode($arr); //转换为json格式
        $result = curl_post($url, $jsonStr);
        $json = json_decode($result, false);
        return $json->errmsg;
    }
}

