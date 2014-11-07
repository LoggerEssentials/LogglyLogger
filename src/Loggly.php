<?php
namespace Dv\Amazon\Logging;

use Psr\Log\AbstractLogger;

class LogglyLogger extends AbstractLogger {
	/**
	 * @var string
	 */
	private $token;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var string
	 */
	private $endPoint;
	/**
	 * @var array
	 */
	private $tags;

	/**
	 * @param string $token
	 * @param array $tags
	 * @param string $host
	 * @param string $endPoint
	 */
	public function __construct($token, array $tags = array(), $host = 'logs-01.loggly.com', $endPoint = 'inputs') {
		if (!extension_loaded('curl')) {
			throw new \RuntimeException('The curl extension is required to use LogglyLogger');
		}
		$this->token = $token;
		$this->host = $host;
		$this->endPoint = $endPoint;
		$this->tags = join(',', $tags);
	}

	/**
	 * Logs with an arbitrary level.
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = array()) {
		try {
			$this->writeToApi($level, $message, $context);
		} catch(\Exception $e) {
		}
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 */
	private function writeToApi($level, $message, array $context) {
		$url = sprintf("https://%s/%s/%s/", $this->host, $this->endPoint, $this->token);
		$headers = array('Content-Type: application/json');

		if($this->tags) {
			$headers[] = "X-LOGGLY-TAG: {$this->tags}";
		}

		$data = array(
			'level' => $level,
			'message' => $message,
			'timestamp' => time(),
			'context' => $context
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_exec($ch);
		curl_close($ch);
	}
}