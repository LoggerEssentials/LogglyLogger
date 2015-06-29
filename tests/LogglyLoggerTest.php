<?php
namespace Logger;

class LogglyLoggerTest extends \PHPUnit_Framework_TestCase {
	public function testAutoloader() {
		$this->assertTrue(class_exists('\\Logger\\LogglyLogger', true));
	}
}
