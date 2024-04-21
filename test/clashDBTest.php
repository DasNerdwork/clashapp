<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/db/clash-db.php');

class ClashDBTest extends TestCase {
    private static $db;
    private static $testAcc1;
    private static $testAcc2;
    private static $testAcc3;

    public static function setUpBeforeClass(): void {
        self::$db = new DB();

        // Create Testaccounts for testing purposes
        self::$testAcc1 = self::$db->create_account('PHPUnitTest', 'EUW', getenv('DB_TESTUSER'), password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        self::$testAcc2 = self::$db->create_account('PHPUnitTest2', 'EUW', getenv('DB_TESTUSER2'), password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier234');
        self::$testAcc3 = self::$db->create_account('PHPUnitTest3', 'EUW', 'phpunittest2@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier345');
        self::$db->verify_account('verifier123');
        self::$db->verify_account('verifier234');
        self::$db->verify_account('verifier345');
        self::$db->deactivate_account('PHPUnitTest2', getenv('DB_TESTUSER2'), 'password123');
    }

    public static function tearDownAfterClass(): void {

        // Clean-up and delete Testaccounts
        self::$db->deactivate_account('PHPUnitTest', getenv('DB_TESTUSER'), 'password123');
        self::$db->force_delete_account(self::$testAcc1['id'], self::$testAcc1['username'], self::$testAcc1['region'], self::$testAcc1['email'], 'password123');
        self::$db->force_delete_account(self::$testAcc2['id'], self::$testAcc2['username'], self::$testAcc2['region'], self::$testAcc2['email'], 'password123');
        self::$db->force_delete_account(self::$testAcc3['id'], self::$testAcc3['username'], self::$testAcc3['region'], self::$testAcc3['email'], 'password123');
    }

    /**
     * @covers DB::check_credentials
     * @uses DB
     */
    public function testCheckCredentials() {
        // Test with valid credentials
        $validCredentials = self::$db->check_credentials(getenv('DB_TESTUSER'), 'password123');
        $this->assertEquals('success', $validCredentials['status'], 'Valid credentials should return success status');
        $this->assertArrayHasKey('id', $validCredentials, 'Valid credentials should return an id key');
        $this->assertArrayHasKey('region', $validCredentials, 'Valid credentials should return a region key');
        $this->assertArrayHasKey('username', $validCredentials, 'Valid credentials should return a username key');
        $this->assertArrayHasKey('email', $validCredentials, 'Valid credentials should return an email key');
        $this->assertArrayHasKey('puuid', $validCredentials, 'Valid credentials should return a puuid key');

        $validCredentialsUsername = self::$db->check_credentials("PHPUnitTest", 'password123');
        $this->assertEquals('success', $validCredentialsUsername['status'], 'Valid credentials should return success status');
        $this->assertArrayHasKey('id', $validCredentialsUsername, 'Valid credentials should return an id key');
        $this->assertArrayHasKey('region', $validCredentialsUsername, 'Valid credentials should return a region key');
        $this->assertArrayHasKey('username', $validCredentialsUsername, 'Valid credentials should return a username key');
        $this->assertArrayHasKey('email', $validCredentialsUsername, 'Valid credentials should return an email key');
        $this->assertArrayHasKey('puuid', $validCredentialsUsername, 'Valid credentials should return a puuid key');

        $wrongPassword = self::$db->check_credentials(getenv('DB_TESTUSER'), 'wrongpassword');
        $this->assertEquals('error', $wrongPassword['status'], 'Invalid credentials should return error status');
        $this->assertEquals('Email/Username or password is invalid.', $wrongPassword['message'], 'Invalid credentials message should be correct');

        $deactivatedAccountDeactivated = self::$db->check_credentials("PHPUnitTest2", 'password123');
        $this->assertEquals('Deactivated', $deactivatedAccountDeactivated['label'], 'Deactivated accounts should return Deactivated on correct passwords');

        $deactivatedAccountForbidden = self::$db->check_credentials(getenv('DB_TESTUSER2'), 'wrongpassword123');
        $this->assertEquals('Forbidden', $deactivatedAccountForbidden['label'], 'Deactivated accounts should return Forbidden on wrong passwords');

        $wrongUsername = self::$db->check_credentials('test@example.com', 'wrongpassword');
        $this->assertEquals('error', $wrongUsername['status'], 'Invalid credentials should return error status');
        $this->assertEquals('Email/Username or password is invalid.', $wrongUsername['message'], 'Invalid credentials message should be correct');
    }

    /**
     * @covers DB::get_credentials_2fa
     * @uses DB
     */
    public function testGetCredentials2FA() {
        $validEmail = self::$db->get_credentials_2fa(getenv('DB_TESTUSER'));
        $this->assertEquals('success', $validEmail['status'], 'Valid email should return success status');
        $this->assertArrayHasKey('id', $validEmail, 'Valid email should return an id key');
        $this->assertArrayHasKey('region', $validEmail, 'Valid email should return a region key');
        $this->assertArrayHasKey('username', $validEmail, 'Valid email should return a username key');
        $this->assertArrayHasKey('email', $validEmail, 'Valid email should return an email key');
        $this->assertArrayHasKey('puuid', $validEmail, 'Valid email should return a puuid key');
        $this->assertArrayHasKey('2fa', $validEmail, 'Valid email should return a 2fa key');

        // Test with invalid username
        $invalidUsername = self::$db->get_credentials_2fa('invaliduser');
        $this->assertEquals('error', $invalidUsername['status'], 'Invalid username should return error status');
        $this->assertEquals('Unable to fetch userdata, please contact an administrator.', $invalidUsername['message'], 'Invalid username message should be correct');
    }

    /**
     * @covers DB::account_exists
     * @uses DB
     */
    public function testAccountExists() {
        $this->assertTrue(self::$db->account_exists(getenv('DB_TESTUSER')), 'Existing email should return true');
        $this->assertTrue(self::$db->account_exists('PHPUnitTest'), 'Existing username should return true');
        $this->assertTrue(self::$db->account_exists(getenv('DB_TESTUSER'), 'PHPUnitTest'), 'Existing mail and username should return true');
        $this->assertFalse(self::$db->account_exists(''), 'Non-existing email and username should return false');
    }

    /**
     * @covers DB::connect_account
     * @covers DB::disconnect_account
     * @uses DB
     */
    public function testDisConnectAccount() {
        $this->assertFalse(self::$db->connect_account('abcde', 'test@example.com'), 'Non-existing email and username should return false');
        $this->assertTrue(self::$db->connect_account('abcde', 'PHPUnitTest'), 'Standard procedure of setting connect account puuid should return true');
        $this->assertTrue(self::$db->connect_account('abcde', 'PHPUnitTest'), 'Already existing account connection should keep returning true');

        $this->assertFalse(self::$db->disconnect_account('abcde', 'test@example.com'), 'Non-existing account should return false');
        $this->assertFalse(self::$db->disconnect_account('hijkl', 'PHPUnitTest'), 'Wrong puuid token should return false');
        $this->assertTrue(self::$db->disconnect_account('abcde', 'PHPUnitTest'), 'Standard procedure of removing account puuid connection should return true');
    }

    /**
     * @covers DB::set_stay_code
     * @covers DB::get_stay_code
     * @uses DB
     */
    public function testSetAndGetStayCode() {
        $setFailure = self::$db->set_stay_code('abcde', 'test@example.com');
        $this->assertFalse($setFailure, 'Setting staycode of non-existing account should return false');

        $setSuccess1 = self::$db->set_stay_code('abcde', getenv('DB_TESTUSER'));
        $this->assertTrue($setSuccess1, 'Setting staycode via existing mail should return true');

        $getSuccess1 = self::$db->get_stay_code(getenv('DB_TESTUSER'));
        $this->assertEquals('abcde', $getSuccess1, 'Retrieved staycode via mail should match the one set');

        $setSuccess2 = self::$db->set_stay_code(NULL, 'PHPUnitTest');
        $this->assertTrue($setSuccess2, 'Setting staycode via existing username should return true');

        $getSuccess2 = self::$db->get_stay_code('PHPUnitTest');
        $this->assertNull($getSuccess2, 'Retrieved staycode via username should return null');

        $getFailure = self::$db->get_stay_code('test@example.com');
        $this->assertNull($getFailure, 'Retrieved staycode via non-existing account should return null');
    }

    /**
     * @covers DB::setPremium
     * @covers DB::getPremium
     * @uses DB
     */
    public function testSetAndGetPremium() {
        $setPremium1 = self::$db->setPremium(true, 'test@example.com');
        $this->assertFalse($setPremium1, 'Setting premium of non-existing account should return false');

        $setPremium2 = self::$db->setPremium(true, getenv('DB_TESTUSER'));
        $this->assertTrue($setPremium2, 'Setting premium via existing mail should return true');

        $getPremium1 = self::$db->getPremium(getenv('DB_TESTUSER'));
        $this->assertTrue((boolean)$getPremium1, 'Retrieved premium via mail should match the one set');

        $setPremium3 = self::$db->setPremium(false, 'PHPUnitTest');
        $this->assertTrue($setPremium3, 'Setting premium via existing username should return true');

        $getPremium2 = self::$db->getPremium('PHPUnitTest');
        $this->assertFalse((boolean)$getPremium2, 'Retrieved premium via username should return false');

        $getPremium3 = self::$db->getPremium('test@example.com');
        $this->assertFalse($getPremium3, 'Retrieved premium via non-existing account should return false');
    }

    /**
     * @covers DB::get_data_via_stay_code
     * @uses DB::set_stay_code
     * @uses DB
     */
    public function testGetDataViaStayCode() {
        $setStaySuccess = self::$db->set_stay_code('lmnop', getenv('DB_TESTUSER'));
        $existingData = self::$db->get_data_via_stay_code('lmnop');
        $this->assertEquals('success', $existingData['status'], 'Existing staycode should return success status');
        $this->assertArrayHasKey('id', $existingData, 'Existing staycode should return an id key');
        $this->assertArrayHasKey('region', $existingData, 'Existing staycode should return a region key');
        $this->assertArrayHasKey('username', $existingData, 'Existing staycode should return a username key');
        $this->assertArrayHasKey('email', $existingData, 'Existing staycode should return an email key');
        $this->assertArrayHasKey('puuid', $existingData, 'Existing staycode should return a puuid key');

        $nonExistingData = self::$db->get_data_via_stay_code('xyz789');
        $this->assertEquals('error', $nonExistingData['status'], 'Non-existing staycode should return error status');
        
        $setSuccess2 = self::$db->set_stay_code(NULL, getenv('DB_TESTUSER'));
    }
    
    /**
     * @covers DB::deactivate_account
     * @uses DB
     */
    public function testDeactivateAccount() {
        $deactivatePWFail = self::$db->deactivate_account('PHPUnitTest3', 'phpunittest2@example.com', 'wrongpassword123');
        $this->assertEquals('error', $deactivatePWFail['status'], 'Trying deactivate action with wrong password should fail');

        $deactivateSuccess = self::$db->deactivate_account('PHPUnitTest3', 'phpunittest2@example.com', 'password123');
        $this->assertEquals('success', $deactivateSuccess['status'], 'Creating an account should return success status');

        $deactivateAgainFail = self::$db->deactivate_account('PHPUnitTest3', 'phpunittest2@example.com', 'password123');
        $this->assertEquals('error', $deactivateAgainFail['status'], 'Deactivating an inactive account should fail');

        $deactivateUnknownFail = self::$db->deactivate_account('WrongUser', 'unknown@example.com', 'password123');
        $this->assertEquals('error', $deactivateAgainFail['status'], 'Deactivating a non-existing account should fail');
    }

    /**
     * @covers DB::create_account
     * @covers DB::delete_account
     * @covers DB::force_delete_account
     * @uses DB
     */
    public function testAccountManagement() {
        $createdAccount = self::$db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $this->assertEquals('success', $createdAccount['status'], 'Creating an account should return success status');
        $this->assertArrayHasKey('region', $createdAccount, 'Creating an account should return a region key');
        $this->assertArrayHasKey('username', $createdAccount, 'Creating an account should return a username key');
        $this->assertArrayHasKey('email', $createdAccount, 'Creating an account should return an email key');
        $this->assertEquals('phpunittest@example.com', $createdAccount['email'], 'Created account should have correct email');

        $overCreateAccount = self::$db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $this->assertEquals('error', $overCreateAccount['status'], 'Creating an already existing account should fail');

        $failDeletedAccount = self::$db->delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'wrongpassword');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete action with wrong password account should fail');

        $failDeletedAccount = self::$db->delete_account($createdAccount['id'], $createdAccount['username'], 'KR', $createdAccount['email'], 'password123');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete account action with wrong data should fail');

        $deletedAccount = self::$db->delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'password123');
        $this->assertEquals('success', $deletedAccount['status'], 'Deleting an account should return success status');
        $this->assertEquals('Account successfully deactivated! It will be deleted withing the next 48-72 hours.', $deletedAccount['message'], 'Deleting an account should return correct message');

        $failForceDeletedAccount = self::$db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], 'test@example.com', 'password123');
        $this->assertEquals('Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.', $failForceDeletedAccount['message'], 'Trying delete action with wrong account data should fail');

        $failDeletedAccount = self::$db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'wrongpassword');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete account action with wrong data should fail');

        $forceDeletedAccount = self::$db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'password123');
        $this->assertEquals('success', $forceDeletedAccount['status'], 'Deleting an account should return success status');
    }

    /**
     * @covers DB::verify_account
     * @uses DB
     */
    public function testVerifyAccount() {
        $testCreatedAccount = self::$db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');

        $testFailVerify = self::$db->verify_account('wrongverifier');
        $this->assertEquals('error', $testFailVerify['status'], 'Trying to verify non-existent');
        $testSuccessVerify = self::$db->verify_account('verifier123');
        $this->assertEquals('success', $testSuccessVerify['status'], 'Deleting an account should return success status');

        self::$db->delete_account($testCreatedAccount['id'], $testCreatedAccount['username'], $testCreatedAccount['region'], $testCreatedAccount['email'], 'password123');
        self::$db->force_delete_account($testCreatedAccount['id'], $testCreatedAccount['username'], $testCreatedAccount['region'], $testCreatedAccount['email'], 'password123');
    }

    /**
     * @covers DB::verify_account
     * @covers DB::check_status
     * @uses DB::create_account
     * @uses DB::delete_account
     * @uses DB::force_delete_account
     * @uses DB
     */
    public function testAccountStatus() {
        $testAccountForStatus = self::$db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $checkStatusCreate = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unverified', $checkStatusCreate['label'], 'After account creation the account should be unverified');

        self::$db->verify_account('wrongverifier');
        $checkStatusFailVerify = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unverified', $checkStatusFailVerify['label'], 'Trying to verify a non-existent verifier should fail and not change the status');
        self::$db->verify_account('verifier123');
        $checkStatusSuccessVerify = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Verified', $checkStatusSuccessVerify['label'], 'Successfully verifying an account should change its status correctly');

        self::$db->delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'wrongpassword');
        $checkStatusFailSoftDelete = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Verified', $checkStatusFailSoftDelete['label'], 'Trying to delete an account with the wrong password should not change the status');
        self::$db->delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'password123');
        $checkStatusSuccessSoftDelete = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Deactivated', $checkStatusSuccessSoftDelete['label'], 'Trying to delete an account with the correct password should change its status');

        self::$db->force_delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'wrongpassword');
        $checkStatusFailSoftDelete = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Deactivated', $checkStatusFailSoftDelete['label'], 'Trying to force-delete an account with the wrong password should not change the status');
        
        self::$db->force_delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'password123');
        $checkStatusSuccessSoftDelete = self::$db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unknown', $checkStatusSuccessSoftDelete['label'], 'Trying to force-delete an account with the correct password remove it from being checkable');
    }

    /**
     * @covers DB::get_puuid
     * @uses DB
     */
    public function testGetPUUID() {
        $failGetPUUID = self::$db->get_puuid(getenv('DB_TESTUSER'));
        $missingGetPUUID = self::$db->get_puuid('puuidtest@example.com');
        $successGetPUUID = self::$db->get_puuid('DasNerdwork');

        $this->assertEquals('success', $successGetPUUID['status'], 'Trying to get a puuid from an existing account with puuid should succeed');
        $this->assertEquals('error', $missingGetPUUID['status'], 'Trying to get a puuid from a non-existent account should return an error');
        $this->assertNull($failGetPUUID['puuid'], 'Trying to get a puuid from an account while the puuid is not existing should return an empty puuid');
    }

    /**
     * @covers DB::set_reset_code
     * @covers DB::get_reset_code
     * @covers DB::check_reset_code
     * @uses DB
     */
    public function testHandleResetCode() {
        $failSetCode = self::$db->set_reset_code('puuidtest@example.com', 'reset123');
        $successSetCode = self::$db->set_reset_code('PHPUnitTest', 'reset123');
        $testOverride = self::$db->set_reset_code('PHPUnitTest', 'reset345');
        $failGetCode = self::$db->get_reset_code('puuidtest@example.com');
        $successGetCode = self::$db->get_reset_code('PHPUnitTest');
        
        $this->assertFalse($failSetCode, 'Setting a reset code on a non-exitising account should fail');
        $this->assertTrue($successSetCode, 'Standard procedure of setting reset codes should succeed');
        $this->assertTrue($testOverride, 'Standard procedure of overriding reset codes should succeed');
        $this->assertFalse($failGetCode, 'Getting a reset code on a non-exitising account should fail');
        $this->assertArrayHasKey('resetter', $successGetCode, 'Getting the success code of an existing account should return its resetter');
        $this->assertArrayHasKey('timestamp', $successGetCode, 'Getting the success code of an existing account should return its timestamp');

        $failCheckCode = self::$db->check_reset_code('wrongresetter');
        $successCheckCode = self::$db->check_reset_code($successGetCode['resetter']);

        $this->assertEquals('error', $failCheckCode['status'], 'Trying to get data via a non-existing resetter should return an error');
        $this->assertEquals('success', $successCheckCode['status'], 'Trying to get data via an existing resetter should return successfully');
    }

    /**
     * @covers DB::reset_password
     * @uses DB::check_credentials
     * @uses DB
     */
    public function testResetPW() {
        $checkPasswordBefore = self::$db->check_credentials(getenv('DB_TESTUSER'), 'password123');
        $this->assertThat($checkPasswordBefore['label'],
            $this->logicalOr(
                $this->stringContains('Deactivated'),
                $this->stringContains('Allowed'),
            ), 'Password check status of account was not successful before testrun'
        );

        $failResetPW = self::$db->reset_password('wronguser', 'wronguser@example.com', password_hash('newpass123', PASSWORD_BCRYPT, ['cost' => 11]));
        $successResetPW = self::$db->reset_password('PHPUnitTest', getenv('DB_TESTUSER'), password_hash('newpass123', PASSWORD_BCRYPT, ['cost' => 11]));

        $this->assertEquals('error', $failResetPW['status'], 'Resetting a password of a non-existing account did not result in an error');
        $this->assertEquals('success', $successResetPW['status'], 'Resetting a password did not succeed although it should have');

        $checkPasswordAfter = self::$db->check_credentials(getenv('DB_TESTUSER'), 'newpass123');
        $this->assertThat($checkPasswordBefore['label'],
            $this->logicalOr(
                $this->stringContains('Deactivated'),
                $this->stringContains('Allowed'),
            ), 'Password check status of account was not successful after testrun'
        );

        // Reset password back again for next iteration
        $successResetPWAgain = self::$db->reset_password('PHPUnitTest', getenv('DB_TESTUSER'), password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]));
        $this->assertEquals('success', $successResetPWAgain['status'], 'Trying to get a puuid from an existing account with puuid should succeed');
    }

    /**
     * @covers DB::set_2fa_code
     * @covers DB::get_2fa_code
     * @covers DB::remove_2fa_code
     * @uses DB
     */
    public function testHandle2FA() {
        $failSet2FA = self::$db->set_2fa_code('puuidtest@example.com', 'test2fa');
        $successSet2FA = self::$db->set_2fa_code('PHPUnitTest', 'test123');
        $testOverride = self::$db->set_2fa_code('PHPUnitTest', 'test2fa');
        $failGet2FA = self::$db->get_2fa_code('puuidtest@example.com');
        $successGet2FA = self::$db->get_2fa_code('PHPUnitTest');
        
        $this->assertFalse($failSet2FA, 'Setting a 2fa code on a non-exitising account should fail');
        $this->assertTrue($successSet2FA, 'Standard procedure of setting 2fa codes should succeed');
        $this->assertTrue($testOverride, 'Standard procedure of overriding 2fa codes should succeed');
        $this->assertNull($failGet2FA, 'Getting a 2fa code on a non-exitising account should fail');
        $this->assertEquals('test2fa', $successGet2FA, 'Getting the 2fa code of an existing account should return it');

        $failRemove2FA = self::$db->remove_2fa_code('puuidtest@example.com', 'test2fa');
        $successRemove2FA = self::$db->remove_2fa_code('PHPUnitTest');
        $testRemoveOverride = self::$db->remove_2fa_code('PHPUnitTest');

        $this->assertFalse($failRemove2FA, 'Removing a 2fa code on a non-exitising account should fail');
        $this->assertTrue($successRemove2FA, 'Removal procedure of 2fa codes should succeed');
        $this->assertTrue($testRemoveOverride, 'Removal override procedure of 2fa codes should succeed');
    }
}
?>
