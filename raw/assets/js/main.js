require(['utils/global', 'jquery', 'bootstrap', 'utils/element', 'utils/pager', 'utils/dragdrop', 'utils/retina', 'utils/require']);

require(['jquery', 'bootbox'], function($, bootbox) {
	var defaults = {};
	var $meta = $('meta[name=gini-locale]');
	if ($meta.length && $meta.attr('content')) {
		defaults.locale = $meta.attr('content');
	}
	if ($.isEmptyObject(defaults)) {
		return;
	}

	bootbox.setDefaults(defaults);
});

