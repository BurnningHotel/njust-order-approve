define('board', ['jquery'], function($) {
	var myWidth = $(window).width();
	var myHeight = $(window).height();
	var clock;
	var resizeMe = function() {
		var $sidebar = $('#sidebar')
		,$board = $('#board');
		$sidebar.css('min-height', 'initial');
		$board.css('min-height', 'initial');
		var h = Math.max($sidebar.outerHeight(), $board.outerHeight(), $(window).height());
		$sidebar.css('min-height', h);
		$board.css('min-height', h);
	};
	$(window).resize(function() {
		var width = $(window).width();
		var height = $(window).height();
		if (myWidth !== width || myHeight !== height) {
			clock && clearTimeout(clock);
			clock = setTimeout(function() {
				resizeMe();
			}, 10);
			myWidth = width;
			myHeight = height;
		}
	});
	resizeMe();

    return {
        'resize': resizeMe
    };
});

