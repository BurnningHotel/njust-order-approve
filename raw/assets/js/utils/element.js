define('utils/element', ['jquery', 'bootbox'], function($) {

    $('body').on('click', 'a[data-confirm]', function(){
        var $link = $(this);
        var href = $link.attr('href');
        bootbox.confirm({
            message: $link.data('confirm'),
            callback: function(result) {
                if (result) {
                    setTimeout(function(){
                       window.location.href = href;
                    }, 0);
                }
            }
        });
        return false;
    });

    $('body').on('click', 'a[href^="ajax:"]', function() {
        var $link = $(this);
        if ($link.data('delegated')) return false;
        $.getScript($(this).attr('href').substring(5));
        return false;
    });

    $('body').on('click', 'a[href^="gini-ajax:"]', function(e) {
        var $link = $(this);
        if ($link.data('delegated')) return false;

        e.preventDefault();

        $link.trigger('ajax-before');
        
        $.ajax({
            type: "GET",
            url: $link.attr('href').substring(10),
            success: function(html) {
                $link.trigger('ajax-success', html);
                $('body').append(html).find('script[data-ajax]').remove();
            },
            complete: function() {
                $link.trigger('ajax-complete');
            }
        });

        return false;
    });

    $('body').on('submit', 'form[action^="gini-ajax:"]', function(e) {
        if ($(this).data('delegated')) return false;

        e.preventDefault();

        var $form = $(this);

        $form.trigger('ajax-before');

        $.ajax({
            type: $form.attr('method') || "POST",
            url: $form.attr('action').substring(10),
            data: $form.serialize(),
            success: function(html) {
                $form.trigger('ajax-success', html);
                $('body').append(html).find('script[data-ajax]').remove();
            },
            complete: function() {
                $form.trigger('ajax-complete');
            }
        });

        return false;
    });

});
