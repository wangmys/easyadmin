define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        savePuhuoZdySet_url:ea.url('system.puhuo.Zdconfig/savePuhuoZdySet'),
        del_url:ea.url('/system.puhuo.Zdconfig/delPuhuoZdySet')
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
                //console.log(122, res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="Yuncang"]').val(res.data.Yuncang)
                element.find('input[name="id"]').val(res.data.id)
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
                filterable: true,
                name: 'Commonfield',
                data: function(){
                    return data
                }
            })
        },

        //贵阳云仓配置
        guiyang_goods_config:function (){
            var that = this;
            var html = '';

            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var guiyang_select_list_hidden = JSON.parse($('#guiyang_select_list_hidden').val());
            var customer_list_hidden = guiyang_select_list_hidden['customer_list'];
            var province_list_hidden = guiyang_select_list_hidden['province_list'];
            var goods_manager_list_hidden = guiyang_select_list_hidden['goods_manager_list'];
            var mathod_list_hidden = guiyang_select_list_hidden['mathod_list'];

            html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            html += '<span class="span_Selecttype"></span>';
            html += '</td>';
            html += '<td class="Commonfield"></td>';
            html += '<td class="guiyang_goods">';
            html += '<input type="text" style="width:900px;" name="GoodsNo" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B72109013 B62109211 B62105155" value="" class="layui-input">';
            html += '</td>';

            //点击添加 操作
            $('.add_guiyang_goods_config').on('click', function(){

                var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();//$('#xm-Selecttype1').val();
                var span_Selecttype = '';
                var each_list_hidden = [];
                if (Selecttype1 == 1) {//多店
                    span_Selecttype = '多店';
                    each_list_hidden = customer_list_hidden;
                } else if (Selecttype1 == 2) {//多省
                    span_Selecttype = '多省';
                    each_list_hidden = province_list_hidden;
                } else if (Selecttype1 == 3) {//商品专员
                    span_Selecttype = '商品专员';
                    each_list_hidden = goods_manager_list_hidden;
                } else if (Selecttype1 == 4) {//经营模式
                    span_Selecttype = '经营模式';
                    each_list_hidden = mathod_list_hidden;
                }

            var element = $([
                '<tr>',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang" class="layui-hide" value="贵阳云仓">',
                        '<input type="text" name="Selecttype" class="layui-hide" value="0">',
                        '<button type="button" class="layui-btn layui-btn-normal get_guiyang_goods_config" id="">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_guiyang_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                element.find('.span_Selecttype').html(span_Selecttype);

                //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        filterable: true,
                        name: 'Commonfield',
                        data: function(){
                            return each_list_hidden
                        }
                    })

                    element.find('input[name="Selecttype"]').val(Selecttype1);
                }


                element.find('.get_guiyang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang"]').val();
                    var Selecttype = element.find('input[name="Selecttype"]').val();
                    var Commonfield = element.find('input[name="Commonfield"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo"]').val();
                    var id = element.find('input[name="id"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_guiyang_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id"]').val();
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

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guiyang_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_guiyang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_guiyang_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id"]').val();
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

            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var wuhan_select_list_hidden = JSON.parse($('#wuhan_select_list_hidden').val());
            var customer_list_hidden = wuhan_select_list_hidden['customer_list'];
            var province_list_hidden = wuhan_select_list_hidden['province_list'];
            var goods_manager_list_hidden = wuhan_select_list_hidden['goods_manager_list'];
            var mathod_list_hidden = wuhan_select_list_hidden['mathod_list'];

            html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            html += '<span class="span_Selecttype"></span>';
            html += '</td>';
            html += '<td class="Commonfield"></td>';
            html += '<td class="wuhan_goods">';
            html += '<input type="text" style="width:900px;" name="GoodsNo" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52502014 B52109004 B52106003" value="" class="layui-input">';
            html += '</td>';

            //点击添加 操作
            $('.add_wuhan_goods_config').on('click', function(){

                var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();
                var span_Selecttype = '';
                var each_list_hidden = [];
                if (Selecttype1 == 1) {//多店
                    span_Selecttype = '多店';
                    each_list_hidden = customer_list_hidden;
                } else if (Selecttype1 == 2) {//多省
                    span_Selecttype = '多省';
                    each_list_hidden = province_list_hidden;
                } else if (Selecttype1 == 3) {//商品专员
                    span_Selecttype = '商品专员';
                    each_list_hidden = goods_manager_list_hidden;
                } else if (Selecttype1 == 4) {//经营模式
                    span_Selecttype = '经营模式';
                    each_list_hidden = mathod_list_hidden;
                }

               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id" class="layui-hide" value="">',
                           '<input type="text" name="Yuncang" class="layui-hide" value="武汉云仓">',
                           '<input type="text" name="Selecttype" class="layui-hide" value="0">',
                           '<button type="button" class="layui-btn layui-btn-normal get_wuhan_goods_config" id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del_wuhan_goods_config">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)

                element.find('.span_Selecttype').html(span_Selecttype);

                //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        filterable: true,
                        name: 'Commonfield',
                        data: function(){
                            return each_list_hidden
                        }
                    })

                    element.find('input[name="Selecttype"]').val(Selecttype1);
                }

            
                element.find('.get_wuhan_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang"]').val();
                    var Selecttype = element.find('input[name="Selecttype"]').val();
                    var Commonfield = element.find('input[name="Commonfield"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo"]').val();
                    var id = element.find('input[name="id"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_wuhan_goods_config').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id"]').val();
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

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.wuhan_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_wuhan_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
    
                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_wuhan_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                         var id = $(element).find('input[name="id"]').val();
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

            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var guangzhou_select_list_hidden = JSON.parse($('#guangzhou_select_list_hidden').val());
            var customer_list_hidden = guangzhou_select_list_hidden['customer_list'];
            var province_list_hidden = guangzhou_select_list_hidden['province_list'];
            var goods_manager_list_hidden = guangzhou_select_list_hidden['goods_manager_list'];
            var mathod_list_hidden = guangzhou_select_list_hidden['mathod_list'];

            html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            html += '<span class="span_Selecttype"></span>';
            html += '</td>';
            html += '<td class="Commonfield"></td>';
            html += '<td class="guangzhou_goods">';
            html += '<input type="text" style="width:900px;" name="GoodsNo" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B62612205 B62501005 B52109011" value="" class="layui-input">';
            html += '</td>';

            //点击添加 操作
            $('.add_guangzhou_goods_config').on('click', function(){

                var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();
                var span_Selecttype = '';
                var each_list_hidden = [];
                if (Selecttype1 == 1) {//多店
                    span_Selecttype = '多店';
                    each_list_hidden = customer_list_hidden;
                } else if (Selecttype1 == 2) {//多省
                    span_Selecttype = '多省';
                    each_list_hidden = province_list_hidden;
                } else if (Selecttype1 == 3) {//商品专员
                    span_Selecttype = '商品专员';
                    each_list_hidden = goods_manager_list_hidden;
                } else if (Selecttype1 == 4) {//经营模式
                    span_Selecttype = '经营模式';
                    each_list_hidden = mathod_list_hidden;
                }

            var element = $([
                '<tr>',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang" class="layui-hide" value="广州云仓">',
                        '<input type="text" name="Selecttype" class="layui-hide" value="0">',
                        '<button type="button" class="layui-btn layui-btn-normal get_guangzhou_goods_config" id="">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_guangzhou_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                element.find('.span_Selecttype').html(span_Selecttype);

                //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        filterable: true,
                        name: 'Commonfield',
                        data: function(){
                            return each_list_hidden
                        }
                    })

                    element.find('input[name="Selecttype"]').val(Selecttype1);
                }


                element.find('.get_guangzhou_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang"]').val();
                    var Selecttype = element.find('input[name="Selecttype"]').val();
                    var Commonfield = element.find('input[name="Commonfield"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo"]').val();
                    var id = element.find('input[name="id"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_guangzhou_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id"]').val();
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

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guangzhou_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_guangzhou_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_guangzhou_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id"]').val();
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

            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            var nanchang_select_list_hidden = JSON.parse($('#nanchang_select_list_hidden').val());
            var customer_list_hidden = nanchang_select_list_hidden['customer_list'];
            var province_list_hidden = nanchang_select_list_hidden['province_list'];
            var goods_manager_list_hidden = nanchang_select_list_hidden['goods_manager_list'];
            var mathod_list_hidden = nanchang_select_list_hidden['mathod_list'];

            html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            html += '<span class="span_Selecttype"></span>';
            html += '</td>';
            html += '<td class="Commonfield"></td>';
            html += '<td class="nanchang_goods">';
            html += '<input type="text" style="width:900px;" name="GoodsNo" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52109006 B52106011 B51501023" value="" class="layui-input">';
            html += '</td>';

            //点击添加 操作
            $('.add_nanchang_goods_config').on('click', function(){

                var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();
                var span_Selecttype = '';
                var each_list_hidden = [];
                if (Selecttype1 == 1) {//多店
                    span_Selecttype = '多店';
                    each_list_hidden = customer_list_hidden;
                } else if (Selecttype1 == 2) {//多省
                    span_Selecttype = '多省';
                    each_list_hidden = province_list_hidden;
                } else if (Selecttype1 == 3) {//商品专员
                    span_Selecttype = '商品专员';
                    each_list_hidden = goods_manager_list_hidden;
                } else if (Selecttype1 == 4) {//经营模式
                    span_Selecttype = '经营模式';
                    each_list_hidden = mathod_list_hidden;
                }

            var element = $([
                '<tr>',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang" class="layui-hide" value="南昌云仓">',
                        '<input type="text" name="Selecttype" class="layui-hide" value="0">',
                        '<button type="button" class="layui-btn layui-btn-normal get_nanchang_goods_config" id="">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_nanchang_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                element.find('.span_Selecttype').html(span_Selecttype);

                //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        filterable: true,
                        name: 'Commonfield',
                        data: function(){
                            return each_list_hidden
                        }
                    })

                    element.find('input[name="Selecttype"]').val(Selecttype1);
                }


                element.find('.get_nanchang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang"]').val();
                    var Selecttype = element.find('input[name="Selecttype"]').val();
                    var Commonfield = element.find('input[name="Commonfield"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo"]').val();
                    var id = element.find('input[name="id"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_nanchang_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id"]').val();
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

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.nanchang_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_nanchang_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_nanchang_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id"]').val();
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

            var Selecttype_hidden = JSON.parse($('#Selecttype_hidden').val());
            // var customer_list_hidden = JSON.parse($('#customer_list_hidden').val());
            // var province_list_hidden = JSON.parse($('#province_list_hidden').val());
            // var goods_manager_list_hidden = JSON.parse($('#goods_manager_list_hidden').val());
            // var mathod_list_hidden = JSON.parse($('#mathod_list_hidden').val());
            var changsha_select_list_hidden = JSON.parse($('#changsha_select_list_hidden').val());
            var customer_list_hidden = changsha_select_list_hidden['customer_list'];
            var province_list_hidden = changsha_select_list_hidden['province_list'];
            var goods_manager_list_hidden = changsha_select_list_hidden['goods_manager_list'];
            var mathod_list_hidden = changsha_select_list_hidden['mathod_list'];


            html += '<td class="Selecttype">';
            // html += '<select id="xm-Selecttype" name="Selecttype" lay-verify="" lay-filter="Selecttype">';
            // html += '<option value="">请选择</option>';
            //         Selecttype_hidden.forEach(function (i,value) {
            //             html += '<option value="'+i.value+'">'+i.name+'</option>';
            //         })
            // html += '</select>';
            html += '<span class="span_Selecttype"></span>';
            html += '</td>';
            html += '<td class="Commonfield"></td>';
            html += '<td class="changsha_goods">';
            html += '<input type="text" style="width:900px;" name="GoodsNo" lay-verify="required" placeholder="请输入,多个货号用空格 隔开，如：B52612002 B52503005 B52110135" value="" class="layui-input">';
            html += '</td>';

            //点击添加 操作
            $('.add_changsha_goods_config').on('click', function(){

                var Selecttype1 = $(this).parents('tr').find('#xm-Selecttype1').val();
                var span_Selecttype = '';
                var each_list_hidden = [];
                if (Selecttype1 == 1) {//多店
                    span_Selecttype = '多店';
                    each_list_hidden = customer_list_hidden;
                } else if (Selecttype1 == 2) {//多省
                    span_Selecttype = '多省';
                    each_list_hidden = province_list_hidden;
                } else if (Selecttype1 == 3) {//商品专员
                    span_Selecttype = '商品专员';
                    each_list_hidden = goods_manager_list_hidden;
                } else if (Selecttype1 == 4) {//经营模式
                    span_Selecttype = '经营模式';
                    each_list_hidden = mathod_list_hidden;
                }

            var element = $([
                '<tr>',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id" class="layui-hide" value="">',
                        '<input type="text" name="Yuncang" class="layui-hide" value="长沙云仓">',
                        '<input type="text" name="Selecttype" class="layui-hide" value="0">',
                        '<button type="button" class="layui-btn layui-btn-normal get_changsha_goods_config" id="">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_changsha_goods_config">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)

                element.find('.span_Selecttype').html(span_Selecttype);

                //选了多店/多省/商品专员/经营模式后 展示对应的值列表出来
                if (Selecttype1 != '') {
                    var gender = element.find('.Commonfield')[0];
                    var genderSelect = xmSelect.render({
                        el: gender,
                        filterable: true,
                        name: 'Commonfield',
                        data: function(){
                            return each_list_hidden
                        }
                    })

                    element.find('input[name="Selecttype"]').val(Selecttype1);
                }


                element.find('.get_changsha_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;

                    var Yuncang = element.find('input[name="Yuncang"]').val();
                    var Selecttype = element.find('input[name="Selecttype"]').val();
                    var Commonfield = element.find('input[name="Commonfield"]').val();
                    var GoodsNo = element.find('input[name="GoodsNo"]').val();
                    var id = element.find('input[name="id"]').val();
                    var _data = {
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo,
                        id:id
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })

                element.find('.del_changsha_goods_config').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除',{},function () {
                        var id = element.find('input[name="id"]').val();
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

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.changsha_goods_config-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_changsha_goods_config').on('click', function(){
                    var _url = url.savePuhuoZdySet_url;
                    var id = $(element).find('input[name="id"]').val();
                    var Yuncang = $(element).find('input[name="Yuncang"]').val();
                    var Selecttype = $(element).find('input[name="Selecttype"]').val();
                    var Commonfield = $(element).find('input[name="Commonfield"]').val();
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();

                    var _data = {
                        id:id,
                        Yuncang:Yuncang,
                        Selecttype:Selecttype,
                        Commonfield:Commonfield,
                        GoodsNo:GoodsNo
                    }
                    // 保存配置
                    that.savePuhuoZdySet($(element), _url, _data);
                })// .bind(genderSelect)

                //删除
                $(element).find('.del_changsha_goods_config').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除',{},function () {
                        var id = $(element).find('input[name="id"]').val();
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