jQuery(function($){
	CustomWpZapier.dataService = {

		get: function(url, data, suppressNote)
		{
			return request(url, data, 'GET', suppressNote);
		},
		post: function(url, data, suppressNote)
		{
			return request(url, data, 'POST', suppressNote);
		},
		put: function(url, data, suppressNote)
		{
			return request(url, data, 'PUT', suppressNote);
		},
		delete: function(url, data, suppressNote)
		{
			return request(url, data, 'DELETE', suppressNote);
		},
		head: function(url, data, suppressNote)
		{
			return request(url, data, 'HEAD', suppressNote);
		}

	}
	function request(url, data, method, suppressNote)
	{
		CustomWpZapier.notificationService.showLoader();
		return $.ajax({ 
			url: apiUrl(url), 
			method:method, 
			data: data, 
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', CustomWpZapier.api.nonce );
			}
		}).done(function(data){
			if(!suppressNote)
			CustomWpZapier.notificationService.handleResponse(data);
		}).error(function(data){
			if(data.status == 403)
			{
				CustomWpZapier.notificationService.handleResponse({
					Status: 0,
					Message: 'Please reload your page, your session seems to be expired.'
				});
			}
			else
			{
				CustomWpZapier.notificationService.handleResponse(data.responseText);
			}
		}).always(function(){
			CustomWpZapier.notificationService.hideLoader();
		});
	}
	function apiUrl(url){
		return CustomWpZapier.api.base + url;
	}

});