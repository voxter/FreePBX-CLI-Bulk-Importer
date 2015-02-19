<?php


	global $APPNAME,$XBAR,$CDB;
	$APPNAME = "Vortex";

	$EXT_CONFIG['AESKEY'] = 'devaeskey';

	$EXT_CONFIG['ERROR_LOG'] = '/tmp/vortex.log';

	$EXT_CONFIG['COOKIE_DOMAIN'] = $_SERVER['HTTP_HOST'];
	$EXT_CONFIG['COOKIE_DOMAIN'] = '';

	global $MODULES;


	//$MODULES['Account'] = array( 'icon' => 'Account', 'desktop' => 0, 'name' => 'Account', 'table' => 'account', 'tk' => 'name', 'pk' => 'id', 'pagination' => 0 );


	//$MODULES['AccountTree'] = array( 'icon' => 'AccountTree', 'desktop' => 1, 'name' => 'Account Tree' );
	//$MODULES['AccountTreeCouch'] = array( 'icon' => 'AccountTreeCouch', 'desktop' => 1, 'name' => 'Account Tree Couch' );
	$MODULES['AccountTreeCrossBar'] = array( 'icon' => 'AccountTreeCrossBar', 'desktop' => 1, 'name' => 'Account Tree CrossBar' );
	//$MODULES['AccountWizard'] = array( 'icon' => 'AccountWizard', 'desktop' => 1, 'name' => 'Account Wizard' );


	$MODULES['DIDWizard'] = array( 'icon' => 'DIDWizard', 'desktop' => 1, 'name' => 'DID Wizard' );
	$MODULES['CallflowWizard'] = array( 'icon' => 'CallflowWizard', 'desktop' => 1, 'name' => 'Callflow Wizard' );
	//$MODULES['PackageWizard'] = array( 'icon' => 'PackageWizard', 'desktop' => 1, 'name' => 'Package Wizard' );


	//CouchDB connection
	$CBOPTS['host'] = "127.0.0.1";
	$CBOPTS['port'] = 5984;


	//Cross Bar Connection
	$XBOPTS['host'] = "127.0.0.1";
	$XBOPTS['port'] = 8000;
	//$XBOPTS['usermd5'] = md5($_POST['username'].":".$_POST['pword']);
	//$XBOPTS['realm'] = $_POST['realm'];

	//Works
	/*	
	$MODULES['DIDWizard'] = array( 'icon' => 'DIDWizard', 'desktop' => 1, 'name' => 'DID Wizard' );
	$MODULES['PackageWizard'] = array( 'icon' => 'PackageWizard', 'desktop' => 1, 'name' => 'Package Wizard' );
	$MODULES['ContractWizard'] = array( 'icon' => 'ContractWizard', 'desktop' => 1, 'name' => 'Contract Wizard' );
	$MODULES['PackageConfigurator'] = array( 'icon' => 'PackageConfigurator', 'desktop' => 1, 'name' => 'Package Configurator' );
	*/
	
	






	//$GMODULES['Product'] = array( 'name' => 'Product', 'table' => 'product', 'tk' => 'name', 'pk' => 'id', 'desktop' => 0, 'pagination' => 0 );
	//$GMODULES['CallPlan'] = array( 'name' => 'CallPlan', 'table' => 'callplan', 'tk' => 'name', 'pk' => 'id', 'desktop' => 0, 'pagination' => 0 );
	//$GMODULES['ContractContents'] = array( 'name' => 'ContractContents', 'table' => 'contract_contents', 'tk' => 'id', 'pk' => 'id', 'desktop' => 0, 'pagination' => 0 );


?>
