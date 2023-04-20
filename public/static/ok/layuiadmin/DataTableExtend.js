var LayUIDataTable = (function () {
    var rowData = {};
    var $;

    function checkJquery () {
        if (!$) {
            console.log("δ��ȡjquery���������Ƿ��ڵ���ConvertDataTable����֮ǰ����SetJqueryObj�������ã�")
            return false;
        } else return true;
    }

    /**
     * ת�����ݱ��
     * @param callback ˫���еĻص��������ûص��������������������ֱ�Ϊ����ǰ����е�����ֵ����ǰ�����Ԫ���ֵ����ǰ������
     * @returns {Array} ���ص�ǰ���ݱ�ǰҳ�����������ݡ����ݽṹ��<br/>
     * [
     *      {�ֶ�����1:{value:"��ǰ�ֶ�ֵ",cell:"��ǰ�ֶ����ڵ�Ԫ��td����",row:"��ǰ�ֶ�������tr����"}}
     *     ,{�ֶ�����2:{value:"",cell:"",row:""}}
     * ]
     * @constructor
     */
    function ConvertDataTable (callback) {
        if (!checkJquery()) return;
        var dataList = [];
        var rowData = {};
        var trArr = $(".layui-table-body.layui-table-main tr");// ������
        if (!trArr || trArr.length == 0) {
            console.log("δ��ȡ����������ݣ��������ݱ���Ƿ���Ⱦ��ϣ�");
            return;
        }
        $.each(trArr, function (index, trObj) {
            var currentClickRowIndex;
            var currentClickCellValue;

            $(trObj).dblclick(function (e) {
                var returnData = {};
                var currentClickRow = $(e.currentTarget);
                currentClickRowIndex = currentClickRow.data("index");
                currentClickCellValue = e.target.innerHTML
                $.each(dataList[currentClickRowIndex], function (key, obj) {
                    returnData[key] = obj.value;
                });
                callback(currentClickRowIndex, currentClickCellValue, returnData);
            });
            var tdArrObj = $(trObj).find('td');
            rowData = {};
            //  ÿ�еĵ�Ԫ������
            $.each(tdArrObj, function (index_1, tdObj) {
                var td_field = $(tdObj).data("field");
                rowData[td_field] = {};
                rowData[td_field]["value"] = $($(tdObj).html()).html();
                rowData[td_field]["cell"] = $(tdObj);
                rowData[td_field]["row"] = $(trObj);

            })
            dataList.push(rowData);
        })
        return dataList;
    }

    return {
        /**
         * ����JQuery���󣬵�һ�������������û����head��ǩ��������jquery��δִ�и÷����Ļ���ParseDataTable������HideField�������޷�ִ�У������Ҳ��� $ �Ĵ����������ʹ��LayUI���õ�Jquery������
         * var $ = layui.jquery   Ȼ��� $ ����÷���
         * @param jqueryObj
         * @constructor
         */
        SetJqueryObj: function (jqueryObj) {
            $ = jqueryObj;
        }

        /**
         * ת�����ݱ��
         */
        , ParseDataTable: ConvertDataTable

        /**
         * �����ֶ�
         * @param fieldName Ҫ���ص��ֶ�����field���ƣ�������Ϊ�ַ��������ص��У��������飨���ض��У�
         * @constructor
         */
        , HideField: function (fieldName) {
            if (!checkJquery()) return;
            if (fieldName instanceof Array) {
                $.each(fieldName, function (index, field) {
                    $("[data-field='" + field + "']").css('display', 'none');
                })
            } else if (typeof fieldName === 'string') {
                $("[data-field='" + fieldName + "']").css('display', 'none');
            } else {

            }
        }
    }
})();