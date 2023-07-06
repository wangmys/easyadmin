define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.skc.Kzconfig/saveConfig'),
        save_skc_config_url:ea.url('system.skc.Kzconfig/saveSkcConfig'),
        del_url:ea.url('/system.skc.Kzconfig/delConfig'),
        index_url:ea.url('/system.skc.Kzconfig/index')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.kz_num();
             this.kzskc_config();
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="id[]"]').val(res.data.id)
                ea.msg.success(res.msg);
             });
        },
        // 保存skc价格配置
        saveSkcConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="sign_id"]').val(res.data.sign_id)
                ea.msg.success(res.msg);
             });
        },
        // 删除配置
        delConfig:function (_this,_url,_id) {
            ea.request.get({
                url:_url,
                data:{id:_id}
            },function (res) {
                if(res.code == 1){
                    _this.parents('tr').remove();
                    layer.closeAll()
                }
                ea.msg.success(res.msg);
            })
        },

        //裤台陈列标准
        kz_num:function (){
            var kz_num_head = $("#kz_num_head").val();
            var kz_num_head = JSON.parse(kz_num_head);
            var html = '';
            var that = this;
            kz_num_head.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
           })
            $('.add_kz_num').on('click', function(){
               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id[]" class="layui-hide" value="">',
                           '<button type="button" class="layui-btn layui-btn-normal get" id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)
            
                element.find('.get').on('click', function(){
                    var _url = url.save_url;
                    var kt_num = element.find('input[name="kt_num[]"]').val();
                    var skc_cknz = element.find('input[name="skc_cknz[]"]').val();
                    var skc_ckxx = element.find('input[name="skc_ckxx[]"]').val();
                    var skc_cksj = element.find('input[name="skc_cksj[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        kt_num:kt_num,
                        skc_cknz:skc_cknz,
                        skc_ckxx:skc_ckxx,
                        skc_cksj:skc_cksj,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除该组合',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-kz_num-config tbody').append(element);
           });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.skc_kz_num-select'), function (key, element) {

                $(element).find('.get').on('click', function(){
                    var _url = url.save_url;
                    var kt_num = $(element).find('input[name="kt_num[]"]').val();
                    var skc_cknz = $(element).find('input[name="skc_cknz[]"]').val();
                    var skc_ckxx = $(element).find('input[name="skc_ckxx[]"]').val();
                    var skc_cksj = $(element).find('input[name="skc_cksj[]"]').val();
                    var id = $(element).find('input[name="id[]"]').val();

                    var _data = {
                        kt_num:kt_num,
                        skc_cknz:skc_cknz,
                        skc_ckxx:skc_ckxx,
                        skc_cksj:skc_cksj,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

               $(element).find('.del').on('click', function(){
                   var _this = $(this);
                   var _id = $(element).find('input[name="id[]"]').val();
                   layer.confirm('是否删除该组合',{},function () {
                        that.delConfig(_this, url.del_url, _id)
                   })
               })

           });
            
            ea.listen();
       },

        //SKC价格配置
        kzskc_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.kzskc_config-select'), function (key, element) {

                var gender = $(element).find('.skc_kz_nostore')[0];
                var data = JSON.parse($(element).attr('lay-data'));
                // console.log(data);
                var data = data.skc_kz_nostore;
                var genderSelect = xmSelect.render({
                    el: gender,
                    filterable: true,
                    data: function(){
                        return data
                    }
                })


                $(element).find('.get_kzskc_config').on('click', function(){
                    var _url = url.save_skc_config_url;
                    var dk_price = $(element).find('input[name="dk_price"]').val();
                    var ck_price = $(element).find('input[name="ck_price"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
                    var skc_kz_nostore = $(element).find('.skc_kz_nostore .label-content').attr('title');

                    var _data = {
                        dk_price:dk_price,
                        ck_price:ck_price,
                        sign_id:sign_id,
                        skc_kz_nostore:skc_kz_nostore
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveSkcConfig($(element), _url, _data);
                }.bind(genderSelect))

            });
                
            ea.listen();
        }


    };
    return Controller;
});