define('page/order', ['jquery', 'utils/bootbox'], function(jQuery, Bootbox) {
	$(document).on('click', '.app-op-per-handler', function() {
		var url = 'ajax/request/get-op-form';
		var key = $(this).attr('data-key');
		var id = $(this).attr('data-id');
		$.get(url, {
			key: key
			,id: id
		}, function(modal) {
			$(modal).modal('show');
		});
	});
	$(document).on('click', '.app-op-submit-handler', function() {
		var $modal = $(this).parents('.modal');
		var $form = $modal.find('form');
		var action = $form.attr('action');
		$.post(action, $form.serialize(), function(response) {
			response = response || {};
			var code = response.code;
			var message = response.message;
			var id = response.id;
			if (code) {
				Bootbox.alert(response.message);
				return;
			}
			var $oph = $(['[data-id=', id, ']'].join(''));
			$oph.hide();
			$modal.modal('hide');
		});
	});
	$(document).on('click', '.app-q-search-handler', function() {
		var $form = $(this).parents('form');
		var q = $form.find('[name=q]').val();
		var action = $form.attr('action');
		search({
			url: action
			,q: q
		});
		return false;
	});

	function search(params) {
		$.get(params.url, params, function(html) {
			$('.board-content').html(html);
		});
	}

});

