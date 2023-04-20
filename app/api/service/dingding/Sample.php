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
     * 获取部门useid
     * @return mixed
     */
    public function getDepartmentListIds()
    {

        echo $getDepartment_config = 'https://oapi.dingtalk.com/department/list_ids?access_token=' . $this->getToekn() . '&id=1';
        // $getDepartment = json_decode($this->GetCurlRequest($getDepartment_config), true);
        // return $getDepartment;

    }

    /**
     * 发送图片消息
     * @return bool|string
     */
    public function sendImageMsg($userid, $media_id)
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getToekn();
        $SendToUser_data = [
            // 'userid_list' => 350364576037719254,
            // 'userid_list' => 293746204229278162,
            'userid_list' => $userid,
            'agent_id' => 2476262581,
            "msg" => [
                "msgtype" => 'image',
                'image' => [
                    // 'media_id' => '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn'
                    // 'media_id' => '@lAjPDfmVbpdNBFvOLM0YvM5Sx48A'
                    'media_id' => $media_id
                ]
            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
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

    /**
     * 上传文件获得media_id
     * @param $filePath
     * @param $fileName
     * @return array
     */
    public function uploadDingFile($filePath, $fileName = '')
    {
        if (!$fileName) {
            $fileName = str_shuffle(md5(rand())) . '.jpg';
        }

        $url = "https://oapi.dingtalk.com/media/upload?access_token=" . $this->getToekn();

        //生成分隔符
        $delimiter = '-------------' . uniqid();
        //先将post的普通数据生成主体字符串
        $data = '';
        $data .= "--" . $delimiter . "\r\n";
        $data .= 'Content-Disposition: form-data; name="type"';
        $data .= "\r\n\r\n" . 'file' . "\r\n";
        $data .= "--" . $delimiter . "\r\n";
        $data .= 'Content-Disposition: form-data; name="' . 'media' . '"; filename="' . $fileName . "\" \r\n";
        $data .= 'Content-Type: ' . 'application/octet-stream' . "\r\n\r\n";
        $data .= file_get_contents($filePath) . "\r\n";
        //主体结束的分隔符
        $data .= "--" . $delimiter . "--";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            ]
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        unset($data);

        if (curl_errno($curl)) {
            return [false, '钉钉文件上传-curl失败'];
        } else {
            $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                return [false, '钉钉文件上传失败-非200'];
            }
        }
        $result = json_decode($response, true);
        if (!isset($result['errcode'])) {
            return [false, '钉钉文件上传失败-解码失败'];
        }
        if (isset($result['errcode']) && $result['errcode'] !== 0) {
            return [false, '钉钉文件上传失败-抛错信息-' . $response['errmsg']];
        }
        if (!isset($result['media_id'])) {
            return [false, '钉钉文件上传失败-media参数不存在'];
        }

        return $result['media_id'];
    }

    /**
     * post 请求
     * @param $remote_server
     * @param $post_string
     * @return bool|string
     */
    protected function PostCurlRequest($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}

