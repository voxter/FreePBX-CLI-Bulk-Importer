<?php

require_once('Logger.php');

class CrossBar {

	private $log;

	function CrossBar( $options ) {
		$this->force_no_decode = false;	
		$this->debug = true;
		$this->verbose = false;
		$this->log = new Logger();
		$this->is_authenticated = false;	
		foreach($options as $key => $value) $this->$key = $value; 
		$auth = array();

		if( !isset($this->xauth) && !isset($this->auth_account_id) ) {


			if( isset($this->usermd5) ) {
				$auth = $this->send("PUT","/v1/user_auth",'{"data":{"account_name": "'.$this->account_name.'", "credentials": "'.$this->usermd5.'" }}');
			} else {
				$auth = $this->send("PUT","/v1/api_auth",'{"data":{"api_key": "'.$this->api_key.'" }}');
			}
			$this->auth = $auth;
			if( $auth['status'] == 'success' ) {
				$this->is_authenticated = true;	
				$this->xauth = $auth['auth_token'];
				$this->auth_account_id = $auth['data']['account_id'];
				$this->use_account_id = $auth['data']['account_id'];
				$this->log("acct id: ".$auth['data']['account_id']);
				$this->log("auth token: ".$auth['auth_token']);
				
			} else {
				$this->log("API Key failure");
			}
		} /*else {
			$this->log("Using cached credentials.");
			//ob_start();
			//print_r($options);
			//$this->log(ob_get_clean());
		}*/

	}
	function log( $logthis ) {
		if($this->verbose) {
			echo $logthis."\n";
		}
	}


	function is_authenticated() { return($this->is_authenticated); }

	function get_version() {

		$tmp = $this->force_no_decode;
		$this->force_no_decode = true;
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/about" );
		$this->force_no_decode = $tmp;
		return($response);
	}


	function create_webhook( $name, $url, $bind_event = 'authz', $retries = 2, $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;

		$webhook = array();
		$webhook['event_name'] = $name;
		$webhook['bind_event'] = $bind_event;
		$webhook['callback_uri'] = $url;
		//$webhook['callback_method'] = "POST";
		$webhook['retries'] = 2;

		

		$response = $this->send("PUT","/v1/accounts/{$account_id}/webhooks", json_encode(array('data'=>$webhook)) );
		return($response);

	}



	function delete_webhook( $hook_id, $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;

		$response = $this->send("DELETE","/v1/accounts/{$account_id}/webhooks/{$hook_id}");
		return($response);

	}








	function use_account( $account_id, $cache = false ) { 

		$this->use_account_id = $account_id; 
		//load all data?
		if( $cache ) {
			$this->cache = true;
		} else {
			$this->cache = false;
		}

	}



	function get( $type, $id = null, $filters = array(), $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;

		$filter = '';

		if( count($filters) ) {
			foreach( $filters as $key => $val ) $filter .= "filter_$key=$val&";
			if( strlen($filter) ) $filter = '?'.substr($filter,0,-1);
		} else if( strlen($id) ) {
			$filter = "/$id";
		}

		//$this->log("GET /v1/accounts/{$account_id}/$type$filter");
		$response = $this->send("GET","/v1/accounts/{$account_id}/$type$filter");

		return($response['data']);



	}

	function post( $type, $id, $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/$type/$id", json_encode(array('data'=>$data)));
		return($response);
	}

	function put( $type, $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/$type/", json_encode(array('data'=>$data)));
		return($response);
	}


	function del( $type, $id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/$type/$id");
		return($response);
	}







	//convenience function 
	function get_accounts($account_id = null) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$children = $this->get_children($account_id);
		$realms = array();
		foreach( $children as $child ) {
			//$realms[$child['id']] = $child['realm'];
			$realms[$child['realm']] = $child['id'];
		}
		$realms[$this->realm] = $this->auth_account_id;
		return($realms);
	}

	function get_callflow_id_map($account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$test = $this->get_callflows($account_id);
		$nums = array();
		foreach( $test as $key => $data ) {
			//$t2 = $XBAR->get_callflow($data['id']);
			if(isset($data['numbers'])) {
				foreach( $data['numbers'] as $num ) {
					$nums[$num] = $data['id'];	
				} 
			}
		}

		return($nums);
	}



	function get_callflows_by( $id, $type = 'device' ) {

		$aout = array();
		$cfs = $this->get_callflows();

		foreach( $cfs as $cf ) { 
			$xcf = $this->get_callflow($cf['id']);
			//print_r($xcf['metadata']);
			print_r($xcf);
			//print_r($cf['numbers']); 
			foreach( $xcf['metadata'] as $key => $data ) {
				if( $data['pvt_type'] == $type ) {
					$aout[$xcf['id']] = $cf['numbers'];
				}
			}
		}

		return($aout);

	}






	function get_device_by_username( $username, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('devices',null,array('username'=>$username),$account_id);	
		return($response[0]);
	}

	function get_device_by_name( $name, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('devices',null,array('name'=>$name),$account_id);	
		
		// If the device has not been created this returns an empty response
		if(empty($response)) {
			$response['id'] = null;
			return $response;
		}
		
		return $response[0];
		
		//return($response[0]);
	}

	function get_devices_by_owner( $owner_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('devices',null,array('owner_id'=>$owner_id),$account_id);	
		return($response);
	}

	function get_device_by_owner( $owner_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('devices',null,array('owner_id'=>$owner_id),$account_id);	
		return($response[0]);
	}

	function get_vmbox_by_ext( $extension,  $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('vmboxes',null,array('mailbox'=>$extension),$account_id);	
		return($response[0]);
	}

	function get_vmbox_by_number( $number,  $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('vmboxes',null,array('mailbox'=>$number),$account_id);	
		return($response[0]);
	}
	



	function get_vmbox_by_owner( $owner_id,  $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		//if( $account_id == null ) $account_id = $this->use_account_id;
		//$response = $this->send("GET","/v1/accounts/{$account_id}/vmboxes?filter_mailbox=$extension");
		//return($response['data'][0]);

		$response = $this->get('vmboxes',null,array('owner_id'=>$owner_id),$account_id);	

		foreach( $response as $key => $data ) {
			$response[$key] = array_merge($data, $this->get_vmbox($data['id'],$account_id));	

		}

		return($response);
	}




	function get_vmbox_by_name( $name,  $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('vmboxes',null,array('name'=>rawurlencode($name)),$account_id);	
		
		// If the device has not been created this returns an empty response
		if(empty($response)) {
			$response['id'] = null;
			return $response;
		}
		
		return $response[0];
		
		//return($response[0]);
	}

	
	function login_vmbox( $mailbox, $pin,   $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->get('vmboxes',null,array('mailbox'=>$mailbox,'pin'=>$pin),$account_id);	
		return($response[0]);
	}
	




	function get_account_id_by_realm( $realm ) { 
		$realms = $this->get_accounts();
		return( $realms[$realm] );
	}

	function get_user_by_name( $username, $account_id = null ) {

		$response = $this->get('users',null,array('username'=>rawurlencode($username)),$account_id);
		//return($response[0]);
		//return($response);
		
		// If the user has not been created this returns an empty response
		if(empty($response)) {
			$response['id'] = null;
			return $response;
		}
		
		return $response[0];
		
		/*
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/users?filter_username=$username");
		return($response);
		*/
	}

	function get_user_id( $username ) {
		$user = $this->get_user($username);
		if( isset($user['id']) ) return($user['id']); return false;
	}

	function get_children($account_id = null) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/children");
		return($response['data']);
	}

	function get_siblings() { 
		$response = $this->send("GET","/v1/accounts/{$this->use_account_id}/siblings");
		return($response['data']);
	}

	function get_descendants() { 
		$response = $this->send("GET","/v1/accounts/{$this->use_account_id}/descendants");
		return($response['data']);
	}
	
	function set_parent( $account_id, $new_parent_id ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/parent","parent=$new_parent_id");
		return($response['data']);
	}
	

	function get_all_info( $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;
		$users = $this->get_users($account_id);
		$temp_devices = $this->get_devices($account_id);
		$devices_status = $this->get_devices_status($account_id);


		foreach( $devices as $device ) {
			

		}
		
		foreach( $users as $user ) {

			
			
		}

	}


	/*

	function is_device_registered( $device_id,  $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/devices/status?filter_device_id=$device_id");
		if( isset($response['data'][0]) ) {
			return(true);
		} 
		return(false);
	}
	*/





	function get_devices_status( $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/devices/status");
		$devices = array();
		foreach( $response['data'] as $data ) $devices[$data['device_id']] = $data['registered'];
		return($devices);
	}



	function get_devices( $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/devices");
		return($response['data']);
	}


	function get_vmboxes( $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/vmboxes");
		foreach($response['data'] as $key => $val ) $response['data'][$key] = array_merge( $val, $this->get_vmbox($val['id'],$account_id) );
		return($response['data']);
	}

	function get_messages( $box_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/vmboxes/$box_id/messages");
		return($response['data']);
	}

	function get_message( $message_id, $box_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/vmboxes/$box_id/messages/$message_id");
		return($response['data']);
	}



	function del_message( $message_id, $box_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/vmboxes/$box_id/messages/$message_id");
		return($response['data']);
	}



	function get_message_raw( $message_id, $box_id, $account_id = null ) { 

		$tmp = $this->force_no_decode;
		$this->force_no_decode = true;

		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/vmboxes/$box_id/messages/$message_id/raw");
		$this->force_no_decode = $tmp;
		return($response);
	}

	
	// If no media_id is specified the get all media
	function get_media( $media_id = null, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/media/{$media_id}" );
		return($response['data']);

	}
	
	// If no media_id is specified the get all media
	function get_media_raw( $media_id = null, $account_id = null ) {

		$tmp = $this->force_no_decode;
		$this->force_no_decode = true;

		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/media/{$media_id}/raw" );

		$this->force_no_decode = $tmp;
		return($response);

	}


	
	function del_media( $media_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/media/{$media_id}" );
		return($response);
	}
	

	function get_users( $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/users" );
		return($response['data']);
	}






	function get_resources( $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/local_resources");
		return($response['data']);
	}

	function get_available_subscriptions( $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/events/available");
		return($response['data']);
	}

	function get_subs( $account_id = null ) {return( $this->get_available_subscriptions($account_id)); } 


















	function get_device( $device_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/devices/$device_id");
		return($response['data']);
	}

	function del_device( $device_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/devices/$device_id");
		return($response);
	}

	function put_device( $data, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/devices/",json_encode(array('data'=>$data)));
		return($response);
	}

	function post_device( $data, $device_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/devices/$device_id", json_encode(array('data'=>$data)));
		return($response);
	}





	function get_vmbox( $box_id, $account_id = null ) { 
		$response = $this->get("vmboxes",$box_id,null,$account_id);
		return($response);
	}


	function del_vmbox( $box_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/vmboxes/$box_id");
		return($response);
	}


	function put_vmbox( $data, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/vmboxes/", json_encode(array('data'=>$data)));
		return($response);
	}

	function post_vmbox( $data, $box_id, $account_id = null ) { 
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/vmboxes/$box_id", json_encode(array('data'=>$data)));
		return($response);
	}














	function get_user( $user_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/{$account_id}/users/$user_id" );
		return($response['data']);
	}



	function del_user( $user_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/users/$user_id");
		return($response);
	}

	function put_user( $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/users", json_encode(array('data'=>$data)));
		return($response);
	}

	function post_user( $data, $user_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/users/$user_id", json_encode(array('data'=>$data),JSON_FORCE_OBJECT));
		return($response);
	}

	function get_account( $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$account = $this->send("GET","/v1/accounts/$account_id");
		return($account['data']);
	}


	function del_account( $account_id ) {
		$response = $this->send("DELETE","/v1/accounts/$account_id/");
		return($response);
	}

	function put_account( $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}", json_encode(array('data'=>$data)));
		return($response);
	}
	
	function post_account( $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}", json_encode(array('data'=>$data)));
		return($response);
	}






	function get_callflows( $account_id = null) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/$account_id/callflows");
		return($response['data']);
	}


	function get_callflow( $call_flow_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("GET","/v1/accounts/$account_id/callflows/$call_flow_id");
		return($response['data']);
	}



	function put_callflow( $data, $account_id = null) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/callflows", json_encode(array('data'=>$data)));
		return($response);
	}


	function del_callflow( $call_flow_id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/$account_id/callflows/$call_flow_id");
		return($response);
	}






	/*
	function profile_send( $method, $url, $post_data = NULL ) {
		$mstart = microtime(true);
		$data = send($method,$url,$post_data);
		$mend = microtime(true);
		return( array( 'data' => $data, 'µTime' => ( $mend - $mstart )));	
	}
	*/
	






	function post_media( $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/media/",json_encode( array( 'data' => $data['data'] ) ) );


		if( $response['status'] == 'success' && isset($response['data']['id']) ) {
			$response = $this->send("POST","/v1/accounts/{$account_id}/media/{$response['data']['id']}/raw",$data['raw'], $data['type'] );
		}
		return($response);

	}


	
	function send( $method, $url, $post_data = NULL, $type = 'application/json' ) {

		$bldred=chr(0x1B).'[1;31m'; $bldgrn=chr(0x1B).'[1;32m'; $bldylw=chr(0x1B).'[1;33m'; $bldblu=chr(0x1B).'[1;34m'; $bldpur=chr(0x1B).'[1;35m'; $bldcyn=chr(0x1B).'[1;36m'; $bldwht=chr(0x1B).'[1;37m'; $txtrst=chr(0x1B).'[0m'; 


		$mstart = microtime(true);
		$s = fsockopen($this->host, $this->port, $errno, $errstr);
		//$this->log(" fsockopen Errno: $errno Str:$errstr");	
		if(!$s) {
			$this->fsock_errno = $errno;
			$this->fsock_errstr = $errstr;
			return false; //if the connection fails return false
		}

		//$request = "$method $url HTTP/1.1\r\nHost: $this->host:$this->port\r\n";
		$request = "$method $url HTTP/1.0\r\nHost: $this->host\r\n";
		if (isset($this->user)) $request .= "Authorization: Basic ".base64_encode("$this->user:$this->pass")."\r\n";


		$request .= "Content-Type: $type\r\n";
		$request .= "Accept: application/json, application/octet-stream, audio/*\r\n";
		if( isset($this->xauth) ) $request .= "X-Auth-Token: {$this->xauth}\r\n";

		if($post_data) {
			//$request .= "Content-Type: application/json\r\n";
			$request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
			$request .= "$post_data\r\n";
		} else {
			$request .= "\r\n";
		}


		fwrite($s, $request);
		$response = "";

		while(!feof($s)) { $response .= fgets($s); }

		$mend = microtime(true);

		//if( $this->profile ) printf("{$bldblu}URL:{$bldylw}$url {$bldblu}µT:{$bldylw}".( $mend - $mstart ).$txtrst."\n");
		$this->log( "{$bldred}$url{$txtrst} {$bldylw}µT:".( $mend - $mstart )."{$txtrst}");

		list($this->headers, $this->body) = explode("\r\n\r\n", $response);

		$REQUEST_ID = '';

		if( strlen($this->headers) ) {
			$hexp = explode("\n",$this->headers);
			foreach( $hexp as $line ) {
				if( strstr($line,"X-Request-ID") ) {
					$reqxp = explode(":",$line);
					$REQUEST_ID = $reqxp[1];
				}				


			}
		}

		if( $method == "DELETE" ) {
			if( stristr($this->headers,"204 No Content") ) {
				return( array('status' => 'success'));
			}

		}
		if( !stristr($this->headers,"200 OK") && !stristr($this->headers,"201 Created") && $this->debug) {
		
			$bodyJson = json_decode($this->body, true);
			if(isset($bodyJson['data']['numbers']['unique']) && stristr($bodyJson['data']['numbers']['unique'], 'exists in callflow')) {
				// Do nothing
			}
			else {
				$this->log->log("{$bldpur}>>>>: $method $url HTTP/1.0 ($type) len:".strlen($post_data)."$txtrst");
				if( $post_data && $type == 'application/json' )
					$this->log->log("{$bldpur}>>>>: ".trim($post_data));
				$this->log->log("{$bldylw}<<<<: ".trim($hexp[0])." µT=".( $mend - $mstart )." request_id:$REQUEST_ID{$txtrst}");
				$this->log->log("{$bldylw}<<<<: ".$this->headers."{$txtrst}");
				$this->log->log("{$bldylw}<<<<: ".$this->body."{$txtrst}.\n\n");
			}
		}
		// Log the outgoing posts always
		else {
			$this->log->log("{$bldpur}>>>>: $method $url HTTP/1.0 ($type) len:".strlen($post_data)."$txtrst");
			if( $post_data && $type == 'application/json' )
				$this->log->log("{$bldpur}>>>>: ".trim($post_data));
		}
		

		if( stristr($this->headers,"401 Unauthorized") || stristr($this->headers,"400 Not Found") || stristr($this->headers,"500 Internal Server") ) { 
				//$this->log("{$bldpur}Found 401{$txtrst}");
				return( array("data" => array('status' => 'failure')) );
				//return false;
		}




		if( $this->force_no_decode ) { return $this->body; } 

		return json_decode($this->body,true);
		

	}



}



?>
