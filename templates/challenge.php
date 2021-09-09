<?php 
/**
 * Nexcloud - RCDevs OpenOTP Two-factor Authentication
 *
 * @package twofactor_rcdevsopenotp
 * @author Charly ROHART
 * @copyright 2018 RCDEVS info@rcdevs.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * Displays <a href="http://opensource.org/licenses/AGPL-3.0">GNU AFFERO GENERAL PUBLIC LICENSE</a>
 * @license http://opensource.org/licenses/AGPL-3.0 GNU AFFERO GENERAL PUBLIC LICENSE
 *
 */
$rcdevsopenotp_message = "";
if(is_array($_['challenge_params'])) extract($_['challenge_params']);
?>
<style>
html #body-login .warning{ margin:0; }
</style>

<?php if ($_['error_msg']): ?>
    <fieldset style="margin:6px 0 15px 0;" class="warning">
		<legend><?php p($l->t('System throws this exception(s):')); ?></legend>
		<p><?php p($_['error_msg']); ?></p>
		<p style="font-size:0.8em;"><?php p($l->t('Please contact administrator (more details on logfile).')); ?>.</p>
    </fieldset>
<?php endif; ?>

<?php //print_r($_); ?>

<form method="POST" id="OpenOTPLoginForm" name="LoginForm">
	
	<?php if($_['status'] && $_['status'] === "pushSuccess") { ?>
		<p><?php p($l->t('You have been connected')); ?><br/><?php p($l->t('You will be redirected in 2 seconds')); ?></p>
		<input type="hidden" name="challenge" value="passme" />
		<input type="hidden" name="rcdevsopenotp_nonce" value="<?php p($_['challenge_params']['rcdevsopenotp_nonce']);?>" />
	<?php }else{ ?>	
	
		<p>Hello <?php p($_['userID']); 
		if($rcdevsopenotp_message): 
			echo ",<br/><b>"; 
			p($rcdevsopenotp_message); 
			echo "</b>"; 
		endif; ?>
		</p>
		<?php if(!$_['error_msg']){ ?>
			<p id="timout_cell" style="padding:10px 0; font-style:italic;"><?php p($l->t('Timeout:')); ?> <span id="timeout"><?php p($rcdevsopenotp_timeout) . " " . p($l->t('seconds')); ?></p>
			<?php foreach( $_['challenge_params'] as $param => $val){ ?>
				<input type="hidden" name="<?php p($param); ?>" value="<?php p($val); ?>">
			<?php } ?>
			
			<?php if( $rcdevsopenotp_u2fChallenge ){ ?>
				<input type="hidden" name="openotp_u2f" value="">
				<input type="hidden" name="challenge" value="" />
				<div id="u2f_display" class="display">
				<?php if( $rcdevsopenotp_otpChallenge ){ ?>
					<b><?php p($l->t('U2F response')); ?></b> &nbsp; <span class="blink" id="u2f_activate">[<?php p($l->t('Activate Device')); ?>]</span>
				<?php }else{ ?>
					<p style="text-align:center;padding-top:10px;">
						<img src="<?php p($rcdevsopenotp_appWebPath) ?>/img/u2f.png"><br/>
						<span class="blink" id="u2f_activate">[<?php p($l->t('Activate Device')); ?>]</span>
					</p>
				<?php } ?>		
				</div>	
			<?php } ?>			
			
			<?php if( $rcdevsopenotp_otpChallenge || ( !$rcdevsopenotp_otpChallenge && !$rcdevsopenotp_u2fChallenge ) ){ ?>
			<div id="actions" class="display">
				<input type="password" id="openotp_password" name="challenge" placeholder="One Time Password" autocomplete="off" autocorrect="off" required autofocus/>
				<input type="submit" id="openotp_submit" class="login primary icon-confirm-white" title="" value="<?php p($l->t('Login')); ?>" />
			</div>
			<?php } ?>		
			<div id="retry"></div>
		<?php }else{ ?>
				<input type="button" id="openotp_retry" class="login primary icon-confirm-white" title="" value="<?php p($l->t('Retry')); ?>" />		
		<?php } ?>

	<?php } ?>
</form>

<script type="text/javascript" nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>">

document.addEventListener('DOMContentLoaded', function() {
	$(document).ready(function () {
	/* Helpers */
	String.prototype.ucwords = function() {
	  str = this.toLowerCase();
	  return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
	  	function(s){
	  	  return s.toUpperCase();
		});
	};

    setInterval(function() {
           $(".blink").animate({opacity:0.1}, 1000).animate({opacity:1}, 1000);
    }, 500);


	/*Handle Push Challenge*/
	<?php if($_['status'] && $_['status'] === "pushSuccess") { ?>
		$("#OpenOTPLoginForm").submit();
	<?php } ?>
		
	if ( $("#openotp_retry").length ) {
		$(this).on('click', function(){
		    window.location = "";    
		});
	}	
	
	$(document).arrive("#openotp_retry", function() {
		$("#openotp_retry").on( "click", function() {
		    window.location = "";    
		});
	});
	
    function login_u2f (request) {
    	var u2f_handles = [];
		var u2fProblemMsg = "<b style=\"color:darkorange;\">" + t('twofactor_rcdevsopenotp', 'A problem occurs, please verify your configuration:') + "</b><br/><ul><li>- " + t('twofactor_rcdevsopenotp', 'FIDO client communication with the public AppID URL requires SSL. Verify your AppID and communication in between.') + " </li><li>- " + t('twofactor_rcdevsopenotp', 'Onwcloud App URL (U2F facets) MUST be under the same DNS domain suffix as the AppID URL (configured in RCDevs MFA Server - WebADM WebPortal)') + "</li><li>- " + t('twofactor_rcdevsopenotp', 'Fido U2F login Method is only available for Chrome, Firefox and Opera. Internet Explorer and other Web browser are coming soon.') + "</li></ul><br/><a style=\"text-decoration:underline;\" target=\"_blank\" href=\"https://www.rcdevs.com/docs/howtos/openotp_u2f/openotp_u2f/\">" + t('twofactor_rcdevsopenotp', 'Read more on RCDevs Docs site') + "</a><br/><br/>";
		var u2fErrorCodes = {
			2: 'Invalid U2F request',
			3: 'Unsupported U2F client',
			4: 'Unsupported U2F device',
			5: 'U2F request timed out',
			6: 'Unknown U2F error',
			10: u2fProblemMsg
		};
		
    	for (i=0, len=request.keyHandles.length; i<len; i++) {
           u2f_handles.push({version:request.version,keyHandle:request.keyHandles[i]});
    	}
    	u2f.sign(request.appId, request.challenge, u2f_handles, function(response) {
            if (response.errorCode) {
				$('#retry').html('<input type="button" id="openotp_retry" class="login primary icon-confirm-white" title="" value="Retry" />');
				if (response.errorMessage) $('#u2f_display').html("<p style='font-style: italic; font-weight:bold;'>" + response.errorMessage.ucwords() + "</p><br/>" + u2fErrorCodes[10]);
				else if (response.errorCode != 5) $('#u2f_display').html("<p style='font-style: italic; font-weight:bold;'>" + t('twofactor_rcdevsopenotp', u2fErrorCodes[response.errorCode]) + "</p>" + t('twofactor_rcdevsopenotp', u2fErrorCodes[10]));
				else if (response.errorCode == 5) $('#u2f_display').html("<p style='font-style: italic; font-weight:bold;'>" + u2fErrorCodes[response.errorCode] + "</p>");
				else $('#u2f_display').html("<p style='font-style: italic; font-weight:bold;'>" + u2fErrorCodes[6] + "</p>");
				console.log("OpenOTP Fido U2F signature Log #Code:" + response.errorCode);								
            } else {
				document.getElementsByName('openotp_u2f')[0].value = JSON.stringify(response); 
				document.getElementsByName('challenge')[0].value = "dummy"; 
				$('#OpenOTPLoginForm').submit();				
            }
	    }, <?php echo $rcdevsopenotp_timeout+1; ?> ); 	
     }
	 
     function login_fido2 (request) {
    	var fido2_request = {
            challenge: Base64Binary.decodeArrayBuffer(request.challenge),
            rpId: request.rpId,
            timeout: <?php echo $rcdevsopenotp_timeout*1000; ?>,
            allowCredentials: []
    	};
    	for (i=0, len=request.credentialIds.length; i<len; i++) {
            var allowCredential = {
            	id: Base64Binary.decodeArrayBuffer(request.credentialIds[i]),
	           	transports: ['usb', 'nfc', 'ble'],
	            type: 'public-key'
            };
            fido2_request['allowCredentials'].push(allowCredential);
        }
    	navigator.credentials.get({'publicKey': fido2_request}).then(function (assertion) {
            var response = {};
            for (i in assertion.response) {
            	response[i] = Base64Binary.encode(assertion.response[i]);
            }
			document.getElementsByName('openotp_u2f')[0].value = JSON.stringify(response); 
			document.getElementsByName('challenge')[0].value = "dummy"; 
			$('#OpenOTPLoginForm').submit();	
    	}).catch (function (error) {
			$('#u2f_display').html("<p style='font-style: italic; font-weight:bold;'>" + error.message + "</p>");
        });
    }

    <?php if ($rcdevsopenotp_u2fChallenge) { ?>
            if (typeof navigator.credentials == 'object') {
                var request = <?php echo $rcdevsopenotp_u2fChallenge; ?>;
                if (request.credentialIds) login_fido2(request);
                if (request.keyHandles) login_u2f(request);
            } else {
				$('#u2f_activate').html('<h2>[' + t('twofactor_rcdevsopenotp', 'Browser Not Supported') + ']</h2>'); 
				$('#u2f_activate').css('color','darkorange'); 
            }
            <?php
        } ?>	
	

	<?php if(!$_['error_msg'] && ($_['status'] && $_['status'] !== "pushSuccess")):?>
		
	var c = <?php p($rcdevsopenotp_timeout); ?>;
	var base = <?php p($rcdevsopenotp_timeout); ?>;
	var static_width = "";
	function count()
	{
		plural = c <= 1 ? "" : "s";
		$("#timeout").html(c + " " + t('twofactor_rcdevsopenotp', 'second'+ plural) );
		var div_width = $('#div_orange').width();
		if(!static_width) static_width = div_width;
		var new_width =  Math.round(c*static_width/base);
		$('#div_orange').css('width',new_width+'px');

		if(c == 0 || c < 0) {
			c = 0;
			clearInterval(timer);
			$("#timout_cell").html("<b style='color:darkorange;'>" + t('twofactor_rcdevsopenotp', 'Login timedout!') + "</b>");
			$(".display").html("");
			$('#retry').html('<input type="button" id="openotp_retry" class="login primary icon-confirm-white" title="" value="' + t('twofactor_rcdevsopenotp', 'Retry') + '" />');
		}
		c--;
	}
	count();

	var timer = setInterval(function() {count();  }, 1000);
	<?php endif; ?>
});
}, false);
</script>
