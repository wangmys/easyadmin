define(["jquery", "easy-admin"], function ($, ea) {

    var table = layui.table,
        upload=layui.upload
        layer=layui.layer

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.puhuo.excelhandle/index',
        url_import: 'import_excel',
        url_export: 'export_excel',
    };

    var Controller = {

        index: function () {

            //excel转换
            //指定允许上传的文件类型
            upload.render({
                elem: '#upload_excel'
                ,url: init.url_import //此处配置你自己的上传接口即可
                ,accept: 'file' //普通文件
                ,before: function (obj) {
                    layer.load();
                }
                ,error: function() {
                    //上传成功结束加载效果
                        setTimeout(function () {
                            layui.layer.closeAll();
                        }, 2000);
                }
                ,done: function(res) {
                    layer.closeAll('loading');
                    console.log('res:....', res);
                    if (res.code != 0) {
                        layer.msg('上传失败！' + res.msg)
                    } else {
                        layer.msg('上传成功，请刷新页面', {time: 2000, icon:1});
                    }
                }
            });


            $('body').on('click', '#search', function (obj) {
                let where = {};
                let form = $('form').serializeArray();
                $.each(form, function (index, item) {
                    where[item.name] = item.value;
                });
                tableList.reload({
                    where: where,
                });
                return false;
            })

            $('body').on('click', '#export_excel', function (obj) {

                console.log('123')
                location.href = init.url_export;
                // return false;
            })


            $.get('xm_select', {}, function (data) {
                xmSelect.render({
                    el: '#xm-select',
                    icon: 'show',
                    tips: '请选择',
                    name: 'val',
                    toolbar: {
                        show: false,
                        list: ['ALL', 'CLEAR', 'REVERSE']
                    },
                    height: '320px',
                    direction: 'auto',
                    empty: '呀, 没有数据呢',
                    filterable: true,
                    theme: {
                        color: '#0081ff',
                    },
                    data: data.customer
                });

                layui.form.render("select");
            });

            
          let tableList=  ea.table.render({
                init: init,
                search: false,
                page: false,
                toolbar: ['refresh']
                , limit: 30000
                , limits: [200,500,1000, 5000, 10000, 20000]
                , height:780
                , cellMinWidth: 60 //全局定义常规单元格的最小宽度，layui 2.2.1 新增
                , cols: [
                    [
                        {type: "checkbox",fixed:'left'},
                        { field: 'TimeCategoryName2', title: '季节', align: 'center', fixed:'left', width: 35}
                        , { field: 'CategoryName1', title: '一级分类', align: 'center', fixed:'left', width: 55}
                        , { field: 'CategoryName2', title: '二级分类', align: 'center', width: 55}
                        , { field: 'CategoryName', title: '分类', align: 'center', width: 75}
                        , { field: 'Lingxing', title: '领型', align: 'center', width: 35}
                        , { field: 'ColorDesc', title: '颜色', align: 'center', width: 35}
                        , { field: 'UnitPrice', title: '零售价', align: 'center', width: 45}
                        , { field: 'GoodsNo', title: '货号', align: 'center', width: 70}
                        , { field: 'StyleCategoryName2', title: '货品等级', align: 'center', width: 55}
                        , { field: 'StyleCategoryName', title: '风格', align: 'center', width: 45}
                        //  ,{field: 'img', width: 80, title: '图片', search: false, templet: table.image,imageHeight:30,merge: true}
                        //  ,{field: 'img', width: 80, title: '图片', search: false, templet: '<div><img src="{}"/></div>',imageHeight:30,merge: true}
                        , { field: 'CustomItem17', title: '商品专员', align: 'center', width: 50}
                        , { field: 'State', title: '省份', align: 'center', width: 35}
                        , { field: 'CustomerName', title: '店铺名称', align: 'center', width: 70}
                        , { field: 'Mathod', title: '经营模式', align: 'center', width: 55}
                        , { field: 'CustomerGrade', title: '店铺等级', align: 'center', width: 55}
                        , { field: 'StoreArea', title: '店铺面积', align: 'center', width: 55}
                        , { field: 'xiuxian_num', title: '休闲裤台个数', align: 'center', width: 50}
                        //  , { field: 'StyleCategoryName1', title: '一级风格', align: 'center', width: 55}
                        , { field: 'score_sort', title: '店铺排名', align: 'center', width: 55}
                        , { field: 'Stock_00_puhuo', title: '28/44/37/S', align: 'center', width: 50}
                        , { field: 'Stock_29_puhuo', title: '29/46/38/M', align: 'center', width: 50}
                        , { field: 'Stock_30_puhuo', title: '30/48/39/L', align: 'center', width: 50}
                        , { field: 'Stock_31_puhuo', title: '31/50/40/XL', align: 'center', width: 50}
                        , { field: 'Stock_32_puhuo', title: '32/52/41/2XL', align: 'center', width: 50}
                        , { field: 'Stock_33_puhuo', title: '33/54/42/3XL', align: 'center', width: 50}
                        , { field: 'Stock_34_puhuo', title: '34/56/43/4XL', align: 'center', width: 50}
                        , { field: 'Stock_35_puhuo', title: '35/58/44/5XL', align: 'center', width: 50}
                        , { field: 'Stock_36_puhuo', title: '36/6XL', align: 'center', width: 35}
                        , { field: 'Stock_38_puhuo', title: '38/7XL', align: 'center', width: 35}
                        , { field: 'Stock_40_puhuo', title: '40/8XL', align: 'center', width: 35}
                        , { field: 'Stock_42_puhuo', title: '42', align: 'center', width: 30}
                        , { field: 'Stock_44_puhuo', title: '44', align: 'center', width: 30}
                        , { field: 'Stock_Quantity_puhuo', title: '合计', align: 'center', width: 40}
                    ],

                ]
                ,done: function (res, curr, count) {
                    $('#hid_count').val(res.count);

                    var data = res.data;
                    $.each(data, function(index, value){
                        if (value.is_total == 1) {

                            if (value.CustomerName == '余量') {
                                $('tr[data-index="'+index+'"]').css({
                                    'background-color': 'rgb(225, 225, 0)', 'color': '#000', 'font-weight':'bold'
                                });
                            } else {
                                $('tr[data-index="'+index+'"]').css({
                                    'background-color': 'rgb(110, 170, 46)', 'color': '#000', 'font-weight':'bold'
                                });
                            }

                        }
                    });

                    $('#nd_id').text(res.nd);
                    $('#wt_id').text(res.wt);
                    $('#xz_id').text(res.xz);
                    $('#xl_id').text(res.xl);
                    $('#store_num').text(res.store_num);


                }
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        }
    };
    return Controller;
});