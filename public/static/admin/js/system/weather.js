define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.weather/index',
        city_url: 'system.weather/city',
        tianqi_url: 'system.weather/tianqi_url',
        list_index: '/admin/system.weather/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.weather/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                // 省列表
                var province_list = res.province_list;
                // 区域列表
                var area_list = res.area_list;
                // 商品负责人
                var liable_list = res.liable_list;
                // 地级市
                var city_list = res.city_list;
                // 气温区域
                var wenqu_list = res.wenqu_list;
                // 温带
                var wendai_list = res.wendai_list;
                // 店铺
                var store_list = res.store_list;
                //绑定城市 字段权限
                var if_can_see = res.if_can_see;
                var mathod = res.mathod;
                var CustomerGrade = res.CustomerGrade;
                var last_update_time = res.last_update_time;
                var bang_url_list = res.bang_url_list;
                var cols = [
                    // {type: "checkbox",fixed:'left'},
                    {field: 'State', width: 70, title: '省份',fixed:'left',search: 'select',selectList:province_list,laySearch:true},
                    {hide:true, field: 'City', width: 50, title: '市',fixed:'left',search: 'select',selectList:city_list,laySearch:true},
                    // {field: 'Region', width: 100, title: '区域',fixed:'left',search: 'select',selectList:area_list,laySearch:true},
                    {field: 'CustomItem30', width: 70, title: '温带',fixed:'left',search: false,search: 'select',selectList:wendai_list},
                    {field: 'CustomItem36', width: 70, title: '温区',fixed:'left',search: false,search: 'select',selectList:wenqu_list},
                    {field: 'CustomerName', width: 90, title: '店铺',fixed:'left',search: 'xmSelect',selectList:store_list,laySearch:true},
                    {field: 'liable', width: 100, title: '商品负责人',fixed:'left',search: 'xmSelect',selectList:liable_list},
                    {hide:true, field: 'Mathod', width: 50, title: '经营模式',fixed:'left',search: 'xmSelect',selectList:mathod},
                    {hide:true, field: 'CustomerGrade', width: 50, title: '店铺等级',fixed:'left',search: 'xmSelect',selectList:CustomerGrade,laySearch:true},
                    {hide:true, field: 'url_2345_cid', width: 70, title: '绑网址',fixed:'left',search: false,search: 'select',selectList:bang_url_list},
                    // {field: 'City', width: 100, title: '地级市',fixed:'left',search: 'select',selectList:city_list,laySearch:true},
                    // {field: 'BdCity', width: 90, title: '绑定城市',fixed:'left',search: false},
                    // {field: 'SendGoodsGroup', width: 150, title: '温度带',fixed:'left'},
                ];

                //判断商品专员是否可以查看该字段
                if (if_can_see == 1) {
                    cols.push({field: 'BdCity', width: 90, title: '绑定城市',fixed:'left',search: false})
                }

                var data = res.data;
                data.forEach(function (val,index){
                    // console.log(val);
                    if(index == 0){
                        cols.push({
                            width: 60 , search:false , field: val, title: val, title: val
                        })
                    }else{
                        cols.push({
                            width: 60 , search:false , field: val
                        })
                    }
                })
                // console.log(cols);

                cols.push({
                    width: 120,
                    title: '操作',
                    templet: ea.table.tool,
                    operat: [
                        [{
                            text: '绑城市',
                            url: init.city_url,
                            method: 'open',
                            auth: '',
                            class: 'layui-btn layui-btn-normal layui-btn-xs',
                            field:'CustomerId'
                        }],
                        [{
                            text: '绑网址',
                            url: init.tianqi_url,
                            method: 'open',
                            auth: '',
                            class: 'layui-btn layui-btn-normal layui-btn-xs',
                            field:'CustomerId'
                        }],
                    ],
                    fixed: 'right'
                })

                ea.table.render({
                    url: init.list_index,
                    search:true,
                    height: 855,
                    limit: 1000,
                    toolbar:[
                        [
                            {
                                text: '<b style="color:black;font-size:25px;">店铺温度是定时获取,会与天气网的实时温度存在偏差,请各位知悉</b>',
                                method: 'none',
                                auth: '',
                                class: 'layui-badge layui-bg-red',
                                event:'guangzhou'
                            }
                        ],

                        [
                            {
                                text: "<b style='color:black;font-size:25px;'>&nbsp; &nbsp; &nbsp; &nbsp; 最后更新时间为："+last_update_time+"&nbsp; &nbsp; &nbsp; &nbsp; </b>",
                                method: 'none',
                                auth: '',
                                class: 'layui-badge layui-bg-blue',
                                event:'guangzhou'
                            }
                        ]

                    ],
                    limits:[1000,2000,3000],
                    cols: [cols],
                    done:function (res, curr, count) {
                        // var that = this.elem.next();
                        // var config = res.data[0].config;
                        // res.data.forEach(function (item,index) {
                        //     var tr = that.find("[data-index=" + index + "]").children();
                        //         tr.each(function (i,value) {
                        //             var key = $(value).data('field');
                        //             if(item['_'+key]){
                        //                 $(this).css("background-color", item['_'+key]);//单元格背景颜色
                        //             }
                        //         })
                        // })
                        var today_date = res.today_date;

                        $('th[data-field="'+today_date+'"]').css({
                            'background-color': 'rgb(110, 170, 46)', 'color': '#000', 'font-weight':'bold'
                        });

                        res.data.forEach(function (item,index) {
                            if (item['url_2345_cid']) {
                                $('tr[data-index="'+index+'"] td[data-field="34"] a[data-title="绑网址"]').addClass('layui-bg-red');
                            }
                        })
                        

                    }
                });

                ea.listen();

                var setColor = function(d,obj){
                    var min_c = d[obj.field]['min_c'] ? d[obj.field]['min_c'] : '';
                    var bgCol = '';
                    var fontCol = '';
                    if (min_c < 10) {
                        bgCol = '#1a6bd7';   
                        fontCol = '#ffffff';   
                    }else if (min_c >= 10 && min_c < 18) {
                        bgCol = '#68b8f5';   
                        fontCol = '#ffffff';  
                    } else if (min_c >= 18 && min_c < 22) {
                        bgCol = '#faf1a4';   
                        fontCol = '#000000';  
                    } else if (min_c >= 22 && min_c < 26) {
                        bgCol = '#fecc51';   
                        fontCol = '#000000';  
                    }
                    return `<span style="width: 100%;display: block; background:${bgCol}; color:${fontCol}" >${d[obj.field]['min_c']} ~ ${d[obj.field]['max_c']} ℃</span>`
                }
            },function (res) {
                alert('失败')
            })
        },
        city: function () {
            ea.listen();
        },
        tianqi_url: function () {
            ea.listen();
        }
    };
    return Controller;
});