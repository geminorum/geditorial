jQuery(document).ready(function($){

	// Restore the Edit Flow submenu if there are no modules enabled
	// We need it down below for dynamically rebuilding the link list when on the settings page
	// var ef_settings_submenu_html = '<div class="wp-submenu"><div class="wp-submenu-wrap"><div class="wp-submenu-head">Editorial</div><ul><li class="wp-first-item current"><a tabindex="1" class="wp-first-item current" href="admin.php?page=geditorial-settings">Editorial</a></li></ul></div></div>';
	// if ( $( 'li#toplevel_page_geditorial-settings .wp-submenu' ).length == 0 ) {
	// 	$( 'li#toplevel_page_geditorial-settings' ).addClass('wp-has-submenu wp-has-current-submenu wp-menu-open');
	// 	$( 'li#toplevel_page_geditorial-settings' ).append( ef_settings_submenu_html );
	// 	$( 'li#toplevel_page_geditorial-settings .wp-submenu' ).show();
	// }

	$('.fields-check-all').click(function(e){
		$(this).closest('table').find('.fields-check').prop('checked',this.checked);
	});

	$('.fields-check').click(function(e){
		$(this).closest('table').find('.fields-check-all').prop('checked',false);
	});

	$('.button-toggle').click(function(){

		if ( $(this).hasClass('button-primary') )
			var module_action = 'enable';
		else if ( $(this).hasClass('button-remove') )
			var module_action = 'disable';

		var slug = $(this).closest('.module').attr('id');
		var change_module_nonce = $('#'+slug+' #module-nonce').val();
		var data = {
			// action: 'change_geditorial_module_state',
			action: 'geditorial_settings',
			sub: 'module_state',
			module_action: module_action,
			module_nonce: change_module_nonce,
			module_slug: slug,
			// change_module_nonce: change_module_nonce,
			// slug: slug,
		}

		// console.log(data);

		$.post( ajaxurl, data, function(response) {

			if (response.success) {
				$('#'+slug+' .button-toggle').hide();

				if ( 'disable' == module_action ) {

					$('#'+slug).addClass('module-disabled').removeClass('module-enabled');
					$('#'+slug+' .button-toggle.button-primary').show();
					$('#'+slug+' .button-configure').hide().addClass('hidden');

				} else if ( 'enable' == module_action ) {

					$('#'+slug).addClass('module-enabled').removeClass('module-disabled');
					$('#'+slug+' .button-toggle.button-remove').show();
					$('#'+slug+' .button-configure').show().removeClass('hidden');

				}

			} else {

			}

			return false;
			//
			// if ( response == 1 ) {
			//
			// 	$('#' + slug + ' .button-toggle' ).hide();
			// 	if ( module_action == 'disable' ) {
			//
			// 		$('#' + slug).addClass('module-disabled').removeClass('module-enabled');
			// 		$('#' + slug + ' .button-toggle.button-primary' ).show();
			// 		$('#' + slug + ' a.configure-geditorial-module' ).hide().addClass('hidden');
			//
			// 		// If there was a configuration URL in the module, let's hide it from the left nav too
			// 		if ( $('#' + slug + ' a.configure-geditorial-module' ).length > 0 ) {
			// 			var configure_url = $('#' + slug + ' a.configure-geditorial-module' ).attr('href').replace(ef_admin_url, '');
			// 			var top_level_menu = $('#' + adminpage );
			// 			$('.wp-submenu-wrap li a', top_level_menu ).each(function(){
			// 				if ( $(this).attr('href') == configure_url )
			// 					$(this).closest('li').fadeOut(function(){ $(this).remove(); });
			// 			});
			// 		}
			//
			// 	} else if ( module_action == 'enable' ) {
			//
			// 		$('#' + slug).addClass('module-enabled').removeClass('module-disabled');
			// 		$('#' + slug + ' .button-toggle.button-remove' ).show();
			// 		$('#' + slug + ' a.configure-geditorial-module' ).show().removeClass('hidden');
			//
			// 		// If there was a configuration URL in the module, let's go through the complex process of adding it again to the left nav
			// 		if ( $('#' + slug + ' a.configure-geditorial-module' ).length > 0 ) {
			// 			// Identify the order it should be in
			// 			var link_order = 0;
			// 			var counter = 0;
			// 			$('.geditorial-module.has-configure-link').each(function(key,item){
			// 				if ( $(this).attr('id') == slug && !$('a.configure-geditorial-module', this).hasClass('hidden') )
			// 					link_order = counter;
			// 				if ( !$('a.configure-geditorial-module', this).hasClass('hidden') )
			// 					counter++;
			// 			});
			// 			// Build the HTML for the new link
			// 			var configure_url = $('#' + slug + ' a.configure-geditorial-module' ).attr('href').replace(ef_admin_url, '');
			// 			var top_level_menu = $('#' + adminpage );
			// 			var html_title = $('#' + slug + ' h4').html();
			// 			var html_insert = '<li><a class="geditorial-settings-fade-in" style="display:none;" href="' + configure_url + '" tabindex="1">' + html_title + '</a>';
			// 			$('.wp-submenu-wrap ul li', top_level_menu).each(function(key,item) {
			// 				if ( key == link_order )
			// 					$(this).after(html_insert);
			// 			});
			// 			// Trick way to do a fade in: add a class of 'geditorial-settings-fade-in' and run it after the action
			// 			$('.geditorial-settings-fade-in').fadeIn().removeClass('geditorial-settings-fade-in');
			// 		}
			// 	}
			// }
			// return false;

		});

		return false;
	});

});
