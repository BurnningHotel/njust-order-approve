define('utils/confirm', ['jquery', 'bootbox'], function($) {
    var data = {};
    var defaultMax = 3;
    var iconfirm = function(key, callback, info) {
        data[key] = data[key]===undefined ? {} : data[key];
        data[key].max = data[key].max===undefined ? defaultMax : data[key].max;

        if (data[key].allowAutoCallback) {
            return callback && callback();
        }

        var dialogData = {
            title: info.title
            ,message: info.message
            ,buttons: {
            }
        };
        if (info.label.cancel) {
            dialogData.buttons.cancel = {
                label: info.label.cancel
                ,className: 'btn btn-default'
                ,callback: function(evt) {
                    var $ele = $(evt.target).parents('.modal').find('input');
                    if ($ele.length) {
                        if ($ele.get(0).checked) {
                            data[key].allowAutoCallback = true;
                        }
                    }
                }
            };
        }
        dialogData.buttons.confirm = {
            label: info.label.ok
            ,className: 'btn btn-warning'
            ,callback: function(evt) {
                var $ele = $(evt.target).parents('.modal').find('input');
                if ($ele.length) {
                    if ($ele.get(0).checked) {
                        data[key].allowAutoCallback = true;
                    }
                }
                callback && callback();
            }
        };
        if (info.label.checkbox && data[key].max <= 1) {
            dialogData.message += [
                '<br/>'
                ,'<label><input type="checkbox" />&#160;'
                ,info.label.checkbox
                ,'</label>'
            ].join('');
        }
        data[key].max = data[key].max - 1;
        bootbox.dialog(dialogData);
    };

    return iconfirm;
});

