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
                this.show1()
                this.show2()
                this.show3()

            ea.listen();
        },

        show1:function (){
            $('body').on('click', '#form-sub', function () {

                let param = {};
                let form = $('#form').serializeArray();
                $.each(form, function (index, item) {
                    param[item.name] = item.value;
                });
                $.post('save?op=1', param, function (res) {

                    layer.msg(res.msg, {time: 2000, icon:1});
                })
            });
        },
        show2:function (){
            $('body').on('click', '#form-sub2', function () {

                let param = {};
                let form = $('#form2').serializeArray();
                $.each(form, function (index, item) {
                    param[item.name] = item.value;
                });
                $.post('save?op=2', param, function (res) {

                    layer.msg(res.msg, {time: 2000, icon:1});
                })
            });
        },
        show3:function (){

            $('.add_customer_level').on('click', function(){
                var element = $("#addTpl").html()
                $('#form-customer_level-config tbody').append(element);
            });

            $("body").on("click", ".del", function () {
                $(this).parent().parent().remove()
            });

            $('body').on('click', '#form-sub3', function () {


                var data_arr = [];
                $('input[name^="CustomerName"]').each(function(index,element) {
                    data_arr[index] = $(this).val();
                })


                let param = {};
                let form = $('#form3').serializeArray();
                // console.log('form',form)
                // $.each(form, function (index, item) {
                //     if(item.name.indexOf('[]') != -1){
                //         var key=item.name.replace('[]','')
                //         if(key  in param){
                //         }else {
                //             param[key]=[]
                //         }
                //         param[key].push(item.value)
                //     }else {
                //         param[item.name] = item.value;
                //     }
                // });
                // console.log('param',param)
                $.post('save?op=3', form, function (res) {

                    layer.msg(res.msg, {time: 2000, icon:1});
                })
            });


            ea.listen();
        },

    };
    return Controller;
});