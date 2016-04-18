define('utils/dropfile', ['jquery', 'cloudfs'], function($, CloudFS) {
    /**
     * @brief 
     *
     * @param url 上传url | function
     * @param handler 事件回调
     *          * over
     *          * enter
     *          * make
     *          * start
     *          * progress
     *          * success
     *          * error
     *          * abort
     *          * always
     *
     * @return 
     */
    function supportDropfile() {
        var div = document.createElement('div');
        return ('draggable' in div) || ('ondragstart' in div && 'ondrop' in div);
    }
    $.fn.dropfile = function(handler, client) {
        if (!supportDropfile()) return;
        var handler = handler || {};
        var that = this;
        $(this).on('dragover', function(evt) {
            evt.preventDefault();
            evt.stopPropagation();
            handler.over && handler.over.call(that, evt);
        });
        $(this).on('dragenter', function(evt) {
            evt.preventDefault();
            evt.stopPropagation();
            handler.enter && handler.enter.call(that, evt);
        });
        $(this).on('dragleave', function(evt) {
            evt.preventDefault();
            evt.stopPropagation();
            handler.leave && handler.leave.call(that, evt);
        });
        $(this).on('drop', function(evt) {
            evt.preventDefault();
            evt.stopPropagation();
            handler.leave && handler.leave.call(that, evt);
            var files = evt.originalEvent.dataTransfer.files;
            if (!files.length) return;
            for (var i=0,l=files.length; i<l; i++) {
                var myFile = {};
                myFile.file = files[i];
                var tmpH = undefined;
                if (handler.start) {
                    tmpH = handler.start.call(that, evt, files[i]);
                }
                CloudFS.upload(client||'', myFile, {
                    progress: (function(h) {return function(data, x) {
                        handler.progress && handler.progress.call(that, evt, data, h, x);
                    }})(tmpH)
                    ,abort: (function(h) {return function(data, x) {
                        handler.abort && handler.abort.call(that, evt, h, x);
                    }})(tmpH)
                    ,error: (function(h) {return function(data, x) {
                        handler.error && handler.error.call(that, evt, h, x);
                    }})(tmpH)
                    ,success: (function(h) {return function(data, x) {
                        handler.success && handler.success.call(that, evt, data, h, x);
                    }})(tmpH)
                    ,always: (function(h) {return function(data, x) {
                        handler.always && handler.always.call(that, evt, h, x);
                    }})(tmpH)
                });
            }
        });
    };
});
