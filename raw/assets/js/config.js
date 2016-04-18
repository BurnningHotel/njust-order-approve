var require = {
	baseUrl: '/assets/js'
	,
	/* 不用缓冲,在js的链接后面增加?_=xxxx*/
	urlArgs: '_t=' + TIMESTAMP
	,paths: {
		jquery: (document.all && ! window.atob) ? 'jquery-1.11.1': 'jquery'
	}
};

