<?php

	include("../vortex/include/config.php");
	include('CrossBar.php');
	include('CouchDB.php');
	
	function json_exit($string) {
		echo json_encode(array(
			'success' => true,
			'error' => $string
		));
	}
	
	class Importer {
		
		private $host = null;
		private $port = null;
		private $token = null;
		private $accountName = null;
		private $realm = null;
		private $accountId = null;
		private $userTimezone = 'America/Vancouver';
		private $unknownEmail = 'unknown@unknown.com';
		private $create = array(
			'user', 'device', 'vm', 'cf'
		);
		
		public $xbar = null;
		
		public function __construct($config) {
			foreach($config as $key => $value) {
				$this->$key = $value;
			}
			
			$this->xbar = new CrossBar(array(
				'host' => $this->host,
				'port' => $this->port,
				'usermd5' => $this->token,
				'account_name' => $this->accountName,
				'realm' => $this->realm
			));
			
			if( strlen($this->accountId) < 10 ) die("Could not find account:$this->realm");
			$this->xbar->use_account($this->accountId);
		}
		
		public function processUpload() {
			if(!file_exists($_FILES['extensionList']['tmp_name'])) {
				echo json_encode(array(
					'success' => true,
					'error' => 'No file was uploaded'
				));
				exit;
			}
		
			$file = file_get_contents($_FILES['extensionList']['tmp_name']);
			
			// Check if standard UNIX LF used.
			$lines = explode("\n", $file);
			if(count($lines) <= 1) {
				// Must be using Mac CR
				$lines = explode("\r", $file);
			}
			
			$this->loopLines($lines);
		}
		
		private function loopLines($lines) {
			$cols = null;
			
			foreach($lines as $key => $line) {
				if($key == 0) {
					$cols = $this->getColumns($line);
					continue;
				}
				
				$data = $this->getColumns($line);
				
				// Quit if there is a trailing line
				if(count($data) < 2) {
					continue;
				}
				
				$fpd = array();
				foreach($data as $key => $value) {
					$fpd[$cols[$key]] = $value;
				}
					
				$DEVICE_TEMPLATE = array();
				$USER_TEMPLATE = array();
				$VMB_TEMPLATE = array();



				$fpd['firstname'] = $fpd['name'];
				$fpd['lastname'] = "";
				if( count( explode(" ",$fpd['name']) ) >= 2 ) {

					if(stristr($fpd['name'],"Soft Phone") || $fpd['devicetype'] == 'softphone') {
						$DEVICE_TEMPLATE['device_type'] = 'softphone';
						$fpd['name'] = str_replace("Soft Phone", "", $fpd['name']);
					}
					else {
						$DEVICE_TEMPLATE['device_type'] = 'sip_device';
					}

					$fpd['firstname'] = substr( $fpd['name'], 0, strpos($fpd['name']," ") );
					$fpd['lastname'] = substr( $fpd['name'], strpos($fpd['name']," ")+1 );
				} 

				if( strlen($fpd['email']) < 5 ) {
					$fpd['email'] = $this->unknownEmail;
				}

				if( $fpd['lastname'] == false ) $fpd['lastname'] = ' ';

		



				$PCID = $fpd['outboundcid'];
				$NCID = "";
				$DID = "";





				if( stristr($PCID,"<") ) {

					$expcid = explode("<",$PCID);
					$DID = str_replace(">","",$expcid[1]); 
					$NCID = substr($expcid[0],0,14);

				}
	
				$NAME = $fpd['extension'];
	
				//$NAME = $NCID;
				$DEVNAME = $NAME;//$fpd['deviceuser'];
				$VMBNAME = $NAME;
	
				$pass = $fpd['vmpwd'];
				if(strlen($pass) < 4) {
					$pass = '1234';
				}

				$USERS_NAMES[$NAME] = true;
				$DEVICES_NAMES[$DEVNAME] = true;


				//echo "Processing ext:{$fpd['extension']} did:$DID cid:$NCID f:{$fpd['firstname']} l:{$fpd['lastname']} e:{$fpd['email']}  name:$NAME dev:{$DEVICE_TEMPLATE['device_type']}\n";
				//print_r($fpd); 
				//continue;		




				$CHECK_USER = $this->xbar->get_user_by_name($NAME, $this->accountId);
				if( strlen($CHECK_USER['id'])) { json_exit("User $NAME already exists"); exit; }

				$CHECK_DEVICE = $this->xbar->get_device_by_name($DEVNAME, $this->accountId);
				if( strlen($CHECK_DEVICE['id'])) { json_exit("Device $DEVNAME already exists"); exit; }

				$CHECK_VMB = $this->xbar->get_vmbox_by_name($VMBNAME, $this->accountId);
				if( strlen($CHECK_VMB['id'])) { json_exit("VMB $VMBNAME already exists"); exit; }





				$CHECK_CF = $this->xbar->get_callflow_id_map($this->accountId);

				$json_packet['cfmap'] = array_keys($CHECK_CF);
				$json_packet['callflow_existing'] = isset($CHECK_CF[$fpd['extension']]) ?
					$this->xbar->get_callflow($CHECK_CF[$fpd['extension']]) :
					null;
		
				// Remove the call flow if it already exists
				if( in_array(intval($fpd['extension']),array_keys($CHECK_CF)) ) {//json_exit("Callflow already exists");
					$this->xbar->del_callflow($CHECK_CF[$fpd['extension']], $this->accountId);
				}

				$USER_TEMPLATE['username'] = $NAME;
				$USER_TEMPLATE['first_name'] = $fpd['firstname'];
				$USER_TEMPLATE['last_name'] = $fpd['lastname'];
				$USER_TEMPLATE['priv_level'] = 'user';
				$USER_TEMPLATE['email'] = $fpd['email'];
				//$USER_TEMPLATE['password'] = substr(sha1(time().$fpd['vmpwd'].$fpd['firstname'].rand()),0,10);
				$USER_TEMPLATE['password'] = $pass;
				$USER_TEMPLATE['verified'] = false;
				$USER_TEMPLATE['enabled'] = true;
				$USER_TEMPLATE['timezone'] = $this->userTimezone;
				/*$USER_TEMPLATE['caller_id']['internal'] = array( 'number' => $fpd['extension'], 'name' => $NCID );
				$USER_TEMPLATE['caller_id']['external'] = array( 'number' => $DID, 'name' => $NCID );*/
				$USER_TEMPLATE['call_forward'] = array( 
					'enabled' => false, 
					'number' => '',
					'require_keypress' => false,
					'keep_caller_id' => true,
					'substitute' => false,
					'direct_calls_only' => false 
				);

				$DEVICE_TEMPLATE['name'] = $DEVNAME;
				$DEVICE_TEMPLATE['enabled'] = true;


				/* TODO FIGURE OUT WHAT THIS IS */
				$DEVICE_TEMPLATE['provision'] = '';
				$DEVICE_TEMPLATE['suppress_unregister_notifications'] = false;

				/*
				$DEVICE_TEMPLATE['music_on_hold'] = array();
				$DEVICE_TEMPLATE['ringtones'] = array();
				$DEVICE_TEMPLATE['caller_id_options'] = array();
				*/


				$DEVICE_TEMPLATE['call_forward'] = array( 
					'enabled' => false, 
					'number' => '',
					'require_keypress' => false,
					'keep_caller_id' => true,
					'substitute' => false,
					'ignore_early_media' => true, 
					'direct_calls_only' => false 
				);
	



				//$DEVICE_TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['extension_number'], 'name' => substr($_REQUEST['first_name'],0,1)." ".substr($_REQUEST['last_name'],0,11) );
				//$DEVICE_TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_did'], 'name' => substr($_REQUEST['first_name'],0,1)." ".substr($_REQUEST['last_name'],0,11) );
				//$DEVICE_TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['extension_number'], 'name' => '' );
				//$DEVICE_TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_did'], 'name' => '' );


				$DEVICE_TEMPLATE['media'] = array(
					'fax' => array( 'option' => 'auto' ),
					'bypass_media' => ($kazooVersion == 'old' ? 'false' : false)
				);

				$DEVICE_TEMPLATE['media']['audio']['codecs'][] = "PCMA";
				$DEVICE_TEMPLATE['media']['audio']['codecs'][] = "PCMU";

				$DEVICE_TEMPLATE['sip'] = array(
					//'custom_sip_headers' => '',
					'method' => 'password', //unsure what this is
					'invite_format' => 'username',
					'username' => $NAME,
					'password' => substr(sha1(time().$pass.$fpd['firstname'].rand()),0,16),
					'expire_seconds' => 300,
					'registration_expiration' => 360 
				);


				$VMB_TEMPLATE['name'] = $VMBNAME;
				$VMB_TEMPLATE['mailbox'] = $fpd['extension'];
				$VMB_TEMPLATE['pin'] = $pass;
				$VMB_TEMPLATE['timezone'] = $this->userTimezone;
				$VMB_TEMPLATE['check_if_owner'] = true;
				$VMB_TEMPLATE['require_pin'] = true;
				$VMB_TEMPLATE['skip_greeting'] = false;
				$VMB_TEMPLATE['skip_instructions'] = false;
				$VMB_TEMPLATE['is_setup'] = false;


				//NOCREATE
				$USER_ID = null;
				if( !isset($CHECK_USER['id']) ) {
					if(in_array('user', $this->create)) $USER_RESPONSE = $this->xbar->put_user( $USER_TEMPLATE, $this->accountId );
					sleep(2);
					$USER_CREATED = $this->xbar->get_user_by_name( $NAME, $this->accountId);
					$USER_ID = $USER_CREATED['id'];
				} else {
					$USER_ID = $CHECK_USER['id'];	
				}

				$json_packet['user_created'] = isset($USER_CREATED) ? $USER_CREATED : null;
				$json_packet['user_reply'] = isset($USER_RESPONSE) ? $USER_CREATED : null;

				$DEVICE_TEMPLATE['owner_id'] = $USER_ID;





				if(in_array('device', $this->create)) $DEVICE_RESPONSE = $this->xbar->put_device($DEVICE_TEMPLATE, $this->accountId);



				$vmb_flow = array();
				$user_flow = array();

				if( $fpd['vm'] == 'enabled' ) {
					$VMB_TEMPLATE['owner_id'] = $USER_ID;
		
					// Prevent short PINs
					/*if(strlen($VMB_TEMPLATE['pin']) < 4) {
						$VMB_TEMPLATE['pin'] = '1234';
					}*/
		
					if(in_array('vm', $this->create)) $VMB_RESPONSE = $this->xbar->put_vmbox($VMB_TEMPLATE, $this->accountId);
					sleep(2);
		
					$VMB_CREATED = $this->xbar->get_vmbox_by_name($VMB_TEMPLATE['name'], $this->accountId);

					$vmb_flow['data'] = array( 'id'=> $VMB_CREATED['id']);
					$vmb_flow['module'] = 'voicemail';
					$vmb_flow['children'] = new stdClass();



				}

				$DEVICE_CREATED = $this->xbar->get_device_by_name($fpd['extension'], $this->accountId);


				$json_packet['dev_reply'] = $DEVICE_RESPONSE;
				$json_packet['vmb_reply'] = isset($VMB_RESPONSE) ? $VMB_RESPONSE : null;
				$json_packet['dev_created'] = $DEVICE_CREATED;
				$json_packet['vmb_created'] = isset($VMB_CREATED) ? $VMB_RESPONSE : null;

				//$device_flow['data'] =  array( 'id' => $DEVICE_CREATED['id'], 'timeout' => 20 );
				//$device_flow['module'] = 'device';
				//$device_flow['children']['_'] = $vmb_flow;



				$user_flow['data'] = array( 'id' => $USER_ID );
				$user_flow['module'] = 'user';
				if( count($vmb_flow) > 0 ) {
					$user_flow['children']['_'] = $vmb_flow;
				} else {
					$user_flow['children'] = new stdClass();
				}

				$CF_TEMPLATE = array();

				$CF_TEMPLATE['numbers'][] = $fpd['extension'];
				if( strlen($DID) ) $CF_TEMPLATE['numbers'][] = '+'.$DID;

				//$CF_TEMPLATE['flow']= $device_flow;
				$CF_TEMPLATE['flow']= $user_flow;



				//print_r($CF_TEMPLATE);


				if(in_array('cf', $this->create)) $CF_RESPONSE = $this->xbar->put_callflow($CF_TEMPLATE, $this->accountId);
				$json_packet['callflow_reply'] = $CF_RESPONSE;
	
				//IF THE ABOVE FAILS ATTEMPT TO ADD THE CALLFLOW WITH JUST THE EXTENSION
	
				if( $CF_RESPONSE['status'] == 'error' && count($CF_TEMPLATE['numbers']) > 1 ) {
					array_pop($CF_TEMPLATE['numbers']);
					if(in_array('cf', $this->create)) $CF_RESPONSE = $this->xbar->put_callflow($CF_TEMPLATE, $this->accountId);
					//if( $CF_RESPONSE['status'] == 'success' ) echo "Corrected callflow due to duplicate numbers\n";
					$json_packet['callflow_reply'] = $CF_RESPONSE;
				}

				file_put_contents("jlog.dat", serialize($json_packet), FILE_APPEND);
			}
		}
		
		private function getColumns($line) {
			return str_getcsv($line);
		}
	}
	
	$config = array(
		'host' => $XBOPTS['host'],
		'port' => $XBOPTS['port'],
		'token' => $_COOKIE['TOKEN'],
		'accountName' => $_COOKIE['REALM'],
		'realm' => $_COOKIE['SIPREALM'],
		'accountId' => substr($_POST['owner_id'], 8)
	);
	if(!empty($_POST['defaultEmail'])) { $config['unknownEmail'] = $_POST['defaultEmail']; }

	$importer = new Importer($config);
	$importer->processUpload();
	
	echo json_encode(array(
		'success' => true
	));
?>
