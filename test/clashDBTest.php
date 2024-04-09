<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/clash-db.php');

class ClashDBTest extends TestCase {
    /**
     * @covers DB::check_credentials
     * @uses DB
     */
    public function testCheckCredentials() {
        $db = new DB();

        // Test with valid credentials
        $validCredentials = $db->check_credentials(getenv('DB_TESTUSER'), 'password123');
        $this->assertEquals('success', $validCredentials['status'], 'Valid credentials should return success status');
        $this->assertArrayHasKey('id', $validCredentials, 'Valid credentials should return an id key');
        $this->assertArrayHasKey('region', $validCredentials, 'Valid credentials should return a region key');
        $this->assertArrayHasKey('username', $validCredentials, 'Valid credentials should return a username key');
        $this->assertArrayHasKey('email', $validCredentials, 'Valid credentials should return an email key');
        $this->assertArrayHasKey('puuid', $validCredentials, 'Valid credentials should return a puuid key');

        $validCredentialsUsername = $db->check_credentials("PHPUnitTest", 'password123');
        $this->assertEquals('success', $validCredentialsUsername['status'], 'Valid credentials should return success status');
        $this->assertArrayHasKey('id', $validCredentialsUsername, 'Valid credentials should return an id key');
        $this->assertArrayHasKey('region', $validCredentialsUsername, 'Valid credentials should return a region key');
        $this->assertArrayHasKey('username', $validCredentialsUsername, 'Valid credentials should return a username key');
        $this->assertArrayHasKey('email', $validCredentialsUsername, 'Valid credentials should return an email key');
        $this->assertArrayHasKey('puuid', $validCredentialsUsername, 'Valid credentials should return a puuid key');

        $wrongPassword = $db->check_credentials(getenv('DB_TESTUSER'), 'wrongpassword');
        $this->assertEquals('error', $wrongPassword['status'], 'Invalid credentials should return error status');
        $this->assertEquals('Email/Username or password is invalid.', $wrongPassword['message'], 'Invalid credentials message should be correct');

        $deactivatedAccountDeactivated = $db->check_credentials("PHPUnitTest3", 'password123');
        $this->assertEquals('Deactivated', $deactivatedAccountDeactivated['label'], 'Deactivated accounts should return Deactivated on correct passwords');

        $deactivatedAccountForbidden = $db->check_credentials(getenv('DB_TESTUSER2'), 'wrongpassword123');
        $this->assertEquals('Forbidden', $deactivatedAccountForbidden['label'], 'Deactivated accounts should return Forbidden on wrong passwords');

        $wrongUsername = $db->check_credentials('test@example.com', 'wrongpassword');
        $this->assertEquals('error', $wrongUsername['status'], 'Invalid credentials should return error status');
        $this->assertEquals('Email/Username or password is invalid.', $wrongUsername['message'], 'Invalid credentials message should be correct');
    }

    /**
     * @covers DB::get_credentials_2fa
     * @uses DB
     */
    public function testGetCredentials2FA() {
        $db = new DB();

        $validEmail = $db->get_credentials_2fa(getenv('DB_TESTUSER'));
        $this->assertEquals('success', $validEmail['status'], 'Valid email should return success status');
        $this->assertArrayHasKey('id', $validEmail, 'Valid email should return an id key');
        $this->assertArrayHasKey('region', $validEmail, 'Valid email should return a region key');
        $this->assertArrayHasKey('username', $validEmail, 'Valid email should return a username key');
        $this->assertArrayHasKey('email', $validEmail, 'Valid email should return an email key');
        $this->assertArrayHasKey('puuid', $validEmail, 'Valid email should return a puuid key');
        $this->assertArrayHasKey('2fa', $validEmail, 'Valid email should return a 2fa key');

        // Test with invalid username
        $invalidUsername = $db->get_credentials_2fa('invaliduser');
        $this->assertEquals('error', $invalidUsername['status'], 'Invalid username should return error status');
        $this->assertEquals('Unable to fetch userdata, please contact an administrator.', $invalidUsername['message'], 'Invalid username message should be correct');
    }

    /**
     * @covers DB::account_exists
     * @uses DB
     */
    public function testAccountExists() {
        $db = new DB();

        $this->assertTrue($db->account_exists(getenv('DB_TESTUSER')), 'Existing email should return true');
        $this->assertTrue($db->account_exists('PHPUnitTest'), 'Existing username should return true');
        $this->assertTrue($db->account_exists(getenv('DB_TESTUSER'), 'PHPUnitTest'), 'Existing mail and username should return true');
        $this->assertFalse($db->account_exists(''), 'Non-existing email and username should return false');
    }

    /**
     * @covers DB::connect_account
     * @covers DB::disconnect_account
     * @uses DB
     */
    public function testDisConnectAccount() {
        $db = new DB();

        $this->assertFalse($db->connect_account('abcde', 'test@example.com'), 'Non-existing email and username should return false');
        $this->assertTrue($db->connect_account('abcde', 'PHPUnitTest'), 'Standard procedure of setting connect account puuid should return true');
        $this->assertTrue($db->connect_account('abcde', 'PHPUnitTest'), 'Already existing account connection should keep returning true');

        $this->assertFalse($db->disconnect_account('abcde', 'test@example.com'), 'Non-existing account should return false');
        $this->assertFalse($db->disconnect_account('hijkl', 'PHPUnitTest'), 'Wrong puuid token should return false');
        $this->assertTrue($db->disconnect_account('abcde', 'PHPUnitTest'), 'Standard procedure of removing account puuid connection should return true');
    }

    /**
     * @covers DB::set_stay_code
     * @covers DB::get_stay_code
     * @uses DB
     */
    public function testSetAndGetStayCode() {
        $db = new DB();

        $setFailure = $db->set_stay_code('abcde', 'test@example.com');
        $this->assertFalse($setFailure, 'Setting staycode of non-existing account should return false');

        $setSuccess1 = $db->set_stay_code('abcde', getenv('DB_TESTUSER'));
        $this->assertTrue($setSuccess1, 'Setting staycode via existing mail should return true');

        $getSuccess1 = $db->get_stay_code(getenv('DB_TESTUSER'));
        $this->assertEquals('abcde', $getSuccess1, 'Retrieved staycode via mail should match the one set');

        $setSuccess2 = $db->set_stay_code(NULL, 'PHPUnitTest');
        $this->assertTrue($setSuccess2, 'Setting staycode via existing username should return true');

        $getSuccess2 = $db->get_stay_code('PHPUnitTest');
        $this->assertNull($getSuccess2, 'Retrieved staycode via username should return null');

        $getFailure = $db->get_stay_code('test@example.com');
        $this->assertNull($getFailure, 'Retrieved staycode via non-existing account should return null');
    }

    /**
     * @covers DB::setPremium
     * @covers DB::getPremium
     * @uses DB
     */
    public function testSetAndGetPremium() {
        $db = new DB();

        $setPremium1 = $db->setPremium(true, 'test@example.com');
        $this->assertFalse($setPremium1, 'Setting premium of non-existing account should return false');

        $setPremium2 = $db->setPremium(true, getenv('DB_TESTUSER'));
        $this->assertTrue($setPremium2, 'Setting premium via existing mail should return true');

        $getPremium1 = $db->getPremium(getenv('DB_TESTUSER'));
        $this->assertTrue((boolean)$getPremium1, 'Retrieved premium via mail should match the one set');

        $setPremium3 = $db->setPremium(false, 'PHPUnitTest');
        $this->assertTrue($setPremium3, 'Setting premium via existing username should return true');

        $getPremium2 = $db->getPremium('PHPUnitTest');
        $this->assertFalse((boolean)$getPremium2, 'Retrieved premium via username should return false');

        $getPremium3 = $db->getPremium('test@example.com');
        $this->assertFalse($getPremium3, 'Retrieved premium via non-existing account should return false');
    }

    /**
     * @covers DB::get_data_via_stay_code
     * @uses DB::set_stay_code
     * @uses DB
     */
    public function testGetDataViaStayCode() {
        $db = new DB();

        $setStaySuccess = $db->set_stay_code('lmnop', getenv('DB_TESTUSER'));
        $existingData = $db->get_data_via_stay_code('lmnop');
        $this->assertEquals('success', $existingData['status'], 'Existing staycode should return success status');
        $this->assertArrayHasKey('id', $existingData, 'Existing staycode should return an id key');
        $this->assertArrayHasKey('region', $existingData, 'Existing staycode should return a region key');
        $this->assertArrayHasKey('username', $existingData, 'Existing staycode should return a username key');
        $this->assertArrayHasKey('email', $existingData, 'Existing staycode should return an email key');
        $this->assertArrayHasKey('puuid', $existingData, 'Existing staycode should return a puuid key');

        $nonExistingData = $db->get_data_via_stay_code('xyz789');
        $this->assertEquals('error', $nonExistingData['status'], 'Non-existing staycode should return error status');
        
        $setSuccess2 = $db->set_stay_code(NULL, getenv('DB_TESTUSER'));
    }

    /**
     * @covers DB::create_account
     * @covers DB::delete_account
     * @covers DB::force_delete_account
     * @uses DB
     */
    public function testAccountManagement() {
        $db = new DB();

        $createdAccount = $db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $this->assertEquals('success', $createdAccount['status'], 'Creating an account should return success status');
        $this->assertArrayHasKey('region', $createdAccount, 'Creating an account should return a region key');
        $this->assertArrayHasKey('username', $createdAccount, 'Creating an account should return a username key');
        $this->assertArrayHasKey('email', $createdAccount, 'Creating an account should return an email key');
        $this->assertEquals('phpunittest@example.com', $createdAccount['email'], 'Created account should have correct email');

        $overCreateAccount = $db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $this->assertEquals('error', $overCreateAccount['status'], 'Creating an already existing account should fail');

        $failDeletedAccount = $db->delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'wrongpassword');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete action with wrong password account should fail');

        $failDeletedAccount = $db->delete_account($createdAccount['id'], $createdAccount['username'], 'KR', $createdAccount['email'], 'password123');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete account action with wrong data should fail');

        $deletedAccount = $db->delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'password123');
        $this->assertEquals('success', $deletedAccount['status'], 'Deleting an account should return success status');
        $this->assertEquals('Account successfully deactivated! It will be deleted withing the next 48-72 hours.', $deletedAccount['message'], 'Deleting an account should return correct message');

        $failForceDeletedAccount = $db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], 'test@example.com', 'password123');
        $this->assertEquals('Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.', $failForceDeletedAccount['message'], 'Trying delete action with wrong account data should fail');

        $failDeletedAccount = $db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'wrongpassword');
        $this->assertEquals('error', $failDeletedAccount['status'], 'Trying delete account action with wrong data should fail');

        $forceDeletedAccount = $db->force_delete_account($createdAccount['id'], $createdAccount['username'], $createdAccount['region'], $createdAccount['email'], 'password123');
        $this->assertEquals('success', $forceDeletedAccount['status'], 'Deleting an account should return success status');
    }

    /**
     * @covers DB::verify_account
     * @uses DB
     */
    public function testVerifyAccount() {
        $db = new DB();

        $testCreatedAccount = $db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');

        $testFailVerify = $db->verify_account('wrongverifier');
        $this->assertEquals('error', $testFailVerify['status'], 'Trying to verify non-existent');
        $testSuccessVerify = $db->verify_account('verifier123');
        $this->assertEquals('success', $testSuccessVerify['status'], 'Deleting an account should return success status');

        $db->delete_account($testCreatedAccount['id'], $testCreatedAccount['username'], $testCreatedAccount['region'], $testCreatedAccount['email'], 'password123');
        $db->force_delete_account($testCreatedAccount['id'], $testCreatedAccount['username'], $testCreatedAccount['region'], $testCreatedAccount['email'], 'password123');
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
        $db = new DB();

        $testAccountForStatus = $db->create_account('testuser', 'EUW', 'phpunittest@example.com', password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]), 'verifier123');
        $checkStatusCreate = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unverified', $checkStatusCreate['label'], 'After account creation the account should be unverified');

        $db->verify_account('wrongverifier');
        $checkStatusFailVerify = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unverified', $checkStatusFailVerify['label'], 'Trying to verify a non-existent verifier should fail and not change the status');
        $db->verify_account('verifier123');
        $checkStatusSuccessVerify = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Verified', $checkStatusSuccessVerify['label'], 'Successfully verifying an account should change its status correctly');

        $db->delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'wrongpassword');
        $checkStatusFailSoftDelete = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Verified', $checkStatusFailSoftDelete['label'], 'Trying to delete an account with the wrong password should not change the status');
        $db->delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'password123');
        $checkStatusSuccessSoftDelete = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Deactivated', $checkStatusSuccessSoftDelete['label'], 'Trying to delete an account with the correct password should change its status');

        $db->force_delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'wrongpassword');
        $checkStatusFailSoftDelete = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Deactivated', $checkStatusFailSoftDelete['label'], 'Trying to force-delete an account with the wrong password should not change the status');
        
        $db->force_delete_account($testAccountForStatus['id'], $testAccountForStatus['username'], $testAccountForStatus['region'], $testAccountForStatus['email'], 'password123');
        $checkStatusSuccessSoftDelete = $db->check_status($testAccountForStatus['id'], $testAccountForStatus['username']);
        $this->assertEquals('Unknown', $checkStatusSuccessSoftDelete['label'], 'Trying to force-delete an account with the correct password remove it from being checkable');
    }

    /**
     * @covers DB::get_puuid
     * @uses DB
     */
    public function testGetPUUID() {
        $db = new DB();

        $failGetPUUID = $db->get_puuid(getenv('DB_TESTUSER'));
        $missingGetPUUID = $db->get_puuid('puuidtest@example.com');
        $successGetPUUID = $db->get_puuid('DasNerdwork');

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
        $db = new DB();

        $failSetCode = $db->set_reset_code('puuidtest@example.com', 'reset123');
        $successSetCode = $db->set_reset_code('PHPUnitTest', 'reset123');
        $testOverride = $db->set_reset_code('PHPUnitTest', 'reset345');
        $failGetCode = $db->get_reset_code('puuidtest@example.com');
        $successGetCode = $db->get_reset_code('PHPUnitTest');
        
        $this->assertFalse($failSetCode, 'Setting a reset code on a non-exitising account should fail');
        $this->assertTrue($successSetCode, 'Standard procedure of setting reset codes should succeed');
        $this->assertTrue($testOverride, 'Standard procedure of overriding reset codes should succeed');
        $this->assertFalse($failGetCode, 'Getting a reset code on a non-exitising account should fail');
        $this->assertArrayHasKey('resetter', $successGetCode, 'Getting the success code of an existing account should return its resetter');
        $this->assertArrayHasKey('timestamp', $successGetCode, 'Getting the success code of an existing account should return its timestamp');

        $failCheckCode = $db->check_reset_code('wrongresetter');
        $successCheckCode = $db->check_reset_code($successGetCode['resetter']);

        $this->assertEquals('error', $failCheckCode['status'], 'Trying to get data via a non-existing resetter should return an error');
        $this->assertEquals('success', $successCheckCode['status'], 'Trying to get data via an existing resetter should return successfully');
    }

    /**
     * @covers DB::reset_password
     * @uses DB::check_credentials
     * @uses DB
     */
    public function testResetPW() {
        $db = new DB();

        $checkPasswordBefore = $db->check_credentials(getenv('DB_TESTUSER'), 'password123');
        $this->assertThat($checkPasswordBefore['label'],
            $this->logicalOr(
                $this->stringContains('Deactivated'),
                $this->stringContains('Allowed'),
            ), 'Password check status of account was not successful before testrun'
        );

        $failResetPW = $db->reset_password('wronguser', 'wronguser@example.com', password_hash('newpass123', PASSWORD_BCRYPT, ['cost' => 11]));
        $successResetPW = $db->reset_password('PHPUnitTest', getenv('DB_TESTUSER'), password_hash('newpass123', PASSWORD_BCRYPT, ['cost' => 11]));

        $this->assertEquals('error', $failResetPW['status'], 'Resetting a password of a non-existing account did not result in an error');
        $this->assertEquals('success', $successResetPW['status'], 'Resetting a password did not succeed although it should have');

        $checkPasswordAfter = $db->check_credentials(getenv('DB_TESTUSER'), 'newpass123');
        $this->assertThat($checkPasswordBefore['label'],
            $this->logicalOr(
                $this->stringContains('Deactivated'),
                $this->stringContains('Allowed'),
            ), 'Password check status of account was not successful after testrun'
        );

        // Reset password back again for next iteration
        $successResetPWAgain = $db->reset_password('PHPUnitTest', getenv('DB_TESTUSER'), password_hash('password123', PASSWORD_BCRYPT, ['cost' => 11]));
        $this->assertEquals('success', $successResetPWAgain['status'], 'Trying to get a puuid from an existing account with puuid should succeed');
    }

    /**
     * @covers DB::set_2fa_code
     * @covers DB::get_2fa_code
     * @covers DB::remove_2fa_code
     * @uses DB
     */
    public function testHandle2FA() {
        $db = new DB();

        $failSet2FA = $db->set_2fa_code('puuidtest@example.com', 'test2fa');
        $successSet2FA = $db->set_2fa_code('PHPUnitTest', 'test123');
        $testOverride = $db->set_2fa_code('PHPUnitTest', 'test2fa');
        $failGet2FA = $db->get_2fa_code('puuidtest@example.com');
        $successGet2FA = $db->get_2fa_code('PHPUnitTest');
        
        $this->assertFalse($failSet2FA, 'Setting a 2fa code on a non-exitising account should fail');
        $this->assertTrue($successSet2FA, 'Standard procedure of setting 2fa codes should succeed');
        $this->assertTrue($testOverride, 'Standard procedure of overriding 2fa codes should succeed');
        $this->assertNull($failGet2FA, 'Getting a 2fa code on a non-exitising account should fail');
        $this->assertEquals('test2fa', $successGet2FA, 'Getting the 2fa code of an existing account should return it');

        $failRemove2FA = $db->remove_2fa_code('puuidtest@example.com', 'test2fa');
        $successRemove2FA = $db->remove_2fa_code('PHPUnitTest');
        $testRemoveOverride = $db->remove_2fa_code('PHPUnitTest');

        $this->assertFalse($failRemove2FA, 'Removing a 2fa code on a non-exitising account should fail');
        $this->assertTrue($successRemove2FA, 'Removal procedure of 2fa codes should succeed');
        $this->assertTrue($testRemoveOverride, 'Removal override procedure of 2fa codes should succeed');
    }
}
?>
