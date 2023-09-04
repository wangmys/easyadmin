define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        // save_url:ea.url('system.puhuo.config/saveConfig'),
        save_warehouse_config_url:ea.url('system.puhuo.config/saveWarehouseConfig'),
        save_lianma_config_url:ea.url('system.puhuo.config/saveLianmaConfig'),
        save_warehouse_qima_config_url:ea.url('system.puhuo.config/saveWarehouseQimaConfig'),
        save_listing_days_config_url:ea.url('system.puhuo.config/saveListingDaysConfig'),
        save_end_lianma_config_url:ea.url('system.puhuo.config/saveEndLianmaConfig'),
        // del_url:ea.url('/system.puhuo.config/delConfig'),
        // index_url:ea.url('/system.puhuo.config/index')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.warehouse_config();
             this.lianma_config();
             this.warehouse_qima_config();
             this.listing_days_config();
             this.end_lianma_config();
        },
        // 保存配置
        // saveConfig:function (element,_url,_data) {
        //     ea.request.post({
        //         url:_url,
        //         data:_data
        //     },function (res) {
        //         // console.log(res);
        //         element.attr('lay-id',res.data.id);
        //         element.find('input[name="sign_id[]"]').val(res.data.sign_id)
        //         ea.msg.success(res.msg);
        //      });
        // },
        // 保存仓库预留参数配置
        saveWarehouseConfig:function (element,_url,_data) {
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
        // delConfig:function (_this,_url,_id) {
        //     ea.request.get({
        //         url:_url,
        //         data:{id:_id}
        //     },function (res) {
        //         if(res.code == 1){
        //             _this.parents('tr').remove();
        //             layer.closeAll()
        //         }
        //         ea.msg.success(res.msg);
        //     })
        // },

        //仓库预留参数配置
        warehouse_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.warehouse_config-select'), function (key, element) {


                $(element).find('.get_warehouse_config').on('click', function(){
                    var _url = url.save_warehouse_config_url;
                    var warehouse_reserve_smallsize = $(element).find('input[name="warehouse_reserve_smallsize"]').val();
                    var warehouse_reserve_mainsize = $(element).find('input[name="warehouse_reserve_mainsize"]').val();
                    var warehouse_reserve_bigsize = $(element).find('input[name="warehouse_reserve_bigsize"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        warehouse_reserve_smallsize:warehouse_reserve_smallsize,
                        warehouse_reserve_mainsize:warehouse_reserve_mainsize,
                        warehouse_reserve_bigsize:warehouse_reserve_bigsize,
                        sign_id:sign_id,
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveWarehouseConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

                
            ea.listen();
        },


        //门店上铺货连码标准配置
        lianma_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.lianma_config-select'), function (key, element) {


                $(element).find('.get_lianma_config').on('click', function(){
                    var _url = url.save_lianma_config_url;
                    var store_puhuo_lianma_nd = $(element).find('input[name="store_puhuo_lianma_nd"]').val();
                    var store_puhuo_lianma_xz = $(element).find('input[name="store_puhuo_lianma_xz"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        store_puhuo_lianma_nd:store_puhuo_lianma_nd,
                        store_puhuo_lianma_xz:store_puhuo_lianma_xz,
                        sign_id:sign_id,
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveWarehouseConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

                
            ea.listen();
        },

        //仓库齐码参数配置
        warehouse_qima_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.warehouse_qima_config-select'), function (key, element) {


                $(element).find('.get_warehouse_qima_config').on('click', function(){
                    var _url = url.save_warehouse_qima_config_url;
                    var warehouse_qima_nd = $(element).find('input[name="warehouse_qima_nd"]').val();
                    var warehouse_qima_xz = $(element).find('input[name="warehouse_qima_xz"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        warehouse_qima_nd:warehouse_qima_nd,
                        warehouse_qima_xz:warehouse_qima_xz,
                        sign_id:sign_id,
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveWarehouseConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

                
            ea.listen();
        },

        //单店上市天数不再铺限制配置
        listing_days_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.listing_days_config-select'), function (key, element) {


                $(element).find('.get_listing_days_config').on('click', function(){
                    var _url = url.save_listing_days_config_url;
                    var listing_days = $(element).find('input[name="listing_days"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        listing_days:listing_days,
                        sign_id:sign_id,
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveWarehouseConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

                
            ea.listen();
        },


        //仓库齐码参数配置
        end_lianma_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.end_lianma_config-select'), function (key, element) {


                $(element).find('.get_end_lianma_config').on('click', function(){
                    var _url = url.save_end_lianma_config_url;
                    var end_puhuo_lianma_nd = $(element).find('input[name="end_puhuo_lianma_nd"]').val();
                    var end_puhuo_lianma_sjdk = $(element).find('input[name="end_puhuo_lianma_sjdk"]').val();
                    var end_puhuo_lianma_xz = $(element).find('input[name="end_puhuo_lianma_xz"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        end_puhuo_lianma_nd:end_puhuo_lianma_nd,
                        end_puhuo_lianma_sjdk:end_puhuo_lianma_sjdk,
                        end_puhuo_lianma_xz:end_puhuo_lianma_xz,
                        sign_id:sign_id,
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveWarehouseConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

                
            ea.listen();
        }



    };
    return Controller;
});