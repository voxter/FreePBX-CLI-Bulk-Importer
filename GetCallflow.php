<?php 


require_once("CrossBar.php");

$bldred=chr(0x1B).'[1;31m'; $bldgrn=chr(0x1B).'[1;32m'; $bldylw=chr(0x1B).'[1;33m'; $bldblu=chr(0x1B).'[1;34m'; $bldpur=chr(0x1B).'[1;35m'; $bldcyn=chr(0x1B).'[1;36m'; $bldwht=chr(0x1B).'[1;37m'; $txtrst=chr(0x1B).'[0m'; 




$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 8000;
$CBOPTS['profile'] = true;
$CBOPTS['debug'] = true;

$CBOPTS['usermd5'] = md5("xpatch:2600hz");
$CBOPTS['realm'] = "whistle.dev.cloudpbx.ca";
//$CBOPTS['api_key'] = "053e71c6414f904326f20a2731bde05736c00b75639141ab69137ee19d5d069e"; //whistle.dev.cloudpbx.ca











global $XBAR;
$XBAR = new CrossBar($CBOPTS); // See if we can make a connection
$USER_ID = '';
$ACCOUNT_ID = '';
$DEVICE_ID = '';



$CLIENT_REALM = 'atimi.dev.cloudpbx.ca'; 
$ACCOUNTS = $XBAR->get_accounts();
$XBAR->use_account($ACCOUNTS[$CLIENT_REALM]);





printf("{$bldblu}TEST: Authentication{$txtrst}\n");

if( $XBAR->is_authenticated() ) {
	printf("{$bldgrn}PASS: Authentication Success.{$txtrst}\n\n");

	$cfm = $XBAR->get_callflow_id_map();
	//$cf = $XBAR->get_callflow($cfm['5555']);
	print_r($cfm);
	
	//$resp = $XBAR->del_callflow($cfm['1133']);
	$resp = $XBAR->del_callflow("973a84e38d6a56fa2d94fe4ebf05354d","79fcf74d37daf355a92e5843251123c3");
	print_r($resp);


} else {


	printf("{$bldred}FAIL: Authentication Failure.{$txtrst}\n\n");


}


?>
