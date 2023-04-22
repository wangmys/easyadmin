define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.accessories/index',
        list_index: '/admin/system.dress.accessories/list',
        add_url: 'system.dress.accessories/add',
        edit_url: 'system.dress.accessories/edit',
        delete_url: 'system.dress.accessories/delete',
        export_url : 'system.dress.accessories/index_export'
    };

    function isRed(d,obj){
        if(d[obj.field] === null){
            d[obj.field] = 0;
        }
        if(parseInt(d[obj.field]) < parseInt(d.config[obj.field])){
            //得到当前行数据，并拼接成自定义模板
            return '<span style="width: 100%;display: block;background: rgba(255,0,0,.2)">'+ d[obj.field] +'</span>'
        }
        return d[obj.field];
    }

    var Controller = {

        index: function () {
            var table = ea.table;
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                init: init,
                event:false,
                search:false,
                height: 620,
                limit: 1000,
                limits:[100,200,500,1000],
                toolbar:[
                    'custom_export'
                ],
                cols: [[
                    // {type: "checkbox"},
                    {field: '省份', width: 134, title: '省份',search: 'select',searchKey:'province_list',setSearch:true},
                    {field: '店铺名称', width: 134, title: '店铺名称',search: true},
                    {field: '商品负责人', width: 134, title: '商品负责人',search: true},
                    {field: '背包', width: 134, title: '背包',templet: function(d){
                        return isRed(d,this);
                      },searchValue:this.width,search:defaultOp},
                    {field: '挎包', width: 134, title: '挎包',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '领带', width: 134, title: '领带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '帽子', width: 134, title: '帽子',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '内裤', width: 134, title: '内裤',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '皮带', width: 134, title: '皮带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '袜子', title: '袜子', width: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '手包', title: '手包', width: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '胸包', title: '胸包', width: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    // {field: '配饰汇总', title: '配饰汇总', width: 122},
                    // {field: '配饰SKC', title: '配饰SKC', width: 122}
                ]],
                done:function(res, curr, count) {

                },
                parseData: function(res){ //res 即为原始返回的数据

                }
            });

            var table = layui.table;
            //转换静态表格
            var list_table = table.init('list', {
            });

            ea.listen();
        },
        list: function () {
            var table = ea.table;
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                url: init.list_index,
                search:true,
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    // {type: "checkbox"},
                    {field: '省份', width: 134, title: '省份',search: true,fixed:'left'},
                    {field: '店铺名称', width: 134, title: '店铺名称',search: true},
                    {field: '商品负责人', width: 134, title: '商品负责人',search: true},
                    {field: '背包', width: 134, title: '背包',searchValue:this.width,search:defaultOp,search: false},
                    {field: '挎包', width: 134, title: '挎包',search:defaultOp,search: false},
                    {field: '领带', width: 134, title: '领带',search:defaultOp,search: false},
                    {field: '帽子', width: 134, title: '帽子',search:defaultOp,search: false},
                    {field: '内裤', width: 134, title: '内裤',search:defaultOp,search: false},
                    {field: '皮带', width: 134, title: '皮带',search:defaultOp,search: false},
                    {field: '袜子', title: '袜子', width: 134,search:defaultOp,search: false},
                    {field: '手包', title: '手包', width: 134,search:defaultOp,search: false},
                    {field: '胸包', title: '胸包', width: 134,search:defaultOp,search: false},
                ]],
                done:function (res, curr, count) {
                    var that = this.elem.next();
                    var config = res.data[0].config;
                    res.data.forEach(function (item,index) {
                        var tr = that.find("[data-index=" + index + "]").children();
                            tr.each(function (i,value) {
                                var key = $(value).data('field');
                                if(config[key]){
                                    if(parseInt(item[key]) < parseInt(config[key])){
                                        $(this).css("background-color", "rgba(255,0,0,.2)");//单元格背景颜色
                                    }
                                }
                                // $(this).css("background-color", "rgba(255,0,0,.2)");//单元格背景颜色
                                // $(this).css("color", "#45ff2a");//单元格字体颜色
                            })
                    })
                }
            });


            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});