#!/usr/bin/php
<?php
	
	include('CrossBar.php');
	include('CouchDB.php');

	global $XBAR, $XBOPTS;
	global $CDB, $CBOPTS;
	
	$REALM = "whistle.dev.cloudpbx.ca";

	
	
	//CouchDB connection
	$CBOPTS['host'] = "127.0.0.1";
	$CBOPTS['port'] = 5984;


	//Cross Bar Connection
	$XBOPTS['host'] = "127.0.0.1";
	$XBOPTS['port'] = 8000;


	switch($XBOPTS['realm']) {
		case 'xpatch.sip.2600hz.com': $XBOPTS['host'] = "apps001-demo-ord.2600hz.com"; break;
		case 'telephonic.cloudpbx.ca': $XBOPTS['port'] = 8001; $CBOPTS['port'] = 5985; break;
		case 'whistle.dev.cloudpbx.ca': $XBOPTS['host'] = "127.0.0.1";$CBOPTS['host'] = "127.0.0.1"; $CBOPTS['port'] = 5984; break;
		case 'atimi.dev.cloudpbx.ca': $XBOPTS['host'] = "127.0.0.1"; break;
	}




	$XBOPTS['usermd5'] = md5("xpatch:2600hz");
	$XBOPTS['realm'] = "whistle.dev.cloudpbx.ca";

        $CBOPTS['user'] = 'b3c90022bad176593311';
        $CBOPTS['pass'] = '4179aafb33fs5de3096c';

        $CBOPTS['user'] = 'patch';
        $CBOPTS['pass'] = 'testing';



	$CREATE_USER = true;
	$CREATE_DEVICE = true;
	$CREATE_VM = true;
	$CREATE_CF = true;



	$USER_TIMEZONE = 'America/Vancouver';
	$UNKNOWN_EMAIL = 'unknown@atimi.com';
	


	$CLIENT_REALM = 'atimi.test4.dev.cloudpbx.ca'; 


	$USERS_NAMES = array();
	$DEVICES_NAMES = array();



        $CDB = new CouchDB($CBOPTS); // See if we can make a connection
	$XBAR = new CrossBar($XBOPTS); // See if we can make a connection

	$ACCOUNTS = $XBAR->get_accounts();


	$XBAR->use_account($ACCOUNTS[$CLIENT_REALM]);

	print_r($XBAR->get_users());


	
	$OWNER_ID = $ACCOUNTS[$CLIENT_REALM];

	if( strlen($OWNER_ID) < 10 ) die("Could not find account:$CLIENT_REALM");

	//echo "OID:".$OWNER_ID."\n"; exit(0);



	if( $argc < 2 ) die("Usage: {$argv[0]} file.csv \r\n");

	$file = file_get_contents($argv[1]);

	$lines = explode("\n",$file);
	$headers = array();

	foreach( $lines as $key => $line ) {
		if( strlen($line) < 10 ) continue; //sanity check	
		$data = explode(",",$line);
		if( count($data) < 92 ) continue;

		if( $key == 0 ) { $headers = $data; continue; }


		$fpd = array();
		foreach( $data as $key => $val ) $fpd[$headers[$key]] = $val;

		//print_r($fpd);
		$DEVICE_TEMPLATE = array();
		$USER_TEMPLATE = array();
		$VMB_TEMPLATE = array();


				


		$fpd['firstname'] = $fpd['name'];
		$fpd['lastname'] = "";
		if( count( explode(" ",$fpd['name']) ) >= 2 ) {
	
			if( stristr($fpd['name'],"Soft Phone") ) {
				$DEVICE_TEMPLATE['device_type'] = 'softphone';
			} else {
				$DEVICE_TEMPLATE['device_type'] = 'sip_device';
			}

			$fpd['name'] = str_replace("Soft Phone","",$fpd['name']);

			$fpd['firstname'] = substr( $fpd['name'], 0, strpos($fpd['name']," ") );
			$fpd['lastname'] = substr( $fpd['name'], strpos($fpd['name']," ")+1 );
		} 

		if( strlen($fpd['email']) < 5 ) {
			$fpd['email'] = $UNKNOWN_EMAIL;
		}

		if( $fpd['lastname'] == false ) $fpd['lastname'] = 'Atimi';

			



		$PCID = $fpd['outboundcid'];
		$NCID = "";
		$DID = "";





		if( stristr($PCID,"<") ) {

			$expcid = explode("<",$PCID);
			$DID = str_replace(">","",$expcid[1]); 
			$NCID = substr($expcid[0],0,14);

		}

		//$NAME = $fpd['firstname']." ".$fpd['lastname']." (".$fpd['extension'].")";//OVERRIDE NAME 
		//$NAME = $fpd['firstname']." ".$fpd['lastname'];
		$NAME = trim(strtolower(substr($fpd['firstname'],0,1).$fpd['lastname'])); //$NAME = str_replace(" ","",$NAME);
		//$NAME = $NCID;
		$DEVNAME = $fpd['deviceuser'];
		$VMBNAME = $NAME;

		$USERS_NAMES[$NAME] = true;
		$DEVICES_NAMES[$DEVNAME] = true;


		echo "Processing ext:{$fpd['extension']} did:$DID cid:$NCID f:{$fpd['firstname']} l:{$fpd['lastname']} e:{$fpd['email']}  name:$NAME dev:{$DEVICE_TEMPLATE['device_type']}\n";
		//print_r($fpd); 
		//continue;		




		//$CHECK_USER = $XBAR->get_user_by_name( $fpd['deviceuser'], $OWNER_ID);
		$CHECK_USER = $XBAR->get_user_by_name( $NAME, $OWNER_ID);
		if( strlen($CHECK_USER['id']) ) json_exit("User already exists");
		//print_r($CHECK_USER);	



		$CHECK_DEVICE = $XBAR->get_device_by_name($DEVNAME, $OWNER_ID);
		if( strlen($CHECK_DEVICE['id']) ) { json_exit("Device already exists"); continue; }

		$CHECK_VMB = $XBAR->get_vmbox_by_name($VMBNAME, $OWNER_ID);
		if( strlen($CHECK_VMB['id']) ) json_exit("VMB already exists");

		$CHECK_CF = $XBAR->get_callflow_id_map($OWNER_ID);

		$json_packet['cfmap'] = array_keys($CHECK_CF);
		$json_packet['callflow_existing'] = $XBAR->get_callflow($CHECK_CF[$fpd['extension']]);
		if( in_array(intval($fpd['extension']),array_keys($CHECK_CF)) ) json_exit("Callflow already exists");





		$USER_TEMPLATE['username'] = $NAME;
		$USER_TEMPLATE['first_name'] = $fpd['firstname'];
		$USER_TEMPLATE['last_name'] = $fpd['lastname'];
		$USER_TEMPLATE['priv_level'] = 'user';
		$USER_TEMPLATE['email'] = $fpd['email'];
		$USER_TEMPLATE['password'] = substr(sha1(time().$fpd['vmpwd'].$fpd['firstname'].rand()),0,10);
		$USER_TEMPLATE['verified'] = false;
		$USER_TEMPLATE['enabled'] = true;
		$USER_TEMPLATE['timezone'] = $USER_TIMEZONE;
		$USER_TEMPLATE['caller_id']['internal'] = array( 'number' => $fpd['extension'], 'name' => $NCID );
		$USER_TEMPLATE['caller_id']['external'] = array( 'number' => $DID, 'name' => $NCID );
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
			'bypass_media' => 'false'
		);

		$DEVICE_TEMPLATE['media']['audio']['codecs'][] = "PCMA";
		$DEVICE_TEMPLATE['media']['audio']['codecs'][] = "PCMU";

		$DEVICE_TEMPLATE['sip'] = array(
			//'custom_sip_headers' => '',
			'method' => 'password', //unsure what this is
			'invite_format' => 'username',
			'username' => $fpd['deviceuser'],
			'password' => substr(sha1(time().$fpd['vmpwd'].$fpd['firstname'].rand()),0,10),
			//'password' => $fpb['devinfo_secret'],
			'expire_seconds' => 300,
			'registration_expiration' => 360 
		);


		$VMB_TEMPLATE['name'] = $VMBNAME;
		$VMB_TEMPLATE['mailbox'] = $fpd['extension'];
		$VMB_TEMPLATE['pin'] = $fpb['vmpwd'];
		$VMB_TEMPLATE['timezone'] = $USER_TIMEZONE;
		$VMB_TEMPLATE['check_if_owner'] = true;
		$VMB_TEMPLATE['require_pin'] = true;
		$VMB_TEMPLATE['skip_greeting'] = false;
		$VMB_TEMPLATE['skip_instructions'] = false;
		$VMB_TEMPLATE['is_setup'] = false;


		//NOCREATE
		$USER_ID = null;
		if( !isset($CHECK_USER['id']) ) {
			if( $CREATE_USER ) $USER_RESPONSE = $XBAR->put_user( $USER_TEMPLATE, $OWNER_ID );
			sleep(2);
			$USER_CREATED = $XBAR->get_user_by_name( $NAME, $OWNER_ID);
			$USER_ID = $USER_CREATED['id'];
		} else {
			$USER_ID = $CHECK_USER['id'];	
		}

		$json_packet['user_created'] = $USER_CREATED;
		$json_packet['user_reply'] = $USER_RESPONSE;

		if( !strlen($USER_ID) ) {
			print_r($USER_RESPONSE);
			print_r($USER_CREATED);
			//die('Could not find the user to create');
		}
		$DEVICE_TEMPLATE['owner_id'] = $USER_ID;





		if( $CREATE_DEVICE ) $DEVICE_RESPONSE = $XBAR->put_device($DEVICE_TEMPLATE, $OWNER_ID );




		$vmb_flow = array();
		$user_flow = array();

		if( $fpb['vm'] == 'enabled' ) {
			$VMB_TEMPLATE['owner_id'] = $USER_ID;
			if( $CREATE_VM ) $VMB_RESPONSE = $XBAR->put_vmbox($VMB_TEMPLATE, $OWNER_ID);
			sleep(2);
			$VMB_CREATED = $XBAR->get_vmbox_by_name($fpd['extension'], $OWNER_ID);

			$vmb_flow['data'] = array( 'id'=> $VMB_CREATED['id']);
			$vmb_flow['module'] = 'voicemail';
			$vmb_flow['children'] = array();



		}

		$DEVICE_CREATED = $XBAR->get_device_by_name($fpd['extension'], $OWNER_ID);


		$json_packet['dev_reply'] = $DEVICE_RESPONSE;
		$json_packet['vmb_reply'] = $VMB_RESPONSE;
		$json_packet['dev_created'] = $DEVICE_CREATED;
		$json_packet['vmb_created'] = $VMB_CREATED;

		//$device_flow['data'] =  array( 'id' => $DEVICE_CREATED['id'], 'timeout' => 20 );
		//$device_flow['module'] = 'device';
		//$device_flow['children']['_'] = $vmb_flow;



		$user_flow['data'] = array( 'id' => $USER_ID );
		$user_flow['module'] = 'user';
		if( count($vmb_flow) > 0 ) {
			$user_flow['children']['_'] = $vmb_flow;
		} else {
			$user_flow['children']['_'] = array();
		}

		$CF_TEMPLATE = array();

		$CF_TEMPLATE['numbers'][] = $fpd['extension'];
		if( strlen($DID) ) $CF_TEMPLATE['numbers'][] = '+'.$DID;
	
		//$CF_TEMPLATE['flow']= $device_flow;
		$CF_TEMPLATE['flow']= $user_flow;


		/*
		$EXT_CF_TEMPLATE = array();
		if( strlen( $_REQUEST['external_did'] ) ) {
			$EXT_CF_TEMPLATE['numbers'][] = '+'.$_REQUEST['external_did'];
			$EXT_CF_TEMPLATE['flow']= $user_flow;
			$EXT_CF_RESPONSE = $XBAR->put_callflow($EXT_CF_TEMPLATE,$OWNER_ID);
			$json_packet['ext_callflow_reply'] = $EXT_CF_RESPONSE;
		}
		*/

		//print_r($CF_TEMPLATE);


		if( $CREATE_CF ) $CF_RESPONSE = $XBAR->put_callflow($CF_TEMPLATE,$OWNER_ID);
		$json_packet['callflow_reply'] = $CF_RESPONSE;
		
		//IF THE ABOVE FAILS ATTEMPT TO ADD THE CALLFLOW WITH JUST THE EXTENSION
		
		if( $CF_RESPONSE['status'] == 'error' && count($CF_TEMPLATE['numbers']) > 1 ) {
			echo "ATTEMPTED TO ADD:\n";
			print_r($CF_TEMPLATE['numbers']);	
			array_pop($CF_TEMPLATE['numbers']); 
			echo "NOW TRYING TO ADD:\n";
			print_r($CF_TEMPLATE['numbers']);	
			if( $CREATE_CF ) $CF_RESPONSE = $XBAR->put_callflow($CF_TEMPLATE,$OWNER_ID);
			if( $CF_RESPONSE['status'] == 'success' ) echo "Yup that was it\n";
			$json_packet['callflow_reply'] = $CF_RESPONSE;
		}

		file_put_contents("jlog.dat", serialize($json_packet), FILE_APPEND);



	}









function json_exit($txt) { echo "$txt\n"; }


?>
