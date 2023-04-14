"use strict";
layui.use(["okUtils",'jquery'], function () {
    var okUtils = layui.okUtils;
    var $ = layui.jquery;
    okLoading.close();
    $('#cszp').click(function () {
        beach('K391000015');
    })
    $('#csgj').click(function () {
        beach('K391000017');
    })
    $('#jnzp').click(function () {
        beach('K391000019');
    })

    $('#jngj').click(function () {
        beach('K391000018');
    })
    var userActiveTodayChartOption = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        },
        legend: {
            data: ['直接访问', '邮件营销', '联盟广告', '视频广告', '搜索引擎']
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'value'
        },
        yAxis: {
            type: 'category',
            data: ['7-29', '周二', '周三', '周四', '周五', '周六', '周日']
        },
        series: [
            {
                name: '直接访问',
                type: 'bar',
                stack: '总量',
                data: [320, 302, 301, 334, 390, 330, 320]
            },
            {
                name: '邮件营销',
                type: 'bar',
                stack: '总量',
                data: [120, 132, 101, 134, 90, 230, 210]
            },
            {
                name: '联盟广告',
                type: 'bar',
                stack: '总量',
                data: [220, 182, 191, 234, 290, 330, 310]
            },
            {
                name: '视频广告',
                type: 'bar',
                stack: '总量',
                data: [150, 212, 201, 154, 190, 330, 410]
            },
            {
                name: '搜索引擎',
                type: 'bar',
                stack: '总量',
                data: [820, 832, 901, 934, 1290, 1330, 1320]
            }
        ]
    };

    /**
     * 今日用户活跃量图表
     */
    function initUserActiveTodayChart() {
        var userActiveTodayChart = echarts.init($("#userActiveTodayChart")[0], "themez");
        userActiveTodayChart.setOption(userActiveTodayChartOption);
        okUtils.echartsResize([userActiveTodayChart]);
        // userActiveTodayChart.on("click", pieConsole);
    }




    var userSourceTodayChartOption = {
        title: {show: false, x: 'center'},

        tooltip: {trigger: 'item', formatter: "{a} <br/>{b} : {c} ({d}%)"},
        legend: {
            left: 'center',
            top: 'top',
            data: []
        },
        series: [
            {
                name: '库存', type: 'pie', radius: '55%', center: ['50%', '60%'],
                data: [],
                itemStyle: {emphasis: {shadowBlur: 5, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)'}}
            }
        ]
    };



    beach('K391000015');


    function beach(f) {
        $.ajax({
            url: "stockbzt",
            type: "POST",
            async: true,
            data:{
                stockid:f,
            },
            cache:false,
            dataType: "json",
            success: function(resonpe){
                userSourceTodayChartOption['legend']['data'] = resonpe['title'];
                userSourceTodayChartOption['series'][0]['data'] = resonpe['data'];
                var userSourceTodayChart = echarts.init($("#userSourceTodayChart")[0], "themez");
                userSourceTodayChart.setOption(userSourceTodayChartOption);
                okUtils.echartsResize([userSourceTodayChart]);

            },
            error:function(err){
            }
        });

    }
    /**
     * 今日用户访问来源图表
     */
    function initUserSourceTodayChart() {
        var userSourceTodayChart = echarts.init($("#userSourceTodayChart")[0], "themez");
        userSourceTodayChart.setOption(userSourceTodayChartOption);
        okUtils.echartsResize([userSourceTodayChart]);
        // userSourceTodayChart.on("click", pieConsole);
    }

    var userSourceWeekChartOption = {
        title: {show: true, text: ''},
        tooltip: {trigger: 'axis', axisPointer: {type: 'cross', label: {backgroundColor: '#6a7985'}}},
        legend: {data: ['营销222', '联盟广告', '视频广告', '直接访问', '搜索引擎2', '营销2', '联盟广告3', '视频广告4', '直接访问5', '搜索引擎6']},
        toolbox: {show: false, feature: {saveAsImage: {}}},
        grid: {left: '3%', right: '4%', bottom: '3%', containLabel: true},
        xAxis: [{type: 'category', boundaryGap: false, data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']}],
        yAxis: [{type: 'value', splitLine: {show: false},}],
        series: [
            {
                name: '营销222',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [120, 132, 101, 134, 90, 230, 210]
            },
            {
                name: '联盟广告',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [220, 182, 191, 234, 290, 330, 310]
            },
            {
                name: '视频广告',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [150, 232, 201, 154, 190, 330, 410]
            },
            {
                name: '直接访问',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {normal: {}},
                data: [320, 332, 301, 334, 390, 330, 320]
            },
            {
                name: '搜索引擎',
                type: 'line',
                stack: '总量',
                smooth: true,
                label: {normal: {show: true, position: 'top'}},
                areaStyle: {normal: {}},
                data: [370, 932, 901, 934, 1290, 1330, 1320]
            },
            {
                name: '营销2',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [120, 132, 101, 134, 90, 230, 210]
            },
            {
                name: '联盟广告3',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [220, 182, 191, 234, 290, 330, 310]
            },
            {
                name: '视频广告4',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {},
                data: [150, 232, 201, 154, 190, 330, 410]
            },
            {
                name: '直接访问5',
                type: 'line',
                stack: '总量',
                smooth: true,
                areaStyle: {normal: {}},
                data: [320, 332, 301, 334, 390, 330, 320]
            },
            {
                name: '搜索引擎6',
                type: 'line',
                stack: '总量',
                smooth: true,
                label: {normal: {show: true, position: 'top'}},
                areaStyle: {normal: {}},
                data: [370, 932, 901, 934, 1290, 1330, 1320]
            }
        ]
    };

    /**
     * 本周用户访问来源图表
     */
    function initUserSourceWeekChart() {
        var userSourceWeekChart = echarts.init($("#userSourceWeekChart")[0], "themez");
        userSourceWeekChart.setOption(userSourceWeekChartOption);
        okUtils.echartsResize([userSourceWeekChart]);
        // userSourceWeekChart.on("click", pieConsole);
    }

    function pieConsole(param) {
        console.log(param);
        alert(param.value);
        alert(param.name);

        //刷新页面
        // location.reload();
        // window.location.reload();
    }


    initUserActiveTodayChart();
    initUserSourceWeekChart();
});


