define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.inventory/index',
        dress_url: 'system.dress.dress/index',
        question_index: '/admin/system.dress.inventory/question',
        list_url: '/admin/system.dress.inventory/index',
        finish_rate: '/admin/system.dress.inventory/finish_rate',
        rate: '/system.dress.inventory/rate',
        rate_url: '/admin/system.dress.inventory/rate',
        gather_url: '/admin/system.dress.inventory/gather',
        export_url: 'system.dress.inventory/index_export',
    };
    var table = layui.table
    function isRed(d,obj){
        if(Number(d[obj.field]) < Number(d.config[obj.field])){
            str = '';
            var key = obj.field + '_';
            if(d._data[key]){
                // str = ' / ' + d._data[obj.field + '_'];
            }
            //得到当前行数据，并拼接成自定义模板
            return '<span style="width: 100%;display: block;background: rgba(255,0,0,.2)">'+ Number(d[obj.field]) + str +'</span>'
        }
        if(d[obj.field] === null){
            d[obj.field] = '';
        }
        return d[obj.field];
    }

    var Controller = {

        index: function () {
            var $get = $("#where").val();
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.dress.inventory/index',
                   export_url: 'system.dress.inventory/index_export'
                },
                search:false,
                where:{filter:$get},
                height: 760,
                limit: 1000,
                toolbar:[
                    'custom_export'
                ],
                limits:[100,200,500,1000],
                cols: [[
                    // {type: "checkbox"},
                    {field: 'Deadline', with: '10%', title: '截止日期',search: true},
                    {field: '店铺名称', minWith: 134, title: '店铺名称',search: true},
                    {field: '商品负责人', minWith: 134, title: '商品负责人',search: true},
                    {field: '背包', minWith: 134, title: '背包',templet: function(d){
                        return isRed(d,this);
                      },searchValue:this.minWith,search:defaultOp,search: false},
                    {field: '挎包', minWith: 134, title: '挎包',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '领带', minWith: 134, title: '领带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '帽子', minWith: 134, title: '帽子',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '内裤', minWith: 134, title: '内裤',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '皮带', minWith: 134, title: '皮带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '袜子', title: '袜子', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '手包', title: '手包', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '胸包', title: '胸包', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                ]]
            });
            var table = layui.table;
            //转换静态表格
            var list_table = table.init('list', {
                done: function (res, curr, count) {
                    // var that = this.elem.next();
                    // for(res.data in item){
                    //     var tr = that.find("[data-index=" + index + "]").children();
                    //         tr.each(function (i,value) {
                    //             $(this).css("background-color", "rgba(255,0,0,.2)");//单元格背景颜色
                    //         })
                    // }
                    // $('#list_table').find('tr').css({'background':'#5FB878','color':'white'}).siblings().removeAttr('style')
                }
            });
            ea.listen();
        },
        question: function () {
            var $get = $("#where").val();
            ea.table.render({
                url: init.question_index,
                search:false,
                where:{filter:$get},
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    // {type: "checkbox"},
                    {field: '商品负责人', minWith: 134, title: '商品负责人'},
                    {field: '背包', minWith: 134, title: '背包(店铺数)',event:'pp'},
                    {field: '挎包', minWith: 134, title: '挎包(店铺数)',event:this.field},
                    {field: '领带', minWith: 134, title: '领带(店铺数)',event:this.field},
                    {field: '帽子', minWith: 134, title: '帽子(店铺数)',event:this.field},
                    {field: '内裤', minWith: 134, title: '内裤(店铺数)',event:this.field},
                    {field: '皮带', minWith: 134, title: '皮带(店铺数)',event:this.field},
                    {field: '袜子', title: '袜子(店铺数)', minWith: 134},
                    {field: '手包', title: '手包(店铺数)', minWith: 134},
                    {field: '胸包', title: '胸包(店铺数)', minWith: 134},
                    {
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看对比',
                                url: init.rate,
                                method: 'open',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                                field:'商品负责人',
                                extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' "
                            }],
                            [{
                                text: '查看详情',
                                url: init.index_url,
                                method: 'open',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                                field:'商品负责人',
                                extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' "
                            }],
                        ],
                        fixed: 'right'
                    }
                ]]
            });
            table.on('tool(currentTableRenderId_LayFilter)', function(obj){
                console.log(obj)
            });
            ea.listen();
        },
        finish_rate: function () {
            // 自定义模块
          layui.config({
            base: '/static/plugs/lay-module/soul-table/',   // 模块目录
            version: 'v1.6.4'
          }).extend({             // 模块别名
            soulTable: 'soulTable'
          });
          var $get = $("#where").val();
          layui.use(['form', 'table','soulTable'], function () {
                var table = layui.table,
                soulTable = layui.soulTable;
                table.render({
                    elem: '#currentTable'
                    ,url: init.finish_rate
                    ,where:JSON.parse($get)
                    ,height: 700
                    ,page: false
                    ,cols: [
                        [
                            {field: '商品负责人', width: 200, title: '商品负责人', rowspan: 2,fixed:'left'},
                            {title: '背包', colspan: 3},
                            {title: '挎包', colspan: 3},
                            {title: '领带', colspan: 3},
                            {title: '帽子', colspan: 3},
                            {title: '内裤', colspan: 3},
                            {title: '皮带', colspan: 3},
                            {title: '袜子', colspan: 3},
                            {title: '手包', colspan: 3},
                            {title: '胸包', colspan: 3}
                        ],
                        [
                            {field: '背包', width:100, title: '问题店铺',event:'pp',border: {
                            style: 'solid',
                            color: '1E9FFF'
                    }},
                            {field: '背包_1', width:100, title: '已完成'},
                            {field: '背包_2', width:150, title: '未完成店铺数'},
                            {field: '挎包', width:100, title: '问题店铺'},
                            {field: '挎包_1', width:100, title: '已完成'},
                            {field: '挎包_2', width:150, title: '未完成店铺数'},
                            {field: '领带', width:100, title: '问题店铺'},
                            {field: '领带_1', width:100, title: '已完成'},
                            {field: '领带_2', width:150, title: '未完成店铺数'},
                            {field: '帽子', width:100, title: '问题店铺'},
                            {field: '帽子_1', width:100, title: '已完成'},
                            {field: '帽子_2', width:150, title: '未完成店铺数'},
                            {field: '内裤', width:100, title: '问题店铺'},
                            {field: '内裤_1', width:100, title: '已完成'},
                            {field: '内裤_2', width:150, title: '未完成店铺数'},
                            {field: '皮带', width:100, title: '问题店铺'},
                            {field: '皮带_1', width:100, title: '已完成'},
                            {field: '皮带_2', width:150, title: '未完成店铺数'},
                            {field: '袜子', title: '问题店铺', width:100},
                            {field: '袜子_1', title: '已完成', width:100},
                            {field: '袜子_2', title: '未完成店铺数', width:150},
                            {field: '手包', title: '问题店铺', width:100},
                            {field: '手包_1', title: '已完成', width:100},
                            {field: '手包_2', title: '未完成店铺数', width:150},
                            {field: '胸包', title: '问题店铺', width:100},
                            {field: '胸包_1', title: '已完成', width:100},
                            {field: '胸包_2', title: '未完成店铺数', width:150}
                        ]
                    ]
                    ,done: function () {
                        soulTable.render(this)
                    }
                    ,rowEvent: function (obj) {
                        obj.tr.css({'background':'#5FB878','color':'white'}).siblings().removeAttr('style') // 设置当前行颜色
                        // console.log('[父表行单击事件] 当前行对象:', obj.tr) //得到当前行元素对象
                        // console.log('[父表行单机事件] 当前行数据:', obj.data) //得到当前行数据
                    }
                    ,toolEvent: function (obj) {
                        var layEvent = obj.event, // 获取 lay-event 对应的值
                            tr = obj.tr, // 获取当前行 的 dom 对象（如果有的话）
                            data = obj.data; // 当前行数据
                        // layer.msg('更新成功！')
                    }
                });
            })
          ea.listen();
        },
        rate: function () {
            var $get = $("#where").val();
            ea.table.render({
                url: init.rate_url,
                where:JSON.parse($get),
                search:false,
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    {field: '商品负责人', minWith: 134, title: '商品负责人'},
                    {field: '配饰', minWith: 134, title: '配饰'},
                    {field: '问题店铺', minWith: 134, title: '问题店铺'},
                    {field: '已处理', minWith: 134, title: '已处理'},
                    {field: '剩余店铺', minWith: 134, title: '剩余店铺'}
                ]]
            });

            ea.listen();
        },
        gather:function () {
            var $get = $("#where").val();
            // 重写方法
            ea.table.toolSpliceUrl = this.toolSpliceUrl;
            ea.table.render({
                url: init.gather_url,
                where:JSON.parse($get),
                search:false,
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    {field: 'order_num', minWith: 134, title: '序号'},
                    {field: '商品负责人', minWith: 134, title: '商品负责人'},
                    {field: 'name', minWith: 134, title: '检核列表'},
                    {field: 'total', minWith: 134, title: '周一问题个数'},
                    {field: 'not_total', minWith: 134, title: '周一剩余问题数'},
                    {field: 'time', minWith: 134, title: '已逾期天数'},
                    {field: 'this_num', minWith: 134, title: '今日所有问题数'},
                    // {
                    //     title: '周一完成度',
                    //     templet: ea.table.tool,
                    //     operat: [
                    //         [{
                    //             text: '查看详情',
                    //             url: init.index_url,
                    //             method: 'open',
                    //             auth: 'false',
                    //             class: 'layui-btn layui-btn-normal layui-btn-xs',
                    //             field:'商品负责人',
                    //             extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' ",
                    //             title:"周一完成进度"
                    //         }],
                    //     ],
                    //     fixed: 'right'
                    // },
                    {
                        title: '今日所有问题',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看详情',
                                url: init.index_url,
                                method: 'open',
                                auth: 'false',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                                field:'商品负责人',
                                extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' ",
                                title:"今日所有问题"
                            }],
                        ],
                        fixed: 'right'
                    }
                ]]
            });

            ea.listen();
        },
        task_overview:function () {
            // 自定义模块
              layui.config({
                base: '/static/plugs/lay-module/soul-table/',   // 模块目录
                version: 'v1.6.4'
              }).extend({             // 模块别名
                soulTable: 'soulTable'
              });
            layui.use(['form', 'table','soulTable'], function () {
                var table = layui.table,
                soulTable = layui.soulTable;
                table.render({
                    elem: '#currentTable'
                    ,url: '/admin/system.dress.inventory/task_overview'
                    ,page: false
                    ,cols: [
                        [
                            {field: 'num', minWidth: 80, title: '序号'},
                            {field: '商品负责人', minWidth: 80, title: '商品负责人'},
                            {title: '监控列表', minWidth: 50,show: 1, lazy: true, children:[
                            {
                                title: '详情'
                                ,url: function(row){
                                    //row 为当前行数据
                                    return init.gather_url+'?name='+row.商品负责人
                                }
                                ,height: 500
                                ,page: false
                                ,cols: [
                                    [
                                        {field: 'name', title: '检核列表', minWidth: 80 },
                                        {field: 'total', minWidth: 80, title: '周一问题总个数'},
                                        {field: 'not_total', minWidth: 80, title: '周一问题未处理个数(截至周四)'},
                                        {field: 'time', minWidth: 80, title: '已逾期天数'},
                                        {field: 'this_num', minWidth: 80, title: '今日所有问题'},
                                        {title: '查看问题详情', width: 156, templet: '#toolbar'}
                                    ]
                                ]
                                ,done: function () {
                                    soulTable.render(this);
                                }
                                ,toolEvent: function (obj, pobj) {
                                    var childId = this.id; // 通过 this 对象获取当前子表的id
                                    if (obj.event === 'childDel') {
                                        data = obj.data;
                                        console.log(data)
                                        switch (data.type) {
                                            case 'yinliu':
                                                var params = "商品负责人=" + data['商品负责人']
                                                ea.open(
                                                    $(this).attr('data-title'),
                                                    ea.url(init.dress_url)+'?' + params,
                                                    '1400px',
                                                    '800px'
                                                );
                                                break;
                                            case 'accessories':
                                                ea.open(
                                                    $(this).attr('data-title'),
                                                    ea.url(init.index_url)+'?商品负责人=' + data['商品负责人'],
                                                    '1400px',
                                                    '900px'
                                                );
                                                break;
                                        }

                                    }
                            }
                            ,childOpen: function(obj) {
                                console.log(obj.tr) //得到当前行元素对象
                                console.log(this.url)
                            }
                            }
                            ]},
                            {field: 'total', minWidth: 80, title: '问题数量'},
                            {field: 'not_total', minWidth: 85, title: '未完成数量'},
                            {field: 'ok_total', minWidth: 80, title: '已完成数量'},
                        ]
                    ]
                    ,done: function () {
                        soulTable.render(this)
                    }
                });
            })

            ea.listen();
        },
        toolSpliceUrl(url, field, data) {
            if(data.type !== undefined){
                switch (data.type){
                    case 'yinliu':
                        url = init.dress_url;
                        break;
                    case 'accessories':
                        url = init.index_url;
                        break;
                }
            }
            url = url.indexOf("?") !== -1 ? url + '&' + field + '=' + data[field] : url + '?' + field + '=' + data[field];
            return url;
        }
    };
    return Controller;
});