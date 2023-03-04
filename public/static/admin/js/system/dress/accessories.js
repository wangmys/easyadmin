define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.accessories/index',
        list_index: '/admin/system.dress.accessories/list',
        add_url: 'system.dress.accessories/add',
        edit_url: 'system.dress.accessories/edit',
        delete_url: 'system.dress.accessories/delete'
    };

    function isRed(d,obj){
        if(d[obj.field] === null){
            d[obj.field] = 0;
        }
        if(parseInt(d[obj.field]) < parseInt(d.config[obj.field])){
            //得到当前行数据，并拼接成自定义模板
            return '<span style="color: #c00;">'+ d[obj.field] +'</span>'
        }
        return '';
    }

    var Controller = {

        index: function () {

            function isRed(d,obj){
                if(d[obj.field] === null){
                    d[obj.field] = 0;
                }
                if(d.config){
                    if(parseInt(d[obj.field]) < parseInt(d.config[obj.field])){
                        //得到当前行数据，并拼接成自定义模板
                        return '<span style="color: #c00;">'+ d[obj.field] +'</span>'
                    }
                }
                return d[obj.field];
            }
            var table = ea.table;
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                init: init,
                event:false,
                // height: 750,
                // totalRow: true,
                cols: [[
                    {type: "checkbox"},
                    {field: '区域', width: 180, title: '区域',search: false},
                    {field: '店铺名称', width: 180, title: '店铺名称',search: false



                    },
                    {field: '背包', width: 180, title: '背包',templet: function(d){
                        return isRed(d,this);
                      },searchValue:this.width,search:defaultOp},
                    {field: '挎包', width: 180, title: '挎包',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '领带', width: 180, title: '领带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '帽子', width: 180, title: '帽子',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '内裤', width: 180, title: '内裤',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '皮带', width: 180, title: '皮带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '袜子', title: '袜子', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '手包', title: '手包', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '胸包', title: '胸包', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    // {field: '配饰汇总', title: '配饰汇总', width: 122},
                    // {field: '配饰SKC', title: '配饰SKC', width: 122}
                ]],
                done:function(res, curr, count) {
                    // let headerTop = $('.layui-table-header').offset().top; //获取表格头到文档顶部的距离
                    // $(window).scroll(function () {
                    //    if ((headerTop - $(window).scrollTop()) < 0) { //超过了
                    //        $('.layui-table-header').addClass('table-header-fixed'); //添加样式，固定住表头
                    //    } else { //没超过
                    //        $('.layui-table-header').removeClass('table-header-fixed'); //移除样式
                    //    }
                    // });
                    //
                    // //滚动body,header跟随滚动
                    // $('.layui-table-body').on('scroll', function(e) {
                    //     if ((headerTop - $(window).scrollTop()) < 0) { //超过了
                    //         var leftPx = $(e.target).scrollLeft(); //获取表格body，滚动条距离左边的长度
                    //         var left = 'translateX(-' + leftPx + 'px)';
                    //         $('.layui-table-header .layui-table').css('transform', left); //设置表格header的内容反向(-)移动
                    //      }
                    // });
                },
                parseData: function(res){ //res 即为原始返回的数据

                }
            });


            ea.listen();
        },
        list: function () {
            var table = ea.table;
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                url: init.list_index,
                search:false,
                cols: [[
                    {type: "checkbox"},
                    {field: '区域', width: 180, title: '区域',search: false},
                    {field: '店铺名称', width: 180, title: '店铺名称',search: false},
                    {field: '背包', width: 180, title: '背包',templet: function(d){
                        return isRed(d,this);
                      },searchValue:this.width,search:defaultOp},
                    {field: '挎包', width: 180, title: '挎包',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '领带', width: 180, title: '领带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '帽子', width: 180, title: '帽子',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '内裤', width: 180, title: '内裤',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '皮带', width: 180, title: '皮带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '袜子', title: '袜子', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '手包', title: '手包', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                    {field: '胸包', title: '胸包', width: 180,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp},
                ]]
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