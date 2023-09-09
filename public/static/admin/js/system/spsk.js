define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        // table_elem: '#currentTable',
        // table_render_id: 'currentTableRenderId',
        // index_url: 'system.weather/index',
        // city_url: 'system.weather/city',
        list_index: '/admin/system.spsk/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.spsk/getSpskSelect');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {

                var store_list = res.data.store;
                var jijie_list = res.data.jijie;
                var yijifenlei_list = res.data.yijifenlei;
                var erjifenlei_list = res.data.erjifenlei;
                var fenlei_list = res.data.fenlei;
                var fengge_list = res.data.fengge;
                var goods_manager_list = res.data.goods_manager;
                var province_list = res.data.province;
                var mathod_list = res.data.mathod;

                var cols = [
                    // {type: "checkbox",fixed:'left'},
                    {field: '云仓', width: 70, title: '云仓',fixed:'left',search: false},
                    {field: '店铺名称', width: 80, title: '店铺名称',fixed:'left',search: 'xmSelect',selectList:store_list,laySearch:true},
                    {field: '货号', width: 70, title: '货号',fixed:'left',search: true,laySearch:true},
                    {field: '预计00/28/37/44/100/160/S', width: 80, title: '预计00/28/37/44/100/160/S',search: false},
                    {field: '预计29/38/46/105/165/M', width: 80, title: '预计29/38/46/105/165/M',search: false},
                    {field: '预计30/39/48/110/170/L', width: 80, title: '预计30/39/48/110/170/L',search: false},
                    {field: '预计31/40/50/115/175/XL', width: 80, title: '预计31/40/50/115/175/XL',search: false},
                    {field: '预计32/41/52/120/180/2XL', width: 80, title: '预计32/41/52/120/180/2XL',search: false},
                    {field: '预计33/42/54/125/185/3XL', width: 80, title: '预计33/42/54/125/185/3XL',search: false},
                    {field: '预计34/43/56/190/4XL', width: 70, title: '预计34/43/56/190/4XL',search: false},
                    {field: '预计35/44/58/195/5XL', width: 70, title: '预计35/44/58/195/5XL',search: false},
                    {field: '预计36/6XL', width: 40, title: '预计36/6XL',search: false},
                    {field: '预计38/7XL', width: 40, title: '预计38/7XL',search: false},
                    {field: '预计_40', width: 40, title: '预计_40',search: false},
                    {field: '预计库存数量', width: 70, title: '预计库存数量',search: false},
                    {field: '商品负责人', width: 70, title: '商品负责人',search: 'xmSelect',selectList:goods_manager_list,laySearch:true},
                    {field: '省份', width: 50, title: '省份',search: 'xmSelect',selectList:province_list,laySearch:true},
                    {field: '经营模式', width: 40, title: '经营模式',search: 'xmSelect',selectList:mathod_list,laySearch:true},
                    {field: '货品名称', width: 70, title: '货品名称',search: false},
                    {field: '年份', width: 50, title: '年份',search: false},
                    {field: '季节', width: 40, title: '季节',search: 'xmSelect',selectList:jijie_list,laySearch:true},
                    {field: '一级分类', width: 65, title: '一级分类',search: 'select',selectList:yijifenlei_list,laySearch:true},
                    {field: '二级分类', width: 65, title: '二级分类',search: 'xmSelect',selectList:erjifenlei_list,laySearch:true},
                    {field: '分类', width: 50, title: '分类',search: 'xmSelect',selectList:fenlei_list,laySearch:true},
                    {field: '风格', width: 50, title: '风格',search: 'select',selectList:fengge_list,laySearch:true},
                ];

                ea.table.render({
                    url: init.list_index,
                    search:true,
                    height: 950,
                    limit: 1000,
                    toolbar:[],
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
                    }
                });

                ea.listen();
            },function (res) {
                alert('失败')
            })
        },
        city: function () {
            ea.listen();
        }
    };
    return Controller;
});