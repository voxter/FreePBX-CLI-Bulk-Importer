<?php

class Logger {

	private $debug = true;
	private $log;
	private $appName = 'FreePBXImporter';
	
	function Logger() {
		$this->log = fopen($this->appName.'.log', 'w');
	}
	
	function __destruct() {
		fclose($this->log);
	}
	
	function log($string) {
		fwrite($this->log, $string."\r\n");
	}
}

?>