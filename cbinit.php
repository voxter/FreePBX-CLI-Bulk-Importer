<?php
	global $CDB, $CBOPTS;
	$CBOPTS['user'] = 'b3c90022bad176593311';
	$CBOPTS['pass'] = '4179aafb33fs5de3096c';

	$CBOPTS['user'] = 'patch';
	$CBOPTS['pass'] = 'testing';

	$CDB = new CouchDB($CBOPTS); // See if we can make a connection
	
?>
