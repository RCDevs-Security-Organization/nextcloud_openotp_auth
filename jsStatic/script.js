/**
 * Nextcloud - openotp_auth
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @package openotp_auth
 * @author RCDevs
 * @copyright 2018 RCDEVS info@rcdevs.com
 */

(function ($, OC) {

	$(document).ready(function () {

		$('form[name="login"]').submit(function () {
			$(this).prepend("<span style='color:white; font-size:0.9em;'>" + t('twofactor_totp', 'Processing request. Please wait...') + "</span>");
			return true;
		});

		$('#openotp_settings #saveconfig').click(function () {
			var url = OC.generateUrl('/apps/openotp_auth/saveconfig');
			var post = {
				post: $("#openotp_settings").serialize()
			};

			$.post(url, post, function (response) {
				if ($('#message').is(":visible")) {
					$('#message').fadeOut("fast");
				}
				if (response.status == "success") {
					$('#message').removeClass('error').addClass('success').html(response.message).fadeIn('fast');
				} else {
					$('#message').removeClass('success').addClass('error').html(response.message).fadeIn('fast');
				}
			});
			return false;
		});


		$('#openotp_psettings input[name="enable_openotp"]:radio').change(function () {
			var url = OC.generateUrl('/apps/openotp_auth/saveconfig');
			var post = {
				post: $("#openotp_psettings").serialize()
			};

			$.post(url, post, function (response) {
				if ($('#message').is(":visible")) {
					$('#message').fadeOut("fast");
				}
				if (response.status == "success") {
					$('#message').removeClass('error').addClass('success').html(response.message).fadeIn('fast');
				} else {
					$('#message').removeClass('success').addClass('error').html(response.message).fadeIn('fast');
				}
			});
			return false;
		});


		$('#check_server_url1').click(function () {
			check_server_url(1);
		});

		$('#check_server_url2').click(function () {
			check_server_url(2);
		});

		if ($("#openotp_settings").length) {
			check_server_url(1);
			check_server_url(2);
		}

		if ($("#body-login div.warning #OpenOTPLoginForm").length) {
			$("#body-login div.warning").prepend('<div style="background-color:red; margin:-10px -10px 10px; height:4px; width:300px; padding:0;" id="count_red"><div style="background-color:orange; margin:0; height:4px; width:300px; padding:0;" id="div_orange"></div></div>');
		}

	});

})(jQuery, OC);

function check_server_url(id) {
	var url = OC.generateUrl('/apps/openotp_auth/check_server_url');
	var server_url_val = $("#openotp_settings #rcdevsopenotp_server_url" + id).val();

	$('#message_check_server_url' + id).hide();
	$('#message_status' + id).hide();
	$("#check_server_loading" + id).fadeIn();
	$.post(url, { server_url: server_url_val }, function (response) {
		/*if($('#message_check_server_url').is(":visible")){
			$('#message_check_server_url').fadeOut("fast"); 
		}*/
		if (response.status == "success") {
			$("#check_server_loading" + id).hide();

			console.log(response.openotpStatus);
			if (response.openotpStatus === false) {
				$('#message_status' + id).removeClass('success').addClass('error').fadeIn('fast');
				$('#message_check_server_url' + id).fadeOut('fast');
			} else {
				$('#message_status' + id).removeClass('error').addClass('success').fadeIn('fast');
				$('#message_check_server_url' + id).removeClass('error').html(response.message).fadeIn('fast');
			}
		} else {
			$("#check_server_loading" + id).hide();
			$('#message_status' + id).removeClass('success').addClass('error').fadeIn('fast');
			$('#message_check_server_url' + id).fadeOut('fast');
		}
	});
}
