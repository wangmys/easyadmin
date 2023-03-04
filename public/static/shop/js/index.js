define(["jquery", "easy-admin", "echarts", "echarts-theme", "miniAdmin", "miniTab"], function ($, ea, echarts, undefined, miniAdmin, miniTab) {

    var Controller = {
        index: function () {
            var options = {
                iniUrl: ea.url('ajax/initAdmin'),    // 初始化接口
                clearUrl: ea.url("ajax/clearCache"), // 缓存清理接口
                urlHashLocation: true,      // 是否打开hash定位
                bgColorDefault: false,      // 主题默认配置
                multiModule: true,          // 是否开启多模块
                menuChildOpen: false,       // 是否默认展开菜单
                loadingTime: 0,             // 初始化加载时间
                pageAnim: true,             // iframe窗口动画
                maxTabNum: 20,              // 最大的tab打开数量
            };
            miniAdmin.render(options);

            $('.login-out').on("click", function () {
                ea.request.get({
                    url: 'login/out',
                    prefix: true,
                }, function (res) {
                    ea.msg.success(res.msg, function () {
                        window.location = ea.url('login/index');
                    })
                });
            });

            // 执行
            this.linkWs();
        },
        linkWs: function () {


            let ws = new WebSocket("ws://127.0.0.1:2346")

            ws.onopen = function (event) {
                var data = {
                    uid:window.CONFIG.ADMIN_ID,
                    value:'54545'
                }
                // 发送消息
                ws.send(JSON.stringify(data));
                console.log('建立连接');
            }

            ws.onclose = function (event) {
                console.log('连接关闭');
            }

            ws.onmessage = function (event) {
                //所要执行的操作
                msg = {
                    uid:'all'
                };
                if(data = event.data){
                    msg = data;
                    console.log(event.data)
                }
                switch (msg){
                    case 'all':
                        console.log(444)
                    default:
                         layer.open({
                            type: 1,
                            title: '系统公告' + '<span style="float: right;right: 1px;font-size: 12px;color: #b1b3b9;margin-top: 1px">' + '</span>',
                            offset: 'rb',
                            shade: 0.8,
                            id: 'layuimini-notice',
                            btn: ['查看', '取消'],
                            btnAlign: 'c',
                            moveType: 1,
                            content: `<div id="layuimini-notice" class="layui-layer-content"><div style="padding:15px 20px; text-align:justify; line-height: 22px;border-bottom:1px solid #e2e2e2;background-color: #2f4056;color: #ffffff">
        <div style="text-align: center;margin-bottom: 20px;font-weight: bold;border-bottom:1px solid #718fb5;padding-bottom: 5px"><h4 class="text-danger">新增treetable插件和菜单管理样式</h4></div>
        <div style="font-size: 12px">
                                        ` + msg + `
                                    </div>
        </div>
        </div>`,
                            success: function (layero) {
                                var btn = layero.find('.layui-layer-btn');
                                btn.find('.layui-layer-btn0').attr({
                                    href: 'https://gitee.com/zhongshaofa/layuimini',
                                    target: '_blank'
                                });
                            }
                });
                }
            }

            ws.onerror = function () {
                alert('websocket通信发生错误！');
            }

            window.onbeforeunload = function () {
                console.log('关闭连接')
                ws.close();
            }

            // ws.onopen = function() {  //绑定连接事件
            //     console.log("连接成功");
            //     //每30秒发送一次心跳
            //     setInterval(function(){
            //         ws.send(JSON.stringify({'type':"peng"}));
            //         console.log('发送心跳...');
            //
            //     },10000)
            //
            // };
            //
            // ws.onmessage = function(evt) {//绑定收到消息事件
            //     data = JSON.parse(evt.data)
            //     console.log(evt.data);
            //     //这里处理收到的消息, type类型有两种: connectin、deposit如果有deposit要提示
            //
            // };
            //
            //
            // ws.onclose = function(evt) { //绑定关闭或断开连接事件
            // 　　console.log("连接已关闭");
            // };

        },
        welcome: function () {

            miniTab.listen();

            /**
             * 查看公告信息
             **/
            $('body').on('click', '.layuimini-notice', function () {
                var title = $(this).children('.layuimini-notice-title').text(),
                    noticeTime = $(this).children('.layuimini-notice-extra').text(),
                    content = $(this).children('.layuimini-notice-content').html();
                var html = '<div style="padding:15px 20px; text-align:justify; line-height: 22px;border-bottom:1px solid #e2e2e2;background-color: #2f4056;color: #ffffff">\n' +
                    '<div style="text-align: center;margin-bottom: 20px;font-weight: bold;border-bottom:1px solid #718fb5;padding-bottom: 5px"><h4 class="text-danger">' + title + '</h4></div>\n' +
                    '<div style="font-size: 12px">' + content + '</div>\n' +
                    '</div>\n';
                layer.open({
                    type: 1,
                    title: '系统公告' + '<span style="float: right;right: 1px;font-size: 12px;color: #b1b3b9;margin-top: 1px">' + noticeTime + '</span>',
                    area: '300px;',
                    shade: 0.8,
                    id: 'layuimini-notice',
                    btn: ['查看', '取消'],
                    btnAlign: 'c',
                    moveType: 1,
                    content: html,
                    success: function (layero) {
                        var btn = layero.find('.layui-layer-btn');
                        btn.find('.layui-layer-btn0').attr({
                            href: 'https://gitee.com/zhongshaofa/layuimini',
                            target: '_blank'
                        });
                    }
                });
            });

            /**
             * 报表功能
             */
            var echartsRecords = echarts.init(document.getElementById('echarts-records'), 'walden');
            var optionRecords = {
                title: {
                    text: '访问统计'
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['邮件营销', '联盟广告', '视频广告', '直接访问', '搜索引擎']
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: '邮件营销',
                        type: 'line',
                        stack: '总量',
                        data: [120, 132, 101, 134, 90, 230, 210]
                    },
                    {
                        name: '联盟广告',
                        type: 'line',
                        stack: '总量',
                        data: [220, 182, 191, 234, 290, 330, 310]
                    },
                    {
                        name: '视频广告',
                        type: 'line',
                        stack: '总量',
                        data: [150, 232, 201, 154, 190, 330, 410]
                    },
                    {
                        name: '直接访问',
                        type: 'line',
                        stack: '总量',
                        data: [320, 332, 301, 334, 390, 330, 320]
                    },
                    {
                        name: '搜索引擎',
                        type: 'line',
                        stack: '总量',
                        data: [820, 932, 901, 934, 1290, 1330, 1320]
                    }
                ]
            };
            echartsRecords.setOption(optionRecords);
            window.addEventListener("resize", function () {
                echartsRecords.resize();
            });
        },
        editAdmin: function () {
            ea.listen();
        },
        editPassword: function () {
            ea.listen();
        }
    };
    return Controller;
});
