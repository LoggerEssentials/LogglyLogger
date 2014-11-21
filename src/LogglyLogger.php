<?php
namespace Logger;

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
	 * @var int
	 */
	private $jsonOptions = 0;

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

		if(defined('JSON_PRETTY_PRINT')) {
			$this->jsonOptions |= (int) constant('JSON_PRETTY_PRINT');
		}
		if(defined('JSON_UNESCAPED_UNICODE')) {
			$this->jsonOptions |= (int) constant('JSON_UNESCAPED_UNICODE');
		}
		if(defined('JSON_UNESCAPED_SLASHES')) {
			$this->jsonOptions |= (int) constant('JSON_UNESCAPED_SLASHES');
		}
		if(defined('JSON_BIGINT_AS_STRING')) {
			$this->jsonOptions |= (int) constant('JSON_BIGINT_AS_STRING');
		}
		if(defined('JSON_FORCE_OBJECT')) {
			$this->jsonOptions |= (int) constant('JSON_FORCE_OBJECT');
		}
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

		$context = $this->unpackException($context);

		$data = array(
			'level' => $level,
			'message' => $message,
			'timestamp' => time(),
			'datetime' => date('c'),
			'context' => $context
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, $this->jsonOptions));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * @param array $context
	 * @return array
	 */
	private function unpackException($context) {
		if(!array_key_exists('exception', $context)) {
			return $context;
		}
		$exception = $context['exception'];
		if(!$exception instanceof \Exception) {
			return $context;
		}
		$context['exception'] = array(
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTrace(),
		);
		return $context;
	}
}
