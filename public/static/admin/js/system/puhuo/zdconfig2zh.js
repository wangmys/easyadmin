define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        savePuhuoZdySet_url:ea.url('system.puhuo.Zdconfig2zh/savePuhuoZdySet'),
        savePuhuoZdySetAll_url:ea.url('system.puhuo.Zdconfig2zh/savePuhuoZdySetAll'),
        delPuhuoZdySetAll_url:ea.url('system.puhuo.Zdconfig2zh/delPuhuoZdySetAll'),
        del_url:ea.url('/system.puhuo.Zdconfig2zh/delPuhuoZdySet')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
            this.guiyang_goods_config();
            this.wuhan_goods_config();
            this.guangzhou_goods_config();
            this.nanchang_goods_config();
            this.changsha_goods_config();

        },
        // 保存仓库预留参数配置
        savePuhuoZdySet:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(122, res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="Yuncang[]"]').val(res.data.Yuncang)
                element.find('input[name="id[]"]').val(res.data.id)
                element.find('button[id="save_button"]').addClass('layui-btn-disabled')
                ea.msg.success(res.msg);
             });
        },

        //一键保存仓库预留参数配置
        savePuhuoZdySetAll:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log('save all.....', res);
                var res_arr = res.data.res_arr;
                $.each(element, function (key, val) {
                    // console.log(key, '===>>>', res_arr[key], '=====>', val);
                    $(val).attr('lay-id', res_arr[key].id);
                    $(val).find('input[name="Yuncang[]"]').val(res_arr[key].Yuncang)
                    $(val).find('input[name="id[]"]').val(res_arr[key].id)
                    $(val).find('button[id="save_button"]').addClass('layui-btn-disabled')
                });
                ea.msg.success(res.msg);
             });
        },


        //一键删除
        delPuhuoZdySetAll:function (_this,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                if(res.code == 1){
                    _this.remove();
                    layer.closeAll()
                }
                ea.msg.success(res.msg);
            });
        },

        // 删除配置
        delConfig:function (_this,_url,_id) {
            ea.request.get({
                url:_url,
                data:{id:_id}
            },function (res) {
                //console.log(res);
                if(res.code == 1){
                    _this.parents('tr').remove();
                    layer.closeAll()
                }
                ea.msg.success(res.msg);
            })
        },

        bind:function (element) {
            var gender = element.find('.Commonfield')[0];
            var that = this;
            var data = JSON.parse(element.attr('lay-data'));
            var genderSelect = xmSelect.render({
                el: gender,
                toolbar: {
                    show: true,
                    list: ['ALL', 'CLEAR', 'REVERSE']
                },
                filterable: true,
                name: 'Commonfield[]',
                data: function(){
                    return data
                }
            })
        },

        //贵阳云仓配置
        guiyang_goods_config:function (){
            var that = this;
            var html = '';

            var rule_type_hidden = JSON.parse($('#rule_type_hidden').val());
            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var guiyang_select_list_hidden = JSON.parse($('#guiyang_select_list_hidden').val());
            var merge_list_hidden = guiyang_select_list_hidden['merge_list'];

            html += '<td class="guiyang_goods">';
            html += '<input type="text" style="width:500px;" name="GoodsNo[]" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B72109013 B62109211 B62105155" value="" class="layui-input">';
            html += '</td>';

            html += '<td class="Commonfield"></td>';

            html += '<td class="rule_type">';
            html += '<select name="rule_type[]">';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';

            html += '<td class="remain_store">';
            html += '<select name="remain_store[]">';
            html += '<option value="2">不铺</option>';
            html += '<option value="1">铺</option>';
            html += '</select>';
            html += '</td>';


            html += '<td class="remain_rule_type">';
            html += '<select name="remain_rule_type[]">';
            html += '<option value="0">请选择</option>';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';


            html += '<td class="if_taozhuang">';
            html += '<select name="if_taozhuang[]">';
            html += '<option value="2">否</option>';
            html += '<option value="1">是</option>';
            html += '</select>';
            html += '</td>';

            html += '<td class="if_zdmd">';
            html += '<select name="if_zdmd[]">';
            html += '<option value="1">是</option>';
            html += '<option value="2">否</option>';
            html += '</select>';
            html += '</td>';

            //点击添加 操作
            $('.add_guiyang_goods_config').on('click', function(){

            var element = $([
                '<tr class="guiyang_goods_config-select">',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id[]" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang[]" class="layui-hide" value="贵阳云仓">',
                        '<input type="text" name="Selecttype[]" class="layui-hide" value="1">',
                        '<button type="button" class="layui-btn layui-btn-normal get_guiyang_goods_config" id="save_button">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_guiyang_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                var gender = element.find('.Commonfield')[0];
                var genderSelect = xmSelect.render({
                    el: gender,
                    filterable: true,
                    toolbar: {
                        show: true,
                        list: ['ALL', 'CLEAR', 'REVERSE']
                    },
                    name: 'Commonfield[]',
                    data: function(){
                        return merge_list_hidden
                    }
                })

                element.find('.get_guiyang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang[]"]').val();
                    var Selecttype = element.find('input[name="Selecttype[]"]').val();
                    var Commonfield = element.find('input[name="Commonfield[]"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo[]"]').val();
                    var rule_type = element.find('select[name="rule_type[]"]').val();
                    var remain_store = element.find('select[name="remain_store[]"]').val();
                    var remain_rule_type = element.find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = element.find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = element.find('select[name="if_zdmd[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_guiyang_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                    $(_this).parents('tr').remove();
                    layer.closeAll()
                })
            })
            $('#form-guiyang_goods-config tbody').append(element);

            ea.listen();
            });

            //一键保存全部
            $('.add_guiyang_goods_config_all').on('click', function(){
                var _url = url.savePuhuoZdySetAll_url;
                var all_data = $('form[id="app-form_guiyang_goods_config"]').serializeArray();
                that.savePuhuoZdySetAll($('.guiyang_goods_config-select'), _url, all_data);
            });

            //一键删除全部
            $('.del_guiyang_goods_config_all').on('click', function () {
                var _this = $(this);
                var _url = url.delPuhuoZdySetAll_url;
                layer.confirm('是否删除全部',{},function () {
                    that.delPuhuoZdySetAll($('.guiyang_goods_config-select'), _url, {Yuncang: '贵阳云仓', Selecttype: 1});
                    layer.closeAll()
                })

            });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guiyang_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_guiyang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id[]"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang[]"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype[]"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield[]"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo[]"]').val();
                    var rule_type = $(element).find('select[name="rule_type[]"]').val();
                    var remain_store = $(element).find('select[name="remain_store[]"]').val();
                    var remain_rule_type = $(element).find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = $(element).find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = $(element).find('select[name="if_zdmd[]"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_guiyang_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })

                //值 多选下拉绑定
                if (JSON.parse($(element).attr('lay-data')).length != 0) {
                    that.bind($(element));
                }

            });
                
            ea.listen();
        },

        //武汉云仓配置
        wuhan_goods_config:function (){
            var that = this;
            var html = '';

            var rule_type_hidden = JSON.parse($('#rule_type_hidden').val());
            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var wuhan_select_list_hidden = JSON.parse($('#wuhan_select_list_hidden').val());
            var merge_list_hidden = wuhan_select_list_hidden['merge_list'];

            // html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            // html += '<span class="span_Selecttype"></span>';
            // html += '</td>';
            
            html += '<td class="wuhan_goods">';
            html += '<input type="text" style="width:500px;" name="GoodsNo[]" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52502014 B52109004 B52106003" value="" class="layui-input">';
            html += '</td>';

            html += '<td class="Commonfield"></td>';

            html += '<td class="rule_type">';
            html += '<select name="rule_type[]">';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';

            html += '<td class="remain_store">';
            html += '<select name="remain_store[]">';
            html += '<option value="2">不铺</option>';
            html += '<option value="1">铺</option>';
            html += '</select>';
            html += '</td>';


            html += '<td class="remain_rule_type">';
            html += '<select name="remain_rule_type[]">';
            html += '<option value="0">请选择</option>';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';


            html += '<td class="if_taozhuang">';
            html += '<select name="if_taozhuang[]">';
            html += '<option value="2">否</option>';
            html += '<option value="1">是</option>';
            html += '</select>';
            html += '</td>';

            html += '<td class="if_zdmd">';
            html += '<select name="if_zdmd[]">';
            html += '<option value="1">是</option>';
            html += '<option value="2">否</option>';
            html += '</select>';
            html += '</td>';

            //点击添加 操作
            $('.add_wuhan_goods_config').on('click', function(){

                // var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();
                // var span_Selecttype = '';
                // var each_list_hidden = [];
                // if (Selecttype1 == 1) {//多店
                //     span_Selecttype = '多店';
                //     each_list_hidden = customer_list_hidden;
                // } else if (Selecttype1 == 2) {//多省
                //     span_Selecttype = '多省';
                //     each_list_hidden = province_list_hidden;
                // } else if (Selecttype1 == 3) {//商品专员
                //     span_Selecttype = '商品专员';
                //     each_list_hidden = goods_manager_list_hidden;
                // } else if (Selecttype1 == 4) {//经营模式
                //     span_Selecttype = '经营模式';
                //     each_list_hidden = mathod_list_hidden;
                // }

               var element = $([
                   '<tr class="wuhan_goods_config-select">',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id[]" class="layui-hide" value="">',
                           '<input type="text" name="Yuncang[]" class="layui-hide" value="武汉云仓">',
                           '<input type="text" name="Selecttype[]" class="layui-hide" value="1">',
                           '<button type="button" class="layui-btn layui-btn-normal get_wuhan_goods_config" id="save_button">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del_wuhan_goods_config">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)

                // element.find('.span_Selecttype').html(span_Selecttype);

                // //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                // if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        toolbar: {
                            show: true,
                            list: ['ALL', 'CLEAR', 'REVERSE']
                        },
                        filterable: true,
                        name: 'Commonfield[]',
                        data: function(){
                            return merge_list_hidden
                        }
                    })

                    // element.find('input[name="Selecttype"]').val(Selecttype1);
                // }

            
                element.find('.get_wuhan_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang[]"]').val();
                    var Selecttype = element.find('input[name="Selecttype[]"]').val();
                    var Commonfield = element.find('input[name="Commonfield[]"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo[]"]').val();
                    var rule_type = element.find('select[name="rule_type[]"]').val();
                    var remain_store = element.find('select[name="remain_store[]"]').val();
                    var remain_rule_type = element.find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = element.find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = element.find('select[name="if_zdmd[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_wuhan_goods_config').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-wuhan_goods-config tbody').append(element);

               ea.listen();
           });

            //一键保存全部
            $('.add_wuhan_goods_config_all').on('click', function(){
                var _url = url.savePuhuoZdySetAll_url;
                var all_data = $('form[id="app-form_wuhan_goods_config"]').serializeArray();
                // console.log(_url,    all_data);
                that.savePuhuoZdySetAll($('.wuhan_goods_config-select'), _url, all_data);
            });


            //一键删除全部
            $('.del_wuhan_goods_config_all').on('click', function () {
                var _this = $(this);
                var _url = url.delPuhuoZdySetAll_url;
                layer.confirm('是否删除全部',{},function () {
                    that.delPuhuoZdySetAll($('.wuhan_goods_config-select'), _url, {Yuncang: '武汉云仓', Selecttype: 1});
                    layer.closeAll()
                })

            });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.wuhan_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_wuhan_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id[]"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang[]"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype[]"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield[]"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo[]"]').val();
                    var rule_type = $(element).find('select[name="rule_type[]"]').val();
                    var remain_store = $(element).find('select[name="remain_store[]"]').val();
                    var remain_rule_type = $(element).find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = $(element).find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = $(element).find('select[name="if_zdmd[]"]').val();
    
                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_wuhan_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                         var id = $(element).find('input[name="id[]"]').val();
                         if (id != '') {
                             that.delConfig(_this, url.del_url, id)
                         }
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })

                //值 多选下拉绑定
                if (JSON.parse($(element).attr('lay-data')).length != 0) {
                    that.bind($(element));
                }

            });
                
            ea.listen();
        },

        //广州云仓配置
        guangzhou_goods_config:function (){
            var that = this;
            var html = '';

            var rule_type_hidden = JSON.parse($('#rule_type_hidden').val());
            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var guangzhou_select_list_hidden = JSON.parse($('#guangzhou_select_list_hidden').val());
            var merge_list_hidden = guangzhou_select_list_hidden['merge_list'];

            html += '<td class="guangzhou_goods">';
            html += '<input type="text" style="width:500px;" name="GoodsNo[]" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B62612205 B62501005 B52109011" value="" class="layui-input">';
            html += '</td>';

            html += '<td class="Commonfield"></td>';

            html += '<td class="rule_type">';
            html += '<select name="rule_type[]">';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';

            html += '<td class="remain_store">';
            html += '<select name="remain_store[]">';
            html += '<option value="2">不铺</option>';
            html += '<option value="1">铺</option>';
            html += '</select>';
            html += '</td>';


            html += '<td class="remain_rule_type">';
            html += '<select name="remain_rule_type[]">';
            html += '<option value="0">请选择</option>';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';


            html += '<td class="if_taozhuang">';
            html += '<select name="if_taozhuang[]">';
            html += '<option value="2">否</option>';
            html += '<option value="1">是</option>';
            html += '</select>';
            html += '</td>';

            html += '<td class="if_zdmd">';
            html += '<select name="if_zdmd[]">';
            html += '<option value="1">是</option>';
            html += '<option value="2">否</option>';
            html += '</select>';
            html += '</td>';

            //点击添加 操作
            $('.add_guangzhou_goods_config').on('click', function(){

            var element = $([
                '<tr class="guangzhou_goods_config-select">',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id[]" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang[]" class="layui-hide" value="广州云仓">',
                        '<input type="text" name="Selecttype[]" class="layui-hide" value="1">',
                        '<button type="button" class="layui-btn layui-btn-normal get_guangzhou_goods_config" id="save_button">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_guangzhou_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                var gender = element.find('.Commonfield')[0];
                var genderSelect = xmSelect.render({
                    el: gender,
                    toolbar: {
                        show: true,
                        list: ['ALL', 'CLEAR', 'REVERSE']
                    },
                    filterable: true,
                    name: 'Commonfield[]',
                    data: function(){
                        return merge_list_hidden
                    }
                })

                element.find('.get_guangzhou_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang[]"]').val();
                    var Selecttype = element.find('input[name="Selecttype[]"]').val();
                    var Commonfield = element.find('input[name="Commonfield[]"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo[]"]').val();
                    var rule_type = element.find('select[name="rule_type[]"]').val();
                    var remain_store = element.find('select[name="remain_store[]"]').val();
                    var remain_rule_type = element.find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = element.find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = element.find('select[name="if_zdmd[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_guangzhou_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                    $(_this).parents('tr').remove();
                    layer.closeAll()
                })
            })
            $('#form-guangzhou_goods-config tbody').append(element);

            ea.listen();
            });

            //一键保存全部
            $('.add_guangzhou_goods_config_all').on('click', function(){
                var _url = url.savePuhuoZdySetAll_url;
                var all_data = $('form[id="app-form_guangzhou_goods_config"]').serializeArray();
                that.savePuhuoZdySetAll($('.guangzhou_goods_config-select'), _url, all_data);
            });

            //一键删除全部
            $('.del_guangzhou_goods_config_all').on('click', function () {
                var _this = $(this);
                var _url = url.delPuhuoZdySetAll_url;
                layer.confirm('是否删除全部',{},function () {
                    that.delPuhuoZdySetAll($('.guangzhou_goods_config-select'), _url, {Yuncang: '广州云仓', Selecttype: 1});
                    layer.closeAll()
                })

            });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guangzhou_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_guangzhou_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id[]"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang[]"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype[]"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield[]"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo[]"]').val();
                    var rule_type = $(element).find('select[name="rule_type[]"]').val();
                    var remain_store = $(element).find('select[name="remain_store[]"]').val();
                    var remain_rule_type = $(element).find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = $(element).find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = $(element).find('select[name="if_zdmd[]"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_guangzhou_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })

                //值 多选下拉绑定
                if (JSON.parse($(element).attr('lay-data')).length != 0) {
                    that.bind($(element));
                }

            });
                
            ea.listen();
        },

        //南昌云仓配置
        nanchang_goods_config:function (){
            var that = this;
            var html = '';

            var rule_type_hidden = JSON.parse($('#rule_type_hidden').val());
            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var nanchang_select_list_hidden = JSON.parse($('#nanchang_select_list_hidden').val());
            var merge_list_hidden = nanchang_select_list_hidden['merge_list'];

            html += '<td class="nanchang_goods">';
            html += '<input type="text" style="width:500px;" name="GoodsNo[]" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52109006 B52106011 B51501023" value="" class="layui-input">';
            html += '</td>';

            html += '<td class="Commonfield"></td>';

            html += '<td class="rule_type">';
            html += '<select name="rule_type[]">';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';

            html += '<td class="remain_store">';
            html += '<select name="remain_store[]">';
            html += '<option value="2">不铺</option>';
            html += '<option value="1">铺</option>';
            html += '</select>';
            html += '</td>';


            html += '<td class="remain_rule_type">';
            html += '<select name="remain_rule_type[]">';
            html += '<option value="0">请选择</option>';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';


            html += '<td class="if_taozhuang">';
            html += '<select name="if_taozhuang[]">';
            html += '<option value="2">否</option>';
            html += '<option value="1">是</option>';
            html += '</select>';
            html += '</td>';

            html += '<td class="if_zdmd">';
            html += '<select name="if_zdmd[]">';
            html += '<option value="1">是</option>';
            html += '<option value="2">否</option>';
            html += '</select>';
            html += '</td>';

            //点击添加 操作
            $('.add_nanchang_goods_config').on('click', function(){

            var element = $([
                '<tr class="nanchang_goods_config-select">',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id[]" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang[]" class="layui-hide" value="南昌云仓">',
                        '<input type="text" name="Selecttype[]" class="layui-hide" value="1">',
                        '<button type="button" class="layui-btn layui-btn-normal get_nanchang_goods_config" id="save_button">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_nanchang_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                var gender = element.find('.Commonfield')[0];
                var genderSelect = xmSelect.render({
                    el: gender,
                    toolbar: {
                        show: true,
                        list: ['ALL', 'CLEAR', 'REVERSE']
                    },
                    filterable: true,
                    name: 'Commonfield[]',
                    data: function(){
                        return merge_list_hidden
                    }
                })

                element.find('.get_nanchang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang[]"]').val();
                    var Selecttype = element.find('input[name="Selecttype[]"]').val();
                    var Commonfield = element.find('input[name="Commonfield[]"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo[]"]').val();
                    var rule_type = element.find('select[name="rule_type[]"]').val();
                    var remain_store = element.find('select[name="remain_store[]"]').val();
                    var remain_rule_type = element.find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = element.find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = element.find('select[name="if_zdmd[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_nanchang_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                    $(_this).parents('tr').remove();
                    layer.closeAll()
                })
            })
            $('#form-nanchang_goods-config tbody').append(element);

            ea.listen();
            });

            //一键保存全部
            $('.add_nanchang_goods_config_all').on('click', function(){
                var _url = url.savePuhuoZdySetAll_url;
                var all_data = $('form[id="app-form_nanchang_goods_config"]').serializeArray();
                that.savePuhuoZdySetAll($('.nanchang_goods_config-select'), _url, all_data);
            });

            //一键删除全部
            $('.del_nanchang_goods_config_all').on('click', function () {
                var _this = $(this);
                var _url = url.delPuhuoZdySetAll_url;
                layer.confirm('是否删除全部',{},function () {
                    that.delPuhuoZdySetAll($('.nanchang_goods_config-select'), _url, {Yuncang: '南昌云仓', Selecttype: 1});
                    layer.closeAll()
                })

            });
            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.nanchang_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_nanchang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id[]"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang[]"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype[]"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield[]"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo[]"]').val();
                    var rule_type = $(element).find('select[name="rule_type[]"]').val();
                    var remain_store = $(element).find('select[name="remain_store[]"]').val();
                    var remain_rule_type = $(element).find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = $(element).find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = $(element).find('select[name="if_zdmd[]"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_nanchang_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })

                //值 多选下拉绑定
                if (JSON.parse($(element).attr('lay-data')).length != 0) {
                    that.bind($(element));
                }

            });
                
            ea.listen();
        },

        //长沙云仓配置
        changsha_goods_config:function (){
            var that = this;
            var html = '';

            var rule_type_hidden = JSON.parse($('#rule_type_hidden').val());
            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var changsha_select_list_hidden = JSON.parse($('#changsha_select_list_hidden').val());
            var merge_list_hidden = changsha_select_list_hidden['merge_list'];

            html += '<td class="changsha_goods">';
            html += '<input type="text" style="width:500px;" name="GoodsNo[]" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52612002 B52503005 B52110135" value="" class="layui-input">';
            html += '</td>';

            html += '<td class="Commonfield"></td>';

            html += '<td class="rule_type">';
            html += '<select name="rule_type[]">';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';

            html += '<td class="remain_store">';
            html += '<select name="remain_store[]">';
            html += '<option value="2">不铺</option>';
            html += '<option value="1">铺</option>';
            html += '</select>';
            html += '</td>';


            html += '<td class="remain_rule_type">';
            html += '<select name="remain_rule_type[]">';
            html += '<option value="0">请选择</option>';
            $.each(rule_type_hidden, function (key, value) {
                html += '<option value="'+key+'">'+value+'</option>';
            });	
            html += '</select>';
            html += '</td>';


            html += '<td class="if_taozhuang">';
            html += '<select name="if_taozhuang[]">';
            html += '<option value="2">否</option>';
            html += '<option value="1">是</option>';
            html += '</select>';
            html += '</td>';

            html += '<td class="if_zdmd">';
            html += '<select name="if_zdmd[]">';
            html += '<option value="1">是</option>';
            html += '<option value="2">否</option>';
            html += '</select>';
            html += '</td>';

            //点击添加 操作
            $('.add_changsha_goods_config').on('click', function(){

            var element = $([
                '<tr class="changsha_goods_config-select">',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id[]" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang[]" class="layui-hide" value="长沙云仓">',
                        '<input type="text" name="Selecttype[]" class="layui-hide" value="1">',
                        '<button type="button" class="layui-btn layui-btn-normal get_changsha_goods_config" id="save_button">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_changsha_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                var gender = element.find('.Commonfield')[0];
                var genderSelect = xmSelect.render({
                    el: gender,
                    toolbar: {
                        show: true,
                        list: ['ALL', 'CLEAR', 'REVERSE']
                    },
                    filterable: true,
                    name: 'Commonfield[]',
                    data: function(){
                        return merge_list_hidden
                    }
                })

                element.find('.get_changsha_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang[]"]').val();
                    var Selecttype = element.find('input[name="Selecttype[]"]').val();
                    var Commonfield = element.find('input[name="Commonfield[]"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo[]"]').val();
                    var rule_type = element.find('select[name="rule_type[]"]').val();
                    var remain_store = element.find('select[name="remain_store[]"]').val();
                    var remain_rule_type = element.find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = element.find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = element.find('select[name="if_zdmd[]"]').val();
                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_changsha_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                    $(_this).parents('tr').remove();
                    layer.closeAll()
                })
            })
            $('#form-changsha_goods-config tbody').append(element);

            ea.listen();
            });

            //一键保存全部
            $('.add_changsha_goods_config_all').on('click', function(){
                var _url = url.savePuhuoZdySetAll_url;
                var all_data = $('form[id="app-form_changsha_goods_config"]').serializeArray();
                that.savePuhuoZdySetAll($('.changsha_goods_config-select'), _url, all_data);
            });

            //一键删除全部
            $('.del_changsha_goods_config_all').on('click', function () {
                var _this = $(this);
                var _url = url.delPuhuoZdySetAll_url;
                layer.confirm('是否删除全部',{},function () {
                    that.delPuhuoZdySetAll($('.changsha_goods_config-select'), _url, {Yuncang: '长沙云仓', Selecttype: 1});
                    layer.closeAll()
                })

            });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.changsha_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_changsha_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id[]"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang[]"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype[]"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield[]"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo[]"]').val();
                    var rule_type = $(element).find('select[name="rule_type[]"]').val();
                    var remain_store = $(element).find('select[name="remain_store[]"]').val();
                    var remain_rule_type = $(element).find('select[name="remain_rule_type[]"]').val();
                    var if_taozhuang = $(element).find('select[name="if_taozhuang[]"]').val();
                    var if_zdmd = $(element).find('select[name="if_zdmd[]"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        rule_type:rule_type,
                        remain_store:remain_store,
                        remain_rule_type:remain_rule_type,
                        if_taozhuang:if_taozhuang,
                        if_zdmd:if_zdmd,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_changsha_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })

                //值 多选下拉绑定
                if (JSON.parse($(element).attr('lay-data')).length != 0) {
                    that.bind($(element));
                }

            });
                
            ea.listen();
        }


    };
    return Controller;
});