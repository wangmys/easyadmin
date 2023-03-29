<?php
use think\facade\Env;

return [
    // 默认使用的数据库连接配置
    'default'         => Env::get('database.driver', 'mysql'),

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,

    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',

    // 数据库连接配置信息
    'connections'     => [
         'sqlsrv' => [
             // 数据库类型
             'type'              => Env::get('database3.type', 'sqlsrv'),
             // 服务器地址
             'hostname'          => Env::get('database3.hostname', '47.113.79.107'),
             // 数据库名
             'database'          => Env::get('database3.database', 'ff211'),
             // 用户名
             'username'          => Env::get('database3.username', 'reader'),
             // 密码
             'password'          => Env::get('database3.password', 'suoge@2023'),
             // 端口
             'hostport'          => Env::get('database3.hostport', '19122'),
             // 数据库连接参数
             'params'            => [],
             // 数据库编码默认采用utf8
             'charset'           => Env::get('database3.charset', 'utf8'),
             // 数据库表前缀
             'prefix'            => Env::get('database3.prefix', ''),
         ],
        'mysql' => [
            // 数据库类型
            'type'              => Env::get('database.type', 'mysql'),
            // 服务器地址
            'hostname'          => Env::get('database.hostname', '42.193.181.241'),
            // 数据库名
            'database'          => Env::get('database.database', 'easyadmin2'),
            // 用户名
            'username'          => Env::get('database.username', 'easyadmin2'),
            // 密码
            'password'          => Env::get('database.password', 'FwRrharKpKEm6ZJa'),
            // 端口
            'hostport'          => Env::get('database.hostport', '3306'),
            // 数据库连接参数
            'params'            => [],
            // 数据库编码默认采用utf8
            'charset'           => Env::get('database.charset', 'utf8'),
            // 数据库表前缀
            'prefix'            => Env::get('database.prefix', 'ea_'),

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'            => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'       => false,
            // 读写分离后 主服务器数量
            'master_num'        => 1,
            // 指定从服务器序号
            'slave_no'          => '',
            // 是否严格检查字段是否存在
            'fields_strict'     => true,
            // 是否需要断线重连
            'break_reconnect'   => false,
            // 监听SQL
            'trigger_sql'       => true,
            // 开启字段缓存
            'fields_cache'      => false,
            // 字段缓存路径
            'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
        ],
        'mysql2' => [
            // 数据库类型
            'type'              => Env::get('database2.type', 'mysql'),
            // 服务器地址
            // 'hostname'          => Env::get('database2.hostname', '192.168.98.7'),
            'hostname'          => Env::get('database2.hostname', '183.6.86.105'),
            // 数据库名
            'database'          => Env::get('database2.database', 'bi'),
            // 用户名
            'username'          => Env::get('database2.username', 'bi'),
            // 密码
            'password'          => Env::get('database2.password', 'WAwFEb772YXDYAza'),
            // 端口
            'hostport'          => Env::get('database2.hostport', '3306'),
            // 数据库连接参数
            'params'            => [],
            // 数据库编码默认采用utf8
            'charset'           => Env::get('database2.charset', 'utf8'),
            // 数据库表前缀
            'prefix'            => Env::get('database2.prefix', 'sp_'),
        ],
        'tianqi' => [
            // 数据库类型
            'type'              => Env::get('database3.type', 'mysql'),
            // 服务器地址
            'hostname'          => Env::get('database3.hostname', '42.193.181.241'),
            // 数据库名
            'database'          => Env::get('database3.database', 'tianqi'),
            // 用户名
            'username'          => Env::get('database3.username', 'tianqi'),
            // 密码
            'password'          => Env::get('database3.password', 'WN3WktaaBERmbxjJ'),
            // 端口
            'hostport'          => Env::get('database3.hostport', '3306'),
            // 数据库连接参数
            'params'            => [],
            // 数据库编码默认采用utf8
            'charset'           => Env::get('database3.charset', 'utf8'),
            // 数据库表前缀
            'prefix'            => Env::get('database3.prefix', ''),
        ]
        // 更多的数据库配置信息
    ],
];
