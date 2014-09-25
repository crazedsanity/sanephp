<?php

require_once(dirname(__FILE__) .'/../RegisterUser.class.php');
require_once(dirname(__FILE__) .'/../Logger.class.php');
require_once(dirname(__FILE__) .'/../Upgrade.class.php');

use crazedsanity\RegisterUser;

class RegisterUserTest extends testDbAbstract {
	
	//--------------------------------------------------------------------------
	function __construct() {
		parent::__construct();
	}//end __construct()
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	function setUp() {
		$this->reset_db(dirname(__FILE__) .'/../setup/schema.pgsql.sql');
		parent::setUp();
	}//end setUp()
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	public function tearDown() {
//		parent::tearDown();
	}//end tearDown()
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	public function test_emailValidity() {
		$regUser = new RegisterUser($this->dbObj);
		$this->assertTrue(is_object($regUser));
//		$this->assertEquals($this->dbObj, $regUser->dbObj);
		
		
		$this->assertTrue($regUser->check_email_validity('foo@bar.com'));
		$this->assertTrue($regUser->check_email_validity('x.y_z-123@mail.poop.cz'));
		$this->assertFalse($regUser->check_email_validity('x@y.z'));
	}
	//--------------------------------------------------------------------------

}
