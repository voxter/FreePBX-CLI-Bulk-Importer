<?php
	global $XBAR, $XBOPTS;

	switch($_COOKIE['REALM']) {
		case 'xpatch.sip.2600hz.com': $XBOPTS['host'] = "apps001-demo-ord.2600hz.com"; break;
		case 'telephonic.cloudpbx.ca': $XBOPTS['port'] = 8001; break;
		//case 'telephonic.cloudpbx.ca': $XBOPTS['host'] = 'www.cloudpbx.ca'; break;
		case 'whistle.dev.cloudpbx.ca': $XBOPTS['host'] = "127.0.0.1"; break;
	}

	$XBOPTS['usermd5'] = $_COOKIE['TOKEN'];
	$XBOPTS['realm'] = $_COOKIE['REALM'];

	if( isset($_COOKIE['XAUTH']) ) {
		$XBOPTS['xauth'] = $_COOKIE['XAUTH'];
		$XBOPTS['auth_account_id'] = $_COOKIE['ACTID'];
		$XBOPTS['use_account_id'] = $_COOKIE['ACTID'];
		$XBAR = new CrossBar($XBOPTS); // See if we can make a connection
	} else {
		$XBAR = new CrossBar($XBOPTS); // See if we can make a connection
		setcookie('XAUTH',$XBAR->xauth,0,'/',$EXT_CONFIG['COOKIE_DOMAIN']);
		setcookie('ACTID',$XBAR->auth_account_id,0,'/',$EXT_CONFIG['COOKIE_DOMAIN']);
	}
	

?>
