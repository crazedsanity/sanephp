<?php

echo "RUNNING (". __FILE__ .")!!!!\n";

// set the timezone to avoid spurious errors from PHP
date_default_timezone_set("America/Chicago");

require_once('PHPUnit/Autoload.php');
require_once(dirname(__FILE__) .'/../AutoLoader.class.php');
require_once(dirname(__FILE__) .'/../debugFunctions.php');
require_once(dirname(__FILE__) .'/../testDb.abstract.class.php');
require_once(dirname(__FILE__) .'/../base.abstract.class.php');
require_once(dirname(__FILE__) .'/../cs_global.class.php');
require_once(dirname(__FILE__) .'/../FileSystem.class.php');

// Handle password compatibility (using "ircmaxell/password-compat")
{
	//handle differences in paths...
	$usePath = dirname(__FILE__) . '/../vendor/ircmaxell/password-compat/version-test.php';
	
	ob_start();
	if(!include_once($usePath)) {
		ob_end_flush();
		print "You must set up the project dependencies, run the following commands:\n
			\twget http://getcomposer.org/composer.phar
			\tphp composer.phar install ircmaxell/password-compat\n";
		exit(1);//
	}
	else {
		$output = ob_get_contents();
		ob_end_clean();
		
		if(preg_match('/Pass/', $output)) {
			require_once(dirname($usePath) .'/lib/password.php');
		}
	}
}

// set a constant for testing...
define('UNITTEST__LOCKFILE', dirname(__FILE__) .'/files/rw/');
define('cs_lockfile-RWDIR', constant('UNITTEST__LOCKFILE'));
define('RWDIR', constant('UNITTEST__LOCKFILE'));
define('LIBDIR', dirname(__FILE__) .'/..');

AutoLoader::registerDirectory(dirname(__FILE__) .'/../');



