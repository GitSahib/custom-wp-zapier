jQuery(function($){ 
	var container = $(CustomWpZapier.container); 
	CustomWpZapier.notificationService = {
		handleResponse: handleResponse,
		showLoader: showLoader,
		hideLoader: hideLoader,
		showSuccess: showSuccess,
		showWarning: showWarning,
		showError: showError
	} 
	function handleResponse(data)
	{
		if(data.Status == 1) 
			showSuccess(data.Message || 'Operation Successfull.'); 
		else 
			showError(data.Message || "Operation failed.");
	}
	function showLoader()
	{
		container.find(".zapier-thinking").addClass("is-active");
	}
	function hideLoader()
	{
		container.find(".zapier-thinking").removeClass('is-active');
	}

	function showSuccess(message)
	{ 
		prependToBody(notice.success(message));
	}
	function showError(message)
	{
		prependToBody(notice.error(message));
	}
	function showWarning(message)
	{
		prependToBody(notice.warning(message));
	}
	
	function prependToBody(element)
	{
		container.find(".body").prepend(element);
	} 
	function buildNotice(message, title, type)
	{
		$notice = $(templates.notice.replace("{message}", message)).addClass('notice-' + type);
		$notice.find('button').on("click", function(){ 
			$(this).parent('.is-dismissible').slideUp(500, function(){
				$(this).remove();
			}); 
		});
		return $notice;
	} 
	function buildTimedNotice(message, title, type, time)
	{
		$notice = buildNotice(message, title, type);
		setTimeout(function(){
			$notice.find("button").trigger("click");
		}, time || 1500);
		return $notice;
	} 
	var notice = 
	{
		error: function(message, title){
			return buildNotice(message, title, 'error');
		},
		warning: function(message, title){
			return buildNotice(message, titile, 'warning');
		},
		success: function(message, title){ 
			return buildTimedNotice(message, title, 'success'); 
		}
	};	
	var templates = {
		notice: '<div class="notice is-dismissible">'+
		    '<p>{message}</p>'+
		    '<button type="button" class="notice-dismiss">' +
				'<span class="screen-reader-text">Dismiss this notice.</span>' +
			'</button>' +
		'</div>'
	};
});