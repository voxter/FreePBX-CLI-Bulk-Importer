<?php

	require_once("config.php");
	require_once("CrossBar.php");
	require_once("CouchDB.php");
	require_once("CreateRealm.php");
	require_once("xbinit.php");
	require_once("cbinit.php");




	global $_COOKIE,$_REQUEST, $NODE, $json_packet, $TOKEN, $XBAR, $CDB;


	ignore_user_abort(true); //don't die if user clicks stop

	//ob_start(); print_r($_FILES); $XBAR->log(ob_get_clean());
	//ob_start(); print_r($_REQUEST); $XBAR->log(ob_get_clean());




	$PNODE = "";
	$NODE = "";
	$TYPE = "";



	//only passed during create
	$OWNER_TYPE = "";
	$OWNER_ID = "";
	$OWNER_ACCOUNT_ID = "";

	$OP = $_REQUEST['op'];



	if( stristr($_REQUEST['id'],"-") ) {
		$tmp = explode("-", $_REQUEST['id']);	
		$TYPE = trim($tmp[0]);
		$NODE = trim($tmp[1]);
		if( isset($tmp[2]) ) $PNODE = trim($tmp[2]);
	}

	if( stristr($_REQUEST['owner_id'],"-") ) {
		$tmp = explode("-", $_REQUEST['owner_id']);	
		$OWNER_TYPE = trim($tmp[0]);
		$OWNER_ID = trim($tmp[1]);
		if( isset($tmp[2]) ) $OWNER_ACCOUNT_ID = trim($tmp[2]);
	}

	/*
		Note: to move a device between users you need to update the owner_id to the new owner,
		this could be done with drag and drop interface.

	*/

	


	function json_exit($string) {
		global $json_packet;
		$json_packet['success'] = false;
		$json_packet['errormsg'] = $string;
		echo json_encode($json_packet);		
		exit(0);
	}
	
	$TEMPLATE = array();
	$resp = array();
	
	if( $OWNER_TYPE == 'MEDIAFOLDER' || $OWNER_TYPE == 'MEDIA' ) {
		//header("Content-Type: text/plain"); //IE might require...
		header("Content-Type: text/html");
	} else {
		header("Content-Type: application/json");
	}


	if( $OWNER_TYPE == 'MEDIAFOLDER' || $OWNER_TYPE == 'MEDIA' ) {


		$MEDIA_TEMPLATE['data']['name'] = $_REQUEST['name'];
		$MEDIA_TEMPLATE['data']['type'] = 'mp3';
		$MEDIA_TEMPLATE['data']['streamable'] = $_REQUEST['streamable'];
		$MEDIA_TEMPLATE['raw'] = base64_encode(file_get_contents($_FILES['media_file']['tmp_name']));
		$MEDIA_TEMPLATE['type'] = $_FILES['media_file']['type'];

		
		$resp = $XBAR->post_media($MEDIA_TEMPLATE,$OWNER_ID);




		/*
		[id] => MEDIA-594aa29c34115278f23674228c225a12-d0549c9e1cf0ebacae84d2e0311f0509
		[owner_id] => MEDIA-594aa29c34115278f23674228c225a12-d0549c9e1cf0ebacae84d2e0311f0509
		[name] => bwhahahaha11
		[streamable] => true


		[id] => MEDIA-594aa29c34115278f23674228c225a12-d0549c9e1cf0ebacae84d2e0311f0509
		[owner_id] => MEDIAFOLDER-d0549c9e1cf0ebacae84d2e0311f0509
		[name] => bwhahahaha11
		[streamable] => true

		[id] => MEDIAUPLOAD-
		[owner_id] => MEDIAFOLDER-d0549c9e1cf0ebacae84d2e0311f0509
		[name] => fewefwefwef
		[streamable] => true

		*/
		//$XBAR->log("NODE:$NODE PNODE:$PNODE");

		/*
		$account_info = $CDB->send('GET',"/accounts/$OWNER_ID/");

		if( isset($account_info['pvt_account_db']) ) {

			$doc['name'] = $_REQUEST['name'];
			$doc['description'] = 'mp3 upload';
			$doc['source_type'] = 'voicemail';

			$doc['content_type'] = $_FILES['media_file']['type'];
			$doc['media_type'] = 'mp3';
			$doc['streamable'] = $_REQUEST['streamable'];
			$doc['pvt_account_id'] = $OWNER_ID;
			$doc['pvt_account_db'] = $account_info['pvt_account_db'];

			$doc['pvt_created'] = time();
			$doc['pvt_modified'] = time();

			$doc['pvt_type'] = "media";

			$doc['_attachments'][$_FILES['media_file']['name']]['content_type'] = $_FILES['media_file']['type'];
			$doc['_attachments'][$_FILES['media_file']['name']]['data'] = base64_encode(file_get_contents($_FILES['media_file']['tmp_name']));

			if( strlen($NODE) ) {
				$doc['_id'] = $NODE;
				$attachment = $CDB->send('GET',"/{$account_info['pvt_account_db']}/$NODE");
				//ob_start(); print_r($attachment); $XBAR->log(ob_get_clean());
				$doc['_rev'] = $attachment['_rev'];
				$resp = $CDB->send("PUT","/{$account_info['pvt_account_db']}/$NODE", json_encode($doc) );
			} else {
				$resp = $CDB->send("POST","/{$account_info['pvt_account_db']}/", json_encode($doc) );
			}
			ob_start(); print_r($resp); $XBAR->log(ob_get_clean());	

			if( $resp['ok'] == 1 ) {

				$resp['status'] = 'success';
			} else {
				json_exit('Upload failed, please contact admin.');
			}

			//ob_start(); print_r($resop); $XBAR->log(ob_get_clean());	
		} else {
			json_exit('Could not find account db info.');
		}
		*/

	}



	if( $TYPE == "EXT" ) {



		//$NAME = trim($_REQUEST['username']);

		$NAME = $_REQUEST['extension_number'];
		/*
		switch( trim($_REQUEST['prefix']) ) {

			case "firstlast":
				$NAME = $_REQUEST['first_name']." ".$_REQUEST['last_name'];
			break;

			case "justext":
				$NAME = $_REQUEST['extension_number'];
			break;

			case "username":
			default:
				$NAME = $_REQUEST['username'];
			break;
		}
		*/

		
		$CHECK_USER = $XBAR->get_user_by_name( $NAME, $OWNER_ID);
		if( strlen($CHECK_USER['id']) ) json_exit("User already exists");

		$CHECK_DEVICE = $XBAR->get_device_by_name($NAME, $OWNER_ID);
		if( strlen($CHECK_DEVICE['id']) ) json_exit("Device already exists");

		$CHECK_VMB = $XBAR->get_vmbox_by_name($NAME, $OWNER_ID);
		if( strlen($CHECK_VMB['id']) ) json_exit("VMB already exists");

		$CHECK_CF = $XBAR->get_callflow_id_map($OWNER_ID);

		$json_packet['cfmap'] = array_keys($CHECK_CF);
		$json_packet['callflow_existing'] = $XBAR->get_callflow($CHECK_CF[$_REQUEST['extension_number']]);
		$json_packet['grumble'] = "Requested extension {$_REQUEST['extension_number']}";
		if( in_array(intval($_REQUEST['extension_number']),array_keys($CHECK_CF)) ) json_exit("Callflow already exists");


		$USER_TEMPLATE = array();
		$USER_TEMPLATE['username'] = $NAME;
		$USER_TEMPLATE['first_name'] = $_REQUEST['first_name'];
		$USER_TEMPLATE['last_name'] = $_REQUEST['last_name'];
		$USER_TEMPLATE['priv_level'] = 'user';
		$USER_TEMPLATE['email'] = $_REQUEST['email'];
		$USER_TEMPLATE['password'] = $_REQUEST['extension_pin'];
		$USER_TEMPLATE['verified'] = false;
		$USER_TEMPLATE['enabled'] = true;
		$USER_TEMPLATE['timezone'] = $_REQUEST['timezone'];
		$USER_TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['extension_number'], 'name' => '' );
		$USER_TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_did'], 'name' => '' );
		$USER_TEMPLATE['call_forward'] = array( 
			'enabled' => false, 
			'number' => '',
			'require_keypress' => false,
			'keep_caller_id' => true,
			'substitute' => false,
			'direct_calls_only' => false 
		);
		




	


		$DEVICE_TEMPLATE = array();
		$DEVICE_TEMPLATE['name'] = $NAME;
		$DEVICE_TEMPLATE['device_type'] = 'sip_device';
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
		



		$DEVICE_TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['extension_number'], 'name' => substr($_REQUEST['first_name'],0,1)." ".substr($_REQUEST['last_name'],0,11) );
		$DEVICE_TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_did'], 'name' => substr($_REQUEST['first_name'],0,1)." ".substr($_REQUEST['last_name'],0,11) );
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
			'username' => $NAME,
			'password' => substr(sha1(time().$_REQUEST['extension_pin'].rand()),0,10),
			'expire_seconds' => 300,
			'registration_expiration' => 360 
		);





		$VMB_TEMPLATE = array();
		$VMB_TEMPLATE['name'] = $NAME;
		$VMB_TEMPLATE['mailbox'] = $_REQUEST['extension_number'];
		$VMB_TEMPLATE['pin'] = $_REQUEST['extension_pin'];
		$VMB_TEMPLATE['timezone'] = $_REQUEST['timezone'];
		$VMB_TEMPLATE['check_if_owner'] = true;
		$VMB_TEMPLATE['require_pin'] = true;
		$VMB_TEMPLATE['skip_greeting'] = false;
		$VMB_TEMPLATE['skip_instructions'] = false;
		$VMB_TEMPLATE['is_setup'] = false;
	

		$USER_RESPONSE = $XBAR->put_user( $USER_TEMPLATE, $OWNER_ID );
		sleep(2);
		$USER_CREATED = $XBAR->get_user_by_name( $NAME, $OWNER_ID);
		$USER_ID = $USER_CREATED['id'];

		$json_packet['user_created'] = $USER_CREATED;
		$json_packet['user_reply'] = $USER_RESPONSE;

		/*
		$USER_ID = $USER_RESPONSE['data']['id'];
		if( strlen($USER_ID) == 0 ) {
			$json_packet['user_created'] = $USER_CREATED;
		}	
		*/


		//$USER_ID = $USER_CREATED['data'][0]['id'];
		if( strlen($USER_ID) ) {
			$DEVICE_TEMPLATE['owner_id'] = $USER_ID;
			$DEVICE_RESPONSE = $XBAR->put_device($DEVICE_TEMPLATE, $OWNER_ID );

			$VMB_TEMPLATE['owner_id'] = $USER_ID;
			$VMB_RESPONSE = $XBAR->put_vmbox($VMB_TEMPLATE, $OWNER_ID);

			sleep(2);
			$DEVICE_CREATED = $XBAR->get_device_by_name($NAME, $OWNER_ID);
			$VMB_CREATED = $XBAR->get_vmbox_by_name($NAME, $OWNER_ID);
			



			$json_packet['dev_reply'] = $DEVICE_RESPONSE;
			$json_packet['vmb_reply'] = $VMB_RESPONSE;
			$json_packet['dev_created'] = $DEVICE_CREATED;
			$json_packet['vmb_created'] = $VMB_CREATED;
				


			if( strlen($VMB_CREATED['id']) && strlen($DEVICE_CREATED['id']) ) {

				$vmb_flow['data'] = array( 'id'=> $VMB_CREATED['id']);
				$vmb_flow['module'] = 'voicemail';
				$vmb_flow['children'] = array();

				//$device_flow['data'] =  array( 'id' => $DEVICE_CREATED['id'], 'timeout' => 20 );
				//$device_flow['module'] = 'device';
				//$device_flow['children']['_'] = $vmb_flow;

				$user_flow['data'] = array( 'id' => $USER_ID );
				$user_flow['module'] = 'user';
				$user_flow['children']['_'] = $vmb_flow;



				$CF_TEMPLATE['numbers'][] = $_REQUEST['extension_number'];
				//$CF_TEMPLATE['flow']= $device_flow;
				$CF_TEMPLATE['flow']= $user_flow;

				$EXT_CF_TEMPLATE = array();

				if( strlen( $_REQUEST['external_did'] ) ) {
				
					$EXT_CF_TEMPLATE['numbers'][] = '+'.$_REQUEST['external_did'];
					$EXT_CF_TEMPLATE['flow']= $user_flow;
					$EXT_CF_RESPONSE = $XBAR->put_callflow($EXT_CF_TEMPLATE,$OWNER_ID);
					$json_packet['ext_callflow_reply'] = $EXT_CF_RESPONSE;
					
				}



				$CF_RESPONSE = $XBAR->put_callflow($CF_TEMPLATE,$OWNER_ID);
				$json_packet['callflow_reply'] = $CF_RESPONSE;
				if( $CF_RESPONSE['success'] == true ) {
					$json_packet['success'] = true;	
				}




				/*
				$vmb_flow['data'] = array( 'id'=> $VMB_CREATED['id']);
				$vmb_flow['module'] = 'voicemail';
				$vmb_flow['children'] = array();

				$device_flow['data'] =  array( 'id' => $DEVICE_CREATED['id'], 'timeout' => 20 );
				$device_flow['module'] = 'device';
				$device_flow['children']['_'] = $vmb_flow;


				$CF_TEMPLATE['numbers'][] = $_REQUEST['extension_number'];
				$CF_TEMPLATE['flow']= $device_flow;

				$CF_RESPONSE = $XBAR->put_callflow($CF_TEMPLATE,$OWNER_ID);
				$json_packet['callflow_reply'] = $CF_RESPONSE;
				if( $CF_RESPONSE['success'] == true ) {
					$json_packet['success'] = true;	
				}
				*/
				
			} else {

				$json_packet['success'] = false;
				$json_packet['errormsg'] = "Device/VMB not created";
				echo json_encode($json_packet);
				$XBAR->log(json_encode($json_packet));
				exit(0);
			}



		} else {
				
			$json_packet['success'] = false;
			$json_packet['errormsg'] = "User creation problem.";
			$json_packet['user_reply'] = $USER_RESPONSE;
			echo json_encode($json_packet);
			exit(0);
		}


		
			$json_packet['success'] = true;
			echo json_encode($json_packet);
			exit(0);





	}



	if( $TYPE == "VMB" ) {

		$TEMPLATE = array();
		$TEMPLATE['name'] = $_REQUEST['name'];
		$TEMPLATE['mailbox'] = $_REQUEST['mailbox'];
		$TEMPLATE['pin'] = $_REQUEST['pin'];
		//$TEMPLATE['timezone'] = $_REQUEST['timezone'];
		$TEMPLATE['check_if_owner'] = $_REQUEST['check_if_owner'];
		$TEMPLATE['require_pin'] = $_REQUEST['require_pin'];
		$TEMPLATE['skip_greeting'] = $_REQUEST['skip_greeting'];
		$TEMPLATE['skip_instructions'] = $_REQUEST['skip_instructions'];
		$TEMPLATE['is_setup'] = $_REQUEST['is_setup'];
		


		if( strlen($NODE) ) {


			switch( $OP ) {
				case 'del': //Delete 
					$resp = $XBAR->del_vmbox($NODE, $PNODE);
				break;

				case 'update': //Update
				default:
					$data = $XBAR->get_vmbox($NODE, $PNODE);
					$TEMPLATE = array_merge($data, $TEMPLATE);
					unset($TEMPLATE['notifications']);
					unset($TEMPLATE['messages']);
					unset($TEMPLATE['media']);
					unset($TEMPLATE['vm_to_email']); 
					$resp = $XBAR->post_vmbox($TEMPLATE, $NODE, $PNODE);
			}


		} else { //Create
			//$XBAR->log("Creating Voicemail Box {$TEMPLATE['name']} $OWNER_ID");
			if( strlen($OWNER_ID) && strlen($OWNER_ACCOUNT_ID) ) {
				$TEMPLATE['owner_id'] = $OWNER_ID;
				//$resp = $XBAR->put_device($TEMPLATE, $OWNER_ACCOUNT_ID );
				$resp = $XBAR->put_vmbox($TEMPLATE, $OWNER_ACCOUNT_ID);
			} else {
				$resp = $XBAR->put_vmbox($TEMPLATE);
			}



		}




	}




	if( $TYPE == "DEVICE" ) {

		$TEMPLATE = array();
		$TEMPLATE['name'] = $_REQUEST['name'];
		$TEMPLATE['device_type'] = $_REQUEST['device_type'];
		$TEMPLATE['enabled'] = $_REQUEST['enabled'];
		//$TEMPLATE['owner_id'] = 'a46e2a788d41b98d275cb3da1a640676';
		//$TEMPLATE['owner_id'] = 'a46e2a788d41b98d275cb3da1a0fd21e';


		/* TODO FIGURE OUT WHAT THIS IS */
		$TEMPLATE['provision'] = '';
		$TEMPLATE['suppress_unregister_notifications'] = false;

		/*
		$TEMPLATE['music_on_hold'] = array();
		$TEMPLATE['ringtones'] = array();
		$TEMPLATE['caller_id_options'] = array();
		*/


		$TEMPLATE['call_forward'] = array( 
			'enabled' => $_REQUEST['cfwd_enabled'], 
			'number' => $_REQUEST['cfwd_number'],
			'require_keypress' => $_REQUEST['cfwd_require_keypress'],
			'keep_caller_id' => $_REQUEST['cfwd_keep_caller_id'],
			'substitute' => $_REQUEST['cfwd_substitute'],
			'mac_address' => $_REQUEST['mac_address'], 
			'ignore_early_media' => $_REQUEST['cfwd_ignore_early_media'], 
			'direct_calls_only' => $_REQUEST['cfwd_direct_calls_only']
		);
		
		$TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['internal_cid_num'], 'name' => $_REQUEST['internal_cid_name'] );
		$TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_cid_num'], 'name' => $_REQUEST['external_cid_name'] );


		$TEMPLATE['media'] = array(
			'fax' => array( 'option' => $_REQUEST['media_fax_option'] ),
			//NOT CURRENTLY USED
			//'ignore_early_media' => $_REQUEST['ignore_early_media'],
			//'bypass_media' => $_REQUEST['bypass_media']
			'bypass_media' => 'false'
		);

		if( $_REQUEST['H261'] == 'true' ) $TEMPLATE['media']['video']['codecs'][] = "H261";
		if( $_REQUEST['H263'] == 'true' ) $TEMPLATE['media']['video']['codecs'][] = "H263";
		if( $_REQUEST['H264'] == 'true' ) $TEMPLATE['media']['video']['codecs'][] = "H264";


		if( $_REQUEST['G729'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "G729";
		if( $_REQUEST['PCMA'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "PCMA";
		if( $_REQUEST['PCMU'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "PCMU";
		if( $_REQUEST['G722_16'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "G722_16";
		if( $_REQUEST['G722_32'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "G722_32";
		if( $_REQUEST['CELT_48'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "CELT_48";
		if( $_REQUEST['CELT_64'] == 'true' ) $TEMPLATE['media']['audio']['codecs'][] = "CELT_64";



		$TEMPLATE['sip'] = array(
			//'custom_sip_headers' => '',
			'method' => 'password', //unsure what this is
			'invite_format' => $_REQUEST['invite_format'],
			'username' => $_REQUEST['username'],
			'password' => $_REQUEST['password'],
			'expire_seconds' => $_REQUEST['expire_seconds'],
			'registration_expiration' => $_REQUEST['registration_expiration']
		);



		if( strlen($NODE) ) {


			switch( $OP ) {
				case 'del': //Delete 
					$resp = $XBAR->del_device($NODE, $PNODE);
				break;



				case 'update': //Update
				default:
					$XBAR->log("RETRIEVING DEVICE INFO!!!!!!!!!!!!!!!!!!!!! FOR UPDATE $NODE $PNODE");
					$data = $XBAR->get_device($NODE,$PNODE);

					ob_start();
					print_r($data);
					$XBAR->log(ob_get_clean());

					$TEMPLATE = array_merge($data, $TEMPLATE);
					if( count($TEMPLATE['ringtones']) == 0 ) unset($TEMPLATE['ringtones']);
					if( count($TEMPLATE['caller_id_options']) == 0 ) unset($TEMPLATE['caller_id_options']);
					unset($TEMPLATE['music_on_hold']);
					//Don't send if this is orphaned
					if( strlen($TEMPLATE['owner_id']) ) {
						$resp = $XBAR->post_device($TEMPLATE, $NODE, $PNODE);
					} else {
						$resp = "FAIL BOT IS FAIL";
					}
					
			}


		} else { //Create
			if( strlen($OWNER_ID) && strlen($OWNER_ACCOUNT_ID) ) {
				$TEMPLATE['owner_id'] = $OWNER_ID;
				$resp = $XBAR->put_device($TEMPLATE, $OWNER_ACCOUNT_ID );
			} else {
				$resp = $XBAR->put_device($TEMPLATE);
			}

		}




	}





	if( $TYPE == "ACCOUNT" ) {

		$TEMPLATE = array();
		$TEMPLATE['name'] = $_REQUEST['name'];
		$TEMPLATE['realm'] = trim($_REQUEST['name']).".".trim($_REQUEST['zone']);


		/*
		$TEMPLATE['caller_id']['default'] = array( 'number' => $_REQUEST['caller_id'], 'name' => $_REQUEST['name'] );
		$TEMPLATE['caller_id']['internal'] = array( 'name' => $_REQUEST['name'].' Internal' );
		$TEMPLATE['caller_id']['emergency'] = array( 'number' => $_REQUEST['caller_id'], 'name' => $_REQUEST['name'].'Emerg' );
		*/



		//$TEMPLATE['trunks'] = $_REQUEST['trunks'];
		//$TEMPLATE['inbound_trunks'] = $_REQUEST['inbound_trunks'];

		if( strlen($NODE) ) {


			switch( $OP ) {
				case 'del': //Delete 
					$resp = $XBAR->del_account($NODE);
				break;



				case 'update': //Update
				default:
					$data = $XBAR->get_account($NODE);
					$TEMPLATE = array_merge($data, $TEMPLATE);
					
					$resp = $XBAR->post_account($TEMPLATE, $NODE);
			}


		} else { //Create

			if( strlen($OWNER_ID) ) $TEMPLATE['owner_id'] = $OWNER_ID;

			$XBAR->log('Creating an account? maybe? if it doesnt time out.');
			$st = microtime();
			$resp = $XBAR->put_account($TEMPLATE, $NODE);
			$et = microtime();
			$XBAR->log('Creating took '.($et-$st).' units of time measurement');	

			if( $resp['status'] == 'success' ) {
				$XBAR->log('Entering realm creation.');
				/*
				$zone_ip_map = array(
					'cloudpbx.ca' => '216.146.39.125', //Dynect
					'cloudpbx.ie' => '216.146.39.125', //Dynect
					'cloudpbx.co' => '74.198.165.40', //Dynect
					'cloudpbx.us' => '209.237.227.53', //PowerDNS
					'cloudpbx.eu' => '209.237.227.53' //PowerDNS

				);
				*/

				$CZONES = array($_REQUEST['zone']);

				if( $_REQUEST['us'] == true ) {
					$CZONES = array_merge($CZONES, array('cloudpbx.us'));
				}
				if( $_REQUEST['ca'] == true ) {
					$CZONES = array_merge($CZONES, array('cloudpbx.ca'));
				}
				if( $_REQUEST['eu'] == true ) {
					$CZONES = array_merge($CZONES, array('cloudpbx.eu'));
				}
				/*
				foreach( $CZONES as $Zone ) {
					CreateRealm( $zone_ip_map[$Zone], $_REQUEST['name'], $Zone );
				}
				*/

				CreateRealm( $_REQUEST['name'], $CZONES );
			}
			
			
		}




	}


	if( $TYPE == "USER" ) {

		$TEMPLATE['username'] = trim($_REQUEST['username']);
		$TEMPLATE['first_name'] = $_REQUEST['first_name'];
		$TEMPLATE['last_name'] = $_REQUEST['last_name'];
		$TEMPLATE['priv_level'] = $_REQUEST['priv_level'];
		$TEMPLATE['email'] = $_REQUEST['email'];
		$TEMPLATE['verified'] = $_REQUEST['verified'];
		$TEMPLATE['timezone'] = $_REQUEST['timezone'];
		$TEMPLATE['password'] = $_REQUEST['password'];
		$TEMPLATE['caller_id']['internal'] = array( 'number' => $_REQUEST['internal_cid_num'], 'name' => $_REQUEST['internal_cid_name'] );
		$TEMPLATE['caller_id']['external'] = array( 'number' => $_REQUEST['external_cid_num'], 'name' => $_REQUEST['external_cid_name'] );
		$TEMPLATE['call_forward'] = array( 
			'enabled' => $_REQUEST['cfwd_enabled'], 
			//'enabled' => false,
			'number' => $_REQUEST['cfwd_number'],
			'require_keypress' => $_REQUEST['cfwd_require_keypress'],
			//'require_keypress' => false,
			'keep_caller_id' => $_REQUEST['cfwd_keep_caller_id'],
			'substitute' => $_REQUEST['cfwd_substitute'],
			//'substitute' => false,
			'direct_calls_only' => $_REQUEST['cfwd_direct_calls_only']
		/*
		number	 Number to redirect calls to when call forwarding is enabled	 string	 yes
		require_keypress	 If set to true when call forwarding takes place it will require the user to dial 1 before connecting the caller	 boolean	 yes
		keep_caller_id	 If set to true when call forwarding takes place it will use the caller-id of the person being forwarded. If false it will use the caller-id of the user.	 boolean	 yes
		substitute	 If set to true, then calls will only go to the number set in the number parameter	 boolean	 yes
		direct_calls_only	 If set to true, then only calls that ring the user/device directly will be forwarded (IE: not ring groups)
		*/
		);
		


		//$TEMPLATE['id'] = $NODE;	
		if( strlen($NODE) ) {



			switch( $OP ) {
				case 'del': //Delete

					$devices = $XBAR->get_devices_by_owner($NODE, $PNODE);
					$vmboxes = $XBAR->get_vmbox_by_owner($NODE, $PNODE);
					/*
					ob_start();
						print_r($devices);
						print_r($vmboxes);
					$XBAR->log(ob_get_clean());
					*/

					//ob_start();
					//foreach( $devices as $device ) echo "del device ".$device['id']." p:$PNODE\n";
					//foreach( $vmboxes as $vmbox ) echo "del vmbox ".$vmbox['id']." p:$PNODE\n";
					//foreach( $devices as $device ) { $resp = $XBAR->del_device($device['id'],$PNODE);print_r($resp); }
					//foreach( $vmboxes as $vmbox )  { $resp = $XBAR->del_vmbox($vmbox['id'],$PNODE);print_r($resp);   }
					//$XBAR->log(ob_get_clean());



					foreach( $devices as $device ) $XBAR->del_device($device['id'],$PNODE);
					foreach( $vmboxes as $vmbox ) $XBAR->del_vmbox($vmbox['id'],$PNODE);
					$resp = $XBAR->del_user($NODE,$PNODE);
				break;

				case 'update': //Update 
				default:
					
					$data = $XBAR->get_user($NODE, $PNODE );
					$TEMPLATE = array_merge($data, $TEMPLATE);
					/*
					unset($TEMPLATE['notifications']); 
					unset($TEMPLATE['music_on_hold']);
					unset($TEMPLATE['media']); 
					unset($TEMPLATE['caller_id_options']); 
					unset($TEMPLATE['vm_to_email']); 
					*/

					if( strlen($TEMPLATE['owner_id']) != 0 ) { 
						$resp = $XBAR->post_user($TEMPLATE, $NODE, $PNODE);
					} else {
						$TEMPLATE['owner_id'] = $PNODE;
						$resp = $XBAR->post_user($TEMPLATE, $NODE, $PNODE);
						$XBAR->log("ATTEMPTED TO UPDATE USER WITH NO OWNER_ID");
					}
			}


		} else { //Create 
			//if( strlen($OWNER_ID) ) $TEMPLATE['owner_id'] = $OWNER_ID;
			if(  strlen($OWNER_ID)  ) {
				$resp = $XBAR->put_user($TEMPLATE,$OWNER_ID);
			} else {
				$resp = $XBAR->put_user($TEMPLATE);
			}
		}
	}

	/*
	ob_start();
	print_r($_REQUEST);
	print_r($TEMPLATE);
	print_r($resp);
	$XBAR->log(ob_get_clean());
	*/



	if( $json_packet['status'] == 'success' || $resp['status'] == 'success' ) {
		$json_packet['success'] = true;
	} else {

		$json_packet['success'] = false;
		$extra_msg = '';
		if( count($resp) ) {
			ob_start();
			print_r($resp);
			$extra_msg = ob_get_clean();
		}
		$json_packet['errormsg'] = $resp['message'].$extra_msg;

	}	


	echo json_encode($json_packet);






?>
