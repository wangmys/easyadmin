"use strict";
layui.use(["okUtils", "okCountUp",'table'], function () {
    var table = layui.table;
    var okUtils = layui.okUtils;
    var $ = layui.jquery;
    var shopname =$('#shopname').html();
    okLoading.close();
    $('.xbxb').click(function (d) {
        beach(d.target.text.trim(),shopname);
    })


    $('.csdj').click(function (d) {
        switch (d.target.text.trim()) {
            case '所有未收':
                table.reload('wsdj', {
                    url: "/shop/csdj",
                    where:{
                        shopname:shopname,
                        code:2

                    }
                    , page: {
                        curr: 1 //重新从第 1 页开始
                    }

                });
                break;
            case '仓库发货':
                table.reload('wsdj', {
                    url: "/shop/csdj",
                    where:{
                        shopname:shopname,
                        code:0

                    }
                    , page: {
                        curr: 1 //重新从第 1 页开始
                    }

                });
                break;
            case '店铺调拨':
                table.reload('wsdj', {
                    url: "/shop/csdj",
                    where:{
                        shopname:shopname,
                        code:1

                    }
                    , page: {
                        curr: 1 //重新从第 1 页开始
                    }

                });
                break;

        }
        beach(d.target.text.trim(),shopname);
    })
    let userTable = table.render({
        elem: '#wsdj',
        url: '/shop/csdj',
        height:280,
        method:'post',
        size: "sm",
        where:{
            shopname:shopname,
            code:2

        },
        cols: [[
            {field: "outbill", title: "单据类型", width: 100, fixed: "left"},
            {field: "bill", title: "单号", width: 120},
            {field: "outteam", title: "发货方", width: 110},
            {field: "Date", title: "日期",width: 100,templet:'<div>{{ layui.util.toDateString(d.Date, "yyyy-MM-dd") }}</div>'},

        ]],
        done: function (res, curr, count) {
            console.info(res, curr, count);
            var that = this.elem.next();
            for(let i in res.data){
                let ID= res.data[i]
                var time=Date.parse(new Date());
                var lasttime=Date.parse(ID.Date);
                var day=parseInt((time-lasttime)/(1000*60*60*24));
                if(day >3 ){
                    let tr = that.find(".layui-table-box tbody tr[data-index='" + i + "']");
                    tr.css("color", "red");
                }

            }
            let tr = that.find(".layui-table-tool");
            tr.hide();

        }
    });



    $('.xcxc').click(function (d) {
        kc(d.target.text.trim(),shopname);
    })

    beach('顶级',shopname);
    server(shopname);
    kc('上装',shopname);
    hour(shopname);

    function hour(shop) {
        $.ajax({
            url: "/shop/hour",
            type: "POST",
            async: true,
            data:{
                shopname:shop
            },
            cache:false,
            dataType: "json",
            success: function(resonpe){
                sdxsqxtOption['xAxis']['data'] = resonpe['col'];
                sdxsqxtOption['series'][0]['data'] = resonpe['data'];
                var userSourceTodayChart = echarts.init($("#sdxsqxt")[0], "themez");
                userSourceTodayChart.setOption(sdxsqxtOption);
                okUtils.echartsResize([userSourceTodayChart]);



            },
            error:function(err){
            }
        });

    }
    function kc(pl,shop) {
        $.ajax({
            url: "/shop/kcjgt",
            type: "POST",
            async: true,
            data:{
                pl:pl,
                shopname:shop
            },
            cache:false,
            dataType: "json",
            success: function(resonpe){
                kucunOption['series'][0]['data'] =resonpe['data'];
                kucunOption['legend']['data'] = resonpe['title'];
                var kucun = echarts.init($("#kucun")[0], "themez");
                kucun.setOption(kucunOption);
                okUtils.echartsResize([kucun]);
            },
            error:function(err){
            }
        });

    }
    function server(shop) {
        $.ajax({
            url: "/shop/shopseven",
            type: "POST",
            async: true,
            data:{
                shopname:shop
            },
            cache:false,
            dataType: "json",
            success: function(resonpe){
                yzxszztOption['xAxis']['data'] = resonpe['col'];
                yzxszztOption['series'][0]['data'] = resonpe['data'];
                var userSourceTodayChart = echarts.init($("#yzxszzt")[0], "themez");
                userSourceTodayChart.setOption(yzxszztOption);
                okUtils.echartsResize([userSourceTodayChart]);

            },
            error:function(err){
            }
        });

    }
    function beach(pl,shop) {
        $.ajax({
            url: "/shop/plzb",
            type: "POST",
            async: true,
            data:{
                pl:pl,
                shopname:shop
            },
            cache:false,
            dataType: "json",
            success: function(resonpe){
                xsbztOption['legend']['data'] = resonpe['title'];
                xsbztOption['series'][0]['data'] = resonpe['data'];
                var userSourceTodayChart = echarts.init($("#xsbzt")[0], "themez");
                userSourceTodayChart.setOption(xsbztOption);
                okUtils.echartsResize([userSourceTodayChart]);


            },
            error:function(err){
            }
        });

    }
    var kucunOption = {
        title: {show: false, text: '库存图', subtext: '纯属虚构', x: 'center'},
        tooltip: {trigger: 'item', formatter: "{a} <br/>{b} : {c} ({d}%)"},
        legend: { left: 'center',
            top: 'top', data: ['上装', '内搭', '配饰', '下装', '鞋履']},
        series: [
            {
                name: '销售数量:', type: 'pie', radius: '55%', center: ['50%', '60%'],
                data: [{value: 335, name: '上装'},
                    {value: 310, name: '内搭'},
                    {value: 234, name: '配饰'},
                    { value: 135,name: '下装' },
                    {value: 1548, name: '鞋履'}],
                itemStyle: {emphasis: {shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)'}}
            }
        ]
    };
    var yzxszztOption = {
        color: "#03a9f3",
        xAxis: {type: 'category', data: ['一', '二', '三', '四', '五', '六', '日']},
        yAxis: {type: 'value'},
        series: [{data: [120, 200, 150, 80, 70, 110, 130], type: 'bar',
            label: {
                normal: {
                    show: true,//是否显示
                    position: 'top',//文字位置
                    formatter: '{c}万'//c后面加单位

                }
            },
        },
        ],

    };
    var xsbztOption = {
        title: {show: false, text: '7天销售饼状图', subtext: '纯属虚构', x: 'center'},
        tooltip: {trigger: 'item', formatter: "{a} <br/>{b} : {c} ({d}%)"},
        legend: { left: 'center',
            top: 'top', data: ['上装', '内搭', '配饰', '下装', '鞋履']},
        series: [
            {
                name: '销售数量:', type: 'pie', radius: '55%', center: ['50%', '60%'],
                data: [{value: 335, name: '上装'},
                    {value: 310, name: '内搭'},
                    {value: 234, name: '配饰'},
                    { value: 135,name: '下装' },
                    {value: 1548, name: '鞋履'}],
                itemStyle: {emphasis: {shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)'}}
            }
        ]
    };
    //库存饼状图

    /**
     * 今日销售时段图
     */
    var sdxsqxtOption = {
        backgroundColor: "#fff",
        tooltip: {
            trigger: 'axis',
            show: true,
        },
        legend: {
            show: true,
            icon: 'circle',
            top: 2,
            textStyle: {
                fontSize: 10,
                color: '#c8c8c8'
            },
        },
        grid: {
            left: '5%',
            right: '5%',
            top: '15%',
            bottom: '6%',
            containLabel: true
        },
        xAxis: {
            axisLine: {
                show: false
            },
            axisTick: {
                show: false
            },
            axisLabel: {
                interval: 0,
            },
            data: [ '7', '8', '9', '10', '11', '12', '一', '二', '三', '四', '五', '六', '六', '20', '21', '22', '23']
        },
        yAxis: {
            axisLine: {
                show: false,
            },
            axisTick: {
                show: false
            },
        },
        series: [
            {
                name: '今日',
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 13,
                lineStyle: {
                    normal: {
                        width: 3,
                        shadowColor: 'rgba(155, 18, 184, .4)',
                        shadowBlur: 5,
                        shadowOffsetY: 20,
                        shadowOffsetX: 0,
                        color: '#24b314',
                    }
                },
                itemStyle: {
                    color: '#24b314',
                    borderColor: "#fff",
                    borderWidth: 2,
                },
                data: [1, 0, 14, 35, 51, 49, 62, 72, 92, 182, 192, 262, 362]
            },

        ]
    };

    /**
     * 本周用户访问来源图表
     */
    function initsdxsqxt() {
        var sdxsqxt = echarts.init($("#sdxsqxt")[0], "themez");
        sdxsqxt.setOption(sdxsqxtOption);
        okUtils.echartsResize([sdxsqxt]);
    }


    //库存图
    //今日时段销售
    initsdxsqxt();

});


