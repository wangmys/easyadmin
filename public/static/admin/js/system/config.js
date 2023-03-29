define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form;

    var Controller = {
        index: function () {

            var app = new Vue({
                el: '#app',
                data: {
                    upload_type: upload_type
                }
            });

            form.on("radio(upload_type)", function (data) {
                app.upload_type = this.value;
            });



        var $ = layui.jquery;
        var index = 1;

        $('.add').on('click', function(){
            var element = $([
                '<tr>',
                    '<td>'+ index +'</td>',
                    '<td class="gender"></td>',
                    '<td class="hobby"></td>',
                    '<td class="handler">',
                        '<button type="button" class="layui-btn layui-btn-normal get">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))

            var hobby = element.find('.hobby')[0];
            var hobbySelect = xmSelect.render({
                el: hobby,
                data: function(){
                    return [
                        {name: '篮球' + index, value: 1},
                        {name: '足球' + index, value: 2},
                        {name: '乒乓球' + index, value: 3},
                    ]
                }
            })

            var gender = element.find('.gender')[0];
            xmSelect.render({
                el: gender,
                radio: true,
                clickClose: true,
                model: { label: { type: 'text' } },
                data: function(){
                    return [
                        {name: '男', value: 1},
                        {name: '女', value: 2},
                        {name: '保密', value: 3},
                    ]
                },
                on: function(data){
                    var changeItem = data.change[0];
                    if(data.isAdd && changeItem.value == 3){
                        this.update({ disabled: true })
                    }else{
                        this.update({ disabled: false })
                    }
                }.bind(hobbySelect),
            })

            element.find('.get').on('click', function(){
                alert('valueStr: ' + this.getValue('valueStr'));
            }.bind(hobbySelect))

            element.find('.del').on('click', function(){
                $(this).parents('tr').remove();
            })

            index++;

            $('#form-create tbody').append(element)
        });


            ea.listen();
        }
    };
    return Controller;
});