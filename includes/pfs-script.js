jQuery(document).ready(function(){
	jQuery(".pfs-post-link").click(function(){
		var $box = jQuery(this).siblings('div');
		var top = ( jQuery(window).height() - $box.height() )/2;
		var left = ( jQuery(window).width() - $box.width() )/2;
		if (top<0) top = 50;
		$box.css({top:top+"px",left:left+"px"}).show();
		
	});
	jQuery(".closex").click(function(){
		jQuery(this).parent().hide();
	});
    jQuery("form.pfs").submit(function() {
        jQuery(this).ajaxSubmit({
            type: "POST",
            url: jQuery(this).attr('action'),
            dataType:'json',
            beforeSend: function(){
                jQuery('.pfs-post-form #post').val('posting...');
            },
            complete: function(request,textStatus,error) {
                data = jQuery.parseJSON(request.responseText);
                if (data && data.error) {
                    jQuery('#pfs-alert').addClass('error').html('<p>'+data.error+'</p>').show();
                    jQuery('.pfs-post-form #post').val('Post');
                } else {
                    jQuery('form.pfs').reset();
                    location.reload();
                }
            }
        });
        return false;
    });
});
