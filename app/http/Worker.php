<?php


namespace app\http;

use app\http\logic\WorkMessage;
use think\worker\Server;

class Worker extends Server
{
//	protected $socket = 'websocket://0.0.0.0:2346';
    protected $protocol = 'websocket';
    protected $host     = '0.0.0.0';
    protected $port     = '2346';
    protected $option   = ['name' => 'think'];
    protected $context  = [];
    protected $redis  = null;
    // 客户端
    protected $list  = [];

    // 开始启动
    public function onWorkerStart(){
        var_dump('开始启动');
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379, 5);
    }

    // 连接成功
    public function onConnect($connection){

        $this->redis->set(1,5);
        // 执行一次
        $connection->send(json_encode("开始启动"));
        // 遍历当前进程所有的客户端连接，发送当前服务器的时间
        foreach($this->worker->connections as $connection)
        {
            $connection->send(time());
        }
        $connection->send($this->redis->get(1));
    }

	public function onMessage($connection,$data)
	{
        // 解析用户消息
        $msg = json_decode($data,true);
        // 获取UID
        $uid = isset($msg['uid'])?$msg['uid']:0;
        if(empty($uid)){
            $connection->send(json_encode('缺少uid'));
            return $connection->close();
        }

        // 判断当前客户端是否已经验证,即是否设置了uid
        if(!isset($connection->uid))
        {
           // 没验证的话把第一个包当做uid
           $connection->uid = $uid;

           // 储存客户端
           $this->worker->uidConnections[$connection->uid] = $connection;
           // 储存客户端
           $this->list[$connection->uid] = $connection;

           // 发送客户端ID
           $connection->send('login success, your uid is : ' . $connection->uid);
        }

        // 实例化一个工作类
        $factory = new WorkMessage($this->worker,$this->redis,$this->list);

        switch ($msg['value']){
            // 指定发送
            case 'all':
                $this->sendMessageByUid($uid,$msg['value']);
                break;
            // 获取客户端数量
            case 'count':
                $factory->getUidList($uid,$msg['value']);
                break;
            // 获取用户uid列表
            case 'key':
                $factory->getUserList($uid,$msg['value']);
                break;
            // 默认广播
            default:
                $this->broadcast($connection,$msg['value']);
        }
	}

	// 关闭
	public function onClose($connection){

        // 清除客户端列表
        if(isset($connection->uid)){
            // 销毁
            unset($this->list[$connection->uid]);
        }
        echo '<pre>';
        print_r(count($this->list));
	    var_dump('连接关闭');
	}

	// 系统报错
	public function onError($connection){
	    var_dump('报错');
	}

	// 向所有验证的用户推送数据
    public function broadcast($thisClient,$message)
    {
       foreach($this->worker->uidConnections as $connection)
       {
           if($connection != $thisClient){
               $connection->send($message);
           }
       }
    }

    // 针对uid推送数据
    public function sendMessageByUid($uid, $message)
    {
        if(isset($this->worker->uidConnections[$uid]))
        {
            $connection = $this->worker->uidConnections[$uid];
            $message .= '在线人数:' .count($this->worker->uidConnections);
            $connection->send($message);
        }
    }
}