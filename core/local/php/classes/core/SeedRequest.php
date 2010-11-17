<?php
/**
 * Handle incoming requests, pass to the appropriate plant, return response
 *
 * @package seed.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2010, CASH Music
 * Licensed under the Affero General Public License version 3.
 * See http://www.gnu.org/licenses/agpl-3.0.html
 *
 */class SeedRequest {
	protected $request=false,$request_method,$plant_array=array(),$plant;
	public $response;
	
	/**
	 * Sets object parameters, calls detectRequest(), and attempts to initialize
	 * the proper Plant
	 *
	 * @param {boolean} $direct_request [default: false] - can only be set when
	 *        called directly, so set to true to indicate direct request method
	 */public function __construct($direct_request=false) {
		if ($direct_request) {
			// skip detect on direct requests
			$this->request = $direct_request;
			$this->request_method = 'direct';
		} else {
			$this->detectRequest();
		}
		if ($this->request) {
			// found something, let's make sure it's legit and do work
			$requested_plant = strtolower(trim($this->request['seed_request_type']));
			unset($this->request['seed_request_type']);
			if ($requested_plant != '' && count($this->request) > 0) {
				$this->buildPlantArray();
				if (isset($this->plant_array[$requested_plant])) {
					$file_path = SEED_ROOT.'/classes/plants/'.$this->plant_array[$requested_plant];
					$class_name = substr_replace($this->plant_array[$requested_plant], '', -4);
					require_once($file_path);
					$this->plant = new $class_name($this->request_method,$this->request);
					$this->response = $this->plant->processRequest();
				}
			}
		}
	}
	
	/**
	 * Determines the method used to make the Seed request, setting $this->request
	 * and $this->request_method
	 *
	 * @return void
	 */protected function detectRequest() {
		if (!$this->request) {
			// determine correct request source
			if (isset($_POST['seed_request_type'])) {
				$this->request = $_POST;
				$this->request_method = 'post';
			} else if (isset($_GET['seed_request_type'])) {
				$this->request = $_GET;
				$this->request_method = 'get';
			} else if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
				$this->request = $_SERVER['argv'];
				$this->request_method = 'commandline';
			}
		}
	}
	
	/**
	 * Builds an associative array of all Plant class files in /classes/plants/
	 * stored as $this->plant_array and used to initialize the appropriate class 
	 * based on the seed_request_type
	 *
	 * @return void
	 */protected function buildPlantArray() {
		if ($plant_dir = opendir(SEED_ROOT.'/classes/plants/')) {
			while (false !== ($file = readdir($plant_dir))) {
				if (substr($file,0,1) != "." && !is_dir($file)) {
					$tmpKey = strtolower(substr_replace($file, '', -9));
					$this->plant_array["$tmpKey"] = $file;
				}
			}
			closedir($plant_dir);
		}
	}
} // END class 
?>