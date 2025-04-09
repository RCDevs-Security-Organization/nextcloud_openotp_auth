<?php

/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
 
namespace OCA\OpenOTPAuth\AuthService;

use Exception;
use nusoap_client;
use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;
use Psr\Log\LoggerInterface;

class OpenotpAuthException extends Exception
{
}

class OpenotpAuth
{

	const NB_SERVERS = 2;

	/** @var home */
	private $home;
	/** @var server_urls */
	private $server_urls;
	/** @var client_id */
	private $client_id;
	/** @var api_key */
	private $api_key;
	/** @var proxy_host */
	private $proxy_host;
	/** @var proxy_port */
	private $proxy_port;
	/** @var proxy_username */
	private $proxy_username;
	/** @var proxy_password */
	private $proxy_password;
	/** NuSOAP object */
	private $soap_client;
	/** Logger object */
	private $logger;
	private string $context_name = '__Host-OpenOTPContext';
	private int $context_size = 32;
	private int $context_time = 2500000;

	/**
	 * @param ILogger $logger
	 * @param Array $params
	 * @param String $home
	 */
	public function __construct(LoggerInterface $logger, $params, $home = '')
	{

		$this->home = $home;
		$this->logger = $logger;
		// load config		
		$this->server_urls 		= array(
			"1" => $params['rcdevsopenotp_server_url1'],
			"2" => $params['rcdevsopenotp_server_url2']
		);
		$this->client_id		= trim($params['rcdevsopenotp_client_id']);
		$this->api_key			= trim($params['rcdevsopenotp_api_key']);
		$this->proxy_host		= trim($params['rcdevsopenotp_proxy_host']);
		$this->proxy_port		= trim($params['rcdevsopenotp_proxy_port']);
		$this->proxy_username	= trim($params['rcdevsopenotp_proxy_username']);
		$this->proxy_password	= trim($params['rcdevsopenotp_proxy_password']);
	}

	public function checkFile($file)
	{
		if (!file_exists($this->home . "/" . $file)) {
			return false;
		}
		return true;
	}

	public function getServer_urls()
	{
		return $this->server_urls;
	}

	public function getScope()
	{
		// return $this->openotp_scope;
	}

	public function getDomain($username)
	{
		$pos = strpos($username, "\\");
		if ($pos) {
			$ret['domain'] = substr($username, 0, $pos);
			$ret['username'] = substr($username, $pos + 1);
		} else {
			// $ret = $this->default_domain;
			$ret = '';
		}
		return $ret;
	}
	public function getContext_name()
	{
		return $this->context_name;
	}
	public function getContext_size()
	{
		return $this->context_size;
	}
	public function getContext_time()
	{
		return $this->context_time;
	}

	public static function getOverlay($otpChallenge, $u2fChallenge, $message, $username, $session, $timeout, $ldappw, $path, $appWebPath, $domain = NULL)
	{
		$appWebPath .= "/images";

		$overlay = <<<EOT

		$(document).ready(function(){
		function addOpenOTPDivs(){
			var overlay_bg = document.createElement("div");
			overlay_bg.id = 'openotp_overlay_bg';
			overlay_bg.style.position = 'fixed'; 
			overlay_bg.style.top = '0'; 
			overlay_bg.style.left = '0'; 
			overlay_bg.style.width = '100%'; 
			overlay_bg.style.height = '100%'; 
			overlay_bg.style.background = 'grey';
			overlay_bg.style.zIndex = "9998"; 
			overlay_bg.style["filter"] = "0.9";
			overlay_bg.style["-moz-opacity"] = "0.9";
			overlay_bg.style["-khtml-opacity"] = "0.9";
			overlay_bg.style["opacity"] = "0.9";
		
			var tokenform = document.getElementsByName("requesttoken")[0].value;
			var timezone = document.getElementById("timezone").value;
			var timezone_offset = document.getElementById("timezone_offset").value;
			var remember_login = document.getElementById("remember_login").value;
			var context = document.getElementsByName("context")[0].value;
			var overlay = document.createElement("div");
			overlay.id = 'openotp_overlay';
			overlay.style.position = 'absolute'; 
			overlay.style.top = '165px'; 
			overlay.style.left = '50%'; 
			overlay.style.width = '280px'; 
			overlay.style.marginLeft = '-180px';
			overlay.style.padding = '65px 40px 50px 40px';
			overlay.style.background = 'url($appWebPath/openotp_banner.png) no-repeat top left #E4E4E4';
			overlay.style.border = '5px solid #545454';
			overlay.style.borderRadius = '10px';
			overlay.style.MozBorderRadius = '10px';
			overlay.style.WebkitBorderRadius = '10px';
			overlay.style.boxShadow = '1px 1px 12px #555555';
			overlay.style.WebkitBoxShadow = '1px 1px 12px #555555';
			overlay.style.MozBoxShadow = '1px 1px 12px #555555';
			overlay.style.zIndex = "9999"; 
			overlay.innerHTML = '<a style="position:absolute; top:-12px; right:-12px; background-color:transparent;" href="index.php" title="close"><img src="$appWebPath/openotp_closebtn.png"/></a>'
			+ '<style>'
			+ 'blink { -webkit-animation: blink 1s steps(5, start) infinite; -moz-animation:    blink 1s steps(5, start) infinite; -o-animation:      blink 1s steps(5, start) infinite; animation: blink 1s steps(5, start) infinite; }'
			+ '	@-webkit-keyframes blink { to { visibility: hidden; } }'
			+ '@-moz-keyframes blink { to { visibility: hidden; } }'
			+ '@-o-keyframes blink { to { visibility: hidden; } }'
			+ '@keyframes blink { to { visibility: hidden; } }'
			+ '#openotp_overlay tbody tr:hover, #openotp_overlay tbody tr:active, #openotp_overlay tbody tr:focus{ background:none; }'
			+ '#body-login #openotp_overlay .button{ border:1px solid rgba(190, 190, 190, 0.9); }'
			+ '</style>'			
			+ '<div style="background-color:red; margin:0 -40px 0; height:4px; width:360px; padding:0;" id="count_red"><div style="background-color:orange; margin:0; height:4px; width:360px; padding:0;" id="div_orange"></div></div>'
			+ '<form style="margin-top:30px;" id="formlogin" name="login" method="POST">'
			+ '<input type="hidden" name="requesttoken" value="'+tokenform+'">'
			+ '<input type="hidden" id="timezone" name="timezone" value="'+timezone+'">'
			+ '<input type="hidden" id="timezone_offset" name="timezone_offset" value="'+timezone_offset+'">'
			+ '<input type="hidden" id="remember_login" name="remember_login" value="0" value="'+remember_login+'">'
			+ '<input type="hidden" name="context" value="'+context+'">'
			+ '<input type="hidden" name="openotp_state" value="$session">'
			+ '<input type="hidden" name="openotp_domain" value="$domain">'
			+ '<input type="hidden" name="user" value="$username">'
			+ '<input type="hidden" name="password" value="$ldappw">'
			+ '<table width="100%">'
			+ '<tr style="border:none;"><td style="text-align:center; font-weight:bold; font-size:14px; border:none;">$message</td></tr>'
			+ '<tr style="border:none;"><td id="timout_cell" style="text-align:center; padding-top:4px; font-weight:bold; font-style:italic; font-size:11px; border:none;">Timeout: <span id="timeout">$timeout seconds</span></td></tr>'
EOT;

		if ($otpChallenge || (!$otpChallenge && !$u2fChallenge)) {
			$overlay .= <<<EOT
			+ '<tr style="border:none;"><td id="inputs_cell" style="text-align:center; padding-top:25px; border:none;"><input style="width:165px; border:1px solid grey; background-color:white; margin-bottom:0; padding:3px; vertical-align:middle;" type="password" size=15 name="openotp_password" id="openotp_password">&nbsp;'
			+ '<input style="vertical-align:middle; padding:5px 10px; margin:5px 5px 0 0;" type="submit" value="Ok" class="button btn btn-primary"></td></tr>'
EOT;
		}

		if ($u2fChallenge) {
			$overlay .= "+ '<tr style=\"border:none;\"><td id=\"inputs_cell\" style=\"text-align:center; padding-top:5px; border:none;\"><input type=\"hidden\" name=\"openotp_u2f\" value=\"\">'";
			if ($otpChallenge) {
				$overlay .= "+ '<b>U2F response</b> &nbsp; <blink id=\"u2f_activate\">[Activate Device]</blink></td></tr>'";
			} else {
				$overlay .= "+ '<img src=\"" . $appWebPath . "/u2f.svg\"><br><br><blink id=\"u2f_activate\">[Activate Device]</blink></td></tr>'";
			}
		}

		$overlay .= <<<EOT
			+ '</table></form>';
			
			document.body.appendChild(overlay_bg);    
			document.body.appendChild(overlay); 
		}
		
		addOpenOTPDivs();
		
		/* Compute Timeout */	
		var c = $timeout;
		var base = $timeout;
		function count()
		{
			plural = c <= 1 ? "" : "s";
			document.getElementById("timeout").innerHTML = c + " second" + plural;
			var div_width = 360;
			var new_width =  Math.round(c*div_width/base);
			document.getElementById('div_orange').style.width=new_width+'px';
			
			if( document.getElementById('openotp_password') ){
				document.getElementById('openotp_password').focus();
			}
			if(c == 0 || c < 0) {
				c = 0;
				clearInterval(timer);
				document.getElementById("timout_cell").innerHTML = " <b style='color:red;'>Login timedout!</b> ";
				document.getElementById("inputs_cell").innerHTML = "<input style='padding:3px 20px;' type='button' value='Retry' class='button btn btn-primary' onclick='window.location.href=\"./\"'>";
			}
			c--;
		}
		count();
		
		
		function getInternetExplorerVersion() {
		
			var rv = -1;
		
			if (navigator.appName == "Microsoft Internet Explorer") {
				var ua = navigator.userAgent;
				var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
				if (re.exec(ua) != null)
					rv = parseFloat(RegExp.$1);
			}
			return rv;
		}
		
		var ver = getInternetExplorerVersion();
		
		if (navigator.appName == "Microsoft Internet Explorer"){
			if (ver <= 10){
				toggleItem = function(){
					
				    var el = document.getElementsByTagName("blink")[0];
				    if (el.style.display === "block") {
				        el.style.display = "none";
				    } else {
				        el.style.display = "block";
				    }
				}
				var t = setInterval(function() {toggleItem; }, 1000);
			}
		}
		
		var timer = setInterval(function() {count();  }, 1000);
		});
		
EOT;

		if ($u2fChallenge) {

			$overlay .= " $(document).ready(function(){ " . "\r\n";
			$overlay .= "if (/chrome|chromium|firefox|opera/.test(navigator.userAgent.toLowerCase())) {
			    var u2f_request = " . $u2fChallenge . ";
			    var u2f_regkeys = [];
			    for (var i=0, len=u2f_request.keyHandles.length; i<len; i++) {
			        u2f_regkeys.push({version:u2f_request.version,keyHandle:u2f_request.keyHandles[i]});
			    }
			    u2f.sign(u2f_request.appId, u2f_request.challenge, u2f_regkeys, function(response) {
					document.getElementsByName('openotp_u2f')[0].value = JSON.stringify(response); 
					document.getElementById('formlogin').submit();					
			    }, $timeout ); }" . "\r\n";
			$overlay .= " else { 
				var u2f_activate = document.getElementById('u2f_activate'); 
				u2f_activate.innerHTML = '[Not Supported]'; 
				u2f_activate.style.color='red'; 
				}" . "\r\n";
			$overlay .= " }); " . "\r\n";
		}

		return $overlay;
	}

	private function soapRequest(string $serverId)
	{

		if (($this->proxy_host !== NULL && $this->proxy_host !== '')
			&& ($this->proxy_port !== NULL && $this->proxy_port !== '')
		) {
			$proxyHost = $this->proxy_host;
			$proxyPort = $this->proxy_port;

			if ($this->proxy_username !== NULL && $this->proxy_password !== NULL) {
				$proxyUsername = $this->proxy_username;
				$proxyPassword = $this->proxy_password;
			}
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$soap_client = new nusoap_client($this->server_urls[$serverId], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, 30);
		$soap_client->setDebugLevel(0);
		$soap_client->soap_defencoding = 'UTF-8';
		$soap_client->decode_utf8 = FALSE;

		$soap_client->setUseCurl(true);
		$soap_client->setCurlOption(CURLOPT_HTTPHEADER, [
			"Content-type: text/xml;charset=\"utf-8\"",
			"WA-API-Key: {$this->api_key}",
		]);

		$this->soap_client = $soap_client;
		return true;
	}

	public function openOTPSimpleLogin($username, $domain, $password, $option, $context)
	{
		for ($i = 1; $i <= self::NB_SERVERS; $i++) {
			$this->soapRequest($i);
			$resp = $this->soap_client->call('openotpSimpleLogin', array(
				'username' => $username,
				'domain' => $domain,
				'anyPassword' => $password,
				'client' => $this->client_id,
				'apiKey' => $this->api_key,
				'source' => $_SERVER['REMOTE_ADDR'],
				// 'settings' => $this->user_settings,
				'options' => $option,
				'context' => $context,
				'retryId' => '',
				'virtual' => ''
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($this->soap_client->fault) {
				$message = __METHOD__ . ', error: ' . $resp['faultcode'] . ' / ' . $resp['faultstring'];
				$this->logger->error($message, array('app' => OpenOTPAuthApp::APP_ID));
				return false;
			}

			$err = $this->soap_client->getError();
			if ($err) {
				$message = __METHOD__ . ', error: ' . $err;
				$this->logger->error($message, array('app' => OpenOTPAuthApp::APP_ID));
				continue;
			}

			return $resp;
		}

		return false;
	}

	public function openOTPChallenge($username, $domain, $state, $password, $u2f, $sample)
	{
		for ($i = 1; $i <= self::NB_SERVERS; $i++) {
			$this->soapRequest($i);
			$resp = $this->soap_client->call('openotpChallenge', array(
				'username' => $username,
				'domain' => $domain,
				'session' => $state,
				'otpPassword' => $password,
				'u2fResponse' => $u2f,
				'voiceSample' => $sample
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($this->soap_client->fault) {
				$message = __METHOD__ . ', error: ' . $resp['faultcode'] . ' / ' . $resp['faultstring'];
				$this->logger->error($message, array('app' => OpenOTPAuthApp::APP_ID));
				return false;
			}

			$err = $this->soap_client->getError();
			if ($err) {
				$message = __METHOD__ . ', error: ' . $err;
				$this->logger->error($message, array('app' => OpenOTPAuthApp::APP_ID));
				continue;
			}

			return $resp;
		}

		return false;
	}

	public function openOTPStatus(string $serverNumber)
	{
		$this->soapRequest($serverNumber);
		return $this->soap_client->call('openotpStatus', array());
	}
}
