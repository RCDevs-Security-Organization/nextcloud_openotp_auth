<?php

/**
 *
 * @copyright Copyright (c) 2026, RCDevs (info@rcdevs.com)
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

declare(strict_types=1);

namespace OCA\OpenOTPAuth\AuthService;

use Exception;
use nusoap_client;
use OCA\OpenOTPAuth\Config;
use OCA\OpenOTPAuth\Helper\RequestParamHelper;
use Psr\Log\LoggerInterface;

class OpenotpAuthException extends Exception
{
}

class OpenotpAuth
{
	private const NB_SERVERS = 2;

	private string $home;
	/** @var array<int,string> */
	private array $serverUrls;
	private string $clientId;
	private string $apiKey;
	private string $proxyHost;
	private string $proxyPort;
	private string $proxyUsername;
	private string $proxyPassword;
	private ?nusoap_client $soapClient = null;
	private string $contextName = '__Host-OpenOTPContext';
	private int $contextSize = 32;
	private int $contextTime = 2500000;
	private string $sourceIp;

	/**
	 * @param array<string,mixed> $params
	 */
	public function __construct(
		private LoggerInterface $logger,
		array $params,
		string $home = '',
		string $sourceIp = ''
	) {
		$this->home = $home;
		$this->sourceIp = $sourceIp;
		$this->serverUrls = [
			'1' => RequestParamHelper::stringParam($params, 'rcdevsopenotp_server_url1'),
			'2' => RequestParamHelper::stringParam($params, 'rcdevsopenotp_server_url2'),
		];
		$this->clientId = RequestParamHelper::stringParam($params, 'rcdevsopenotp_client_id');
		$this->apiKey = RequestParamHelper::stringParam($params, 'rcdevsopenotp_api_key');
		$this->proxyHost = RequestParamHelper::stringParam($params, 'rcdevsopenotp_proxy_host');
		$this->proxyPort = RequestParamHelper::stringParam($params, 'rcdevsopenotp_proxy_port');
		$this->proxyUsername = RequestParamHelper::stringParam($params, 'rcdevsopenotp_proxy_username');
		$this->proxyPassword = RequestParamHelper::stringParam($params, 'rcdevsopenotp_proxy_password');
	}

	public function checkFile(string $file): bool
	{
		return file_exists($this->home . '/' . $file);
	}

	/** @return array<int,string> */
	public function getServerUrls(): array
	{
		return $this->serverUrls;
	}

	/** @return array{domain:string,username:string}|string */
	public function getDomain(string $username): array|string
	{
		$pos = strpos($username, '\\');
		if ($pos !== false) {
			return [
				'domain' => substr($username, 0, $pos),
				'username' => substr($username, $pos + 1),
			];
		}

		return '';
	}

	public function getContextName(): string
	{
		return $this->contextName;
	}

	public function getContextSize(): int
	{
		return $this->contextSize;
	}

	public function getContextTime(): int
	{
		return $this->contextTime;
	}

	private function formatSoapFaultMessage(string $method, mixed $response): string
	{
		$faultCode = '';
		$faultString = '';

		if (is_array($response)) {
			$faultCode = RequestParamHelper::stringValue($response['faultcode'] ?? '');
			$faultString = RequestParamHelper::stringValue($response['faultstring'] ?? '');
		}

		if ($faultCode === '' && $faultString === '') {
			return $method . ', error: SOAP fault without details';
		}

		return $method . ', error: ' . $faultCode . ' / ' . $faultString;
	}

	/** @return array<string,mixed>|false */
	private function normalizeSoapResponse(mixed $response): array|false
	{
		if (!is_array($response)) {
			return false;
		}

		$result = [];
		foreach ($response as $key => $value) {
			if (!is_string($key)) {
				return false;
			}
			$result[$key] = $value;
		}

		return $result;
	}

	private function soapRequest(string $serverId): bool
	{
		$proxyHost = '';
		$proxyPort = '';
		$proxyUsername = '';
		$proxyPassword = '';

		if ($this->proxyHost !== '' && $this->proxyPort !== '') {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			if ($this->proxyUsername !== '' && $this->proxyPassword !== '') {
				$proxyUsername = $this->proxyUsername;
				$proxyPassword = $this->proxyPassword;
			}
		}

		$soapClient = new nusoap_client(
			$this->serverUrls[$serverId] ?? '',
			false,
			$proxyHost,
			$proxyPort,
			$proxyUsername,
			$proxyPassword,
			30
		);
		$soapClient->setDebugLevel(0);
		$soapClient->soap_defencoding = 'UTF-8';
		$soapClient->decode_utf8 = false;
		$soapClient->setUseCurl(true);
		$soapClient->setCurlOption(CURLOPT_HTTPHEADER, [
			'Content-type: text/xml;charset="utf-8"',
			"WA-API-Key: {$this->apiKey}",
		]);

		$this->soapClient = $soapClient;
		return true;
	}

	/** @return array<string,mixed>|false */
	public function openOTPSimpleLogin(string $username, string $domain, ?string $password, string $option, string $context): array|false
	{
		for ($i = 1; $i <= self::NB_SERVERS; $i++) {
			$this->soapRequest((string)$i);
			$resp = $this->soapClient?->call('openotpSimpleLogin', [
				'username' => $username,
				'domain' => $domain,
				'anyPassword' => $password,
				'client' => $this->clientId,
				'apiKey' => $this->apiKey,
				'source' => $this->sourceIp,
				'options' => $option,
				'context' => $context,
				'retryId' => '',
				'virtual' => '',
			], 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($this->soapClient?->fault) {
				$message = $this->formatSoapFaultMessage(__METHOD__, $resp);
				$this->logger->error($message, ['app' => Config::APP_ID]);
				return false;
			}

			$err = $this->soapClient?->getError();
			if ($err) {
				$message = __METHOD__ . ', error: ' . $err;
				$this->logger->error($message, ['app' => Config::APP_ID]);
				continue;
			}

			return $this->normalizeSoapResponse($resp);
		}

		return false;
	}

	/** @return array<string,mixed>|false */
	public function openOTPChallenge(string $username, string $domain, string $state, ?string $password, string $u2f, ?string $sample): array|false
	{
		for ($i = 1; $i <= self::NB_SERVERS; $i++) {
			$this->soapRequest((string)$i);
			$resp = $this->soapClient?->call('openotpChallenge', [
				'username' => $username,
				'domain' => $domain,
				'session' => $state,
				'otpPassword' => $password,
				'u2fResponse' => $u2f,
				'voiceSample' => $sample,
			], 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($this->soapClient?->fault) {
				$message = $this->formatSoapFaultMessage(__METHOD__, $resp);
				$this->logger->error($message, ['app' => Config::APP_ID]);
				return false;
			}

			$err = $this->soapClient?->getError();
			if ($err) {
				$message = __METHOD__ . ', error: ' . $err;
				$this->logger->error($message, ['app' => Config::APP_ID]);
				continue;
			}

			return $this->normalizeSoapResponse($resp);
		}

		return false;
	}

	/** @return array<string,mixed>|false */
	public function openOTPStatus(string $serverNumber): array|false
	{
		$this->soapRequest($serverNumber);
		$resp = $this->soapClient?->call('openotpStatus', []);
		return $this->normalizeSoapResponse($resp);
	}
}
