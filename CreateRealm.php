<?php
require_once("Dynect.php");
require_once("Zonomi.php");


date_default_timezone_set("America/Vancouver");


//'216.146.39.124'
//CreateRealm( 'dev', array('cloudpbx.ca') );



function CreateRealm(  $realm, $zones = array( 'cloudpbx.ca','cloudpbx.us'), $sbcs = array( 'sbc1.van1.cloudpbx.ca', 'sbc1.mtl1.cloudpbx.ca','sbc1.dub1.cloudpbx.eu' ) ) {
	

	$zone_ip_map = array(
		'cloudpbx.ca' => '216.146.39.125', //Dynect
		'cloudpbx.ie' => '216.146.39.125', //Dynect
		'cloudpbx.co' => '74.198.165.40', //Dynect
		'cloudpbx.us' => '209.237.227.53', //PowerDNS
		'cloudpbx.eu' => '209.237.227.53' //PowerDNS

	);

	$PROVISION_IP = '74.114.208.162';


	$DynectConfig['host'] = "ssl://api2.dynect.net";
	$DynectConfig['port'] = 443;
	$DynectConfig['debug'] = false;
	$DynectConfig['customer_name'] = 'telephonic';
	$DynectConfig['user_name'] = 'vortex';
	$DynectConfig['password'] = 'mAbesWeVuCa6uGum9SaPajEm';

	$ZON = new Zonomi(array());
	$DYN = new Dynect($DynectConfig); // See if we can make a connection

	$uzones = array_unique($zones);
	foreach( $uzones as $zone ) {
		$DYN->log("Processing zone: $zone");

		$ip = $zone_ip_map[$zone];

		if( $DYN->manages_zone($zone) ) {
			$DYN->log("Dynect API -> ADMIN $zone");
			$RTYPE = "POST";
			$resp = $DYN->get("ARecord/$zone/$realm.$zone");
			if( $resp['status'] == 'success' ) $RTYPE = "PUT";	
				
			//echo $RTYPE; exit(0);	

			/*
			$post_fields = array( 'rdata' => array( 'address' => $ip ), 'ttl' => '0' );
			$resp = $DYN->inter($RTYPE,"ARecord/$zone/$realm.$zone/", $post_fields );
			$DYN->log("Dynect API -> $RTYPE A $realm.$zone $ip");
			$resp['status']=='success'?$DYN->log("Dynect API -> SUCCESS"):$DYN->log("Dynect API -> FAILURE");
			*/
	
			$post_fields = array( 'rdata' => array( 'address' => $PROVISION_IP ), 'ttl' => '0' );
			$resp = $DYN->inter($RTYPE,"ARecord/$zone/provision.$realm.$zone/", $post_fields );
			$DYN->log("Dynect API -> $RTYPE A provision.$realm.$zone $PROVISION_IP");
			$resp['status']=='success'?$DYN->log("Dynect API -> SUCCESS"):$DYN->log("Dynect API -> FAILURE");
			
		

			foreach( $sbcs as $skey => $sbc ) {
				$DYN->log("Dynect API -> $RTYPE SRV _sip.(udp/tcp).$realm.$zone $sbc PRI:".($skey+1)*5);
				$post_fields = array( 'rdata' => array( 'port' => '7000', 'priority' => ($skey+1)*5, 'target' => $sbc, 'weight' => '100'  ), 'ttl' => '60' );
				$resp = $DYN->inter( $RTYPE, "SRVRecord/$zone/_sip._tcp.$realm.$zone/", $post_fields );
				$resp['status']=='success'?$DYN->log("Dynect API -> SUCCESS"):$DYN->log("Dynect API -> TCP FAILURE");
				$resp = $DYN->inter( $RTYPE, "SRVRecord/$zone/_sip._udp.$realm.$zone/", $post_fields );
				$resp['status']=='success'?$DYN->log("Dynect API -> SUCCESS"):$DYN->log("Dynect API -> UDP FAILURE");
			}

			$DYN->log("Dynect API -> Publishing Zone $zone");
			$resp = $DYN->put("Zone/$zone",array("publish" => 1));
			$resp['status']=='success'?$DYN->log("Dynect API -> SUCCESS"):$DYN->log("Dynect API -> FAILURE");
		}

		if( $ZON->manages_zone($zone) ) {

			$ZON->log("Zonomi API -> ADMIN $zone");

			$tcpentry = "";
			$udpentry = "";

			foreach( $sbcs as $skey => $sbc ) {
				$tcpentry .= "&action[$skey]=SET&name[$skey]=_sip._tcp.$realm.$zone&type[$skey]=SRV&value[$skey]=100+7000+$sbc&ttl[$skey]=60&prio[$skey]=".($skey+1)*5;
				$udpentry .= "&action[$skey]=SET&name[$skey]=_sip._udp.$realm.$zone&type[$skey]=SRV&value[$skey]=100+7000+$sbc&ttl[$skey]=60&prio[$skey]=".($skey+1)*5;
			}

			$response = $ZON->send("GET","/app/dns/dyndns.jsp?api_key=".$ZON->apiKey."$tcpentry");
			$response = $ZON->send("GET","/app/dns/dyndns.jsp?api_key=".$ZON->apiKey."$udpentry");


		}

	}

}







?>
