<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

class DB {
    private $dbHost;
    private $dbUsername;
    private $dbPassword;
    private $dbName;
    private $db;

    /**
     * @codeCoverageIgnore
     */
    public function __construct() {
        $this->dbHost = getenv('HOST');
        $this->dbUsername = getenv('DB_USER');
        $this->dbPassword = getenv('DB_PW');
        $this->dbName = getenv('DB_NAME');

        if(!isset($this->db)){
            // Connect to the database
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);
            $conn->set_charset('utf8mb4'); // for sql injection prevention
            if($conn->connect_error){
                die("Failed to connect with MySQL: " . $conn->connect_error);
            }else{
                $this->db = $conn;
            }
        }
    }
  
    public function check_credentials($mailorname = '', $password = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT id, region, username, email, password, status, puuid, 2fa FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT id, region, username, email, password, status, puuid, 2fa FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            
            $row = $result->fetch_assoc(); // Fetch returnvalue to an array ($row) of mysql query above

            if ($row['status'] == '1' || $row['status'] == '2') {
                if (password_verify($password, $row['password'])) {
                    return array('status' => 'success', 'id' => $row['id'], 'region' => $row['region'], 'username' => $row['username'], 'email' => $row['email'], 'puuid' => $row['puuid'], 'label' => 'Allowed');
                }
                return array('status' => 'error', 'message' => 'Email/Username or password is invalid.', 'label' => 'Forbidden'); // The Password decrypt was unsuccessful
            } else { // The Users status is set to 0 (deactivated account)
                if (password_verify($password, $row['password'])) {
                    return array('status' => 'error', 'message' => 'Your account was deactivated. If you did not take this action please reach out to an administrator.', 'label' => 'Deactivated'); // Only Disabled
                } else {
                    return array('status' => 'error', 'message' => 'Your account was deactivated. If you did not take this action please reach out to an administrator.', 'label' => 'Forbidden'); // Unauthorized & Disabled
                }
            }
        }
        return array('status' => 'error', 'message' => 'Email/Username or password is invalid.', 'label' => 'Unknown'); // Cannot find email/username in database
    }

    public function get_credentials_2fa($mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT id, region, username, email, status, puuid, 2fa FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT id, region, username, email, status, puuid, 2fa FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            
            $row = $result->fetch_assoc(); // Fetch returnvalue to an array ($row) of mysql query above
 
            $twofa = $row['2fa'] != NULL ? $twofa = 'true' : $twofa = 'false';

            return array('status' => 'success', 'id' => $row['id'], 'region' => $row['region'], 'username' => $row['username'], 'email' => $row['email'], 'puuid' => $row['puuid'], '2fa' => $twofa);
        }
        return array('status' => 'error', 'message' => 'Unable to fetch userdata, please contact an administrator.'); // The Password decrypt was unsuccessful
    }

    public function account_exists($mailorname = '', $username = '') {
        $sql = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        if($username != ''){
            $sql->bind_param('ss', $mailorname, $username);
        } else {
            $sql->bind_param('ss', $mailorname, $mailorname);
        }
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            return true;
        } else {
            return false;
        }
    } 

    public function connect_account($puuid, $mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET puuid = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET puuid = ? WHERE username = ?");
        }
        $sql->bind_param('ss', $puuid, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    } 

    public function set_stay_code($staycode, $mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET staycode = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET staycode = ? WHERE username = ?");
        }
        $sql->bind_param('ss', $staycode, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_stay_code($mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT staycode FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT staycode FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
    
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            return $row['staycode'];
        } else {
            return null; // Return null if no staycode is found
        }
    }
    
    public function setPremium($isPremium, $mailorname) {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET is_premium = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET is_premium = ? WHERE username = ?");
        }
        $sql->bind_param('is', $isPremium, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;
    
        if ($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    }

    public function getPremium($mailorname) {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT is_premium FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT is_premium FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['is_premium'];
        } else {
            return false;
        }
    }

    public function get_data_via_stay_code($staycode) {
        $sql = $this->db->prepare("SELECT id, region, username, email, puuid FROM users WHERE staycode = ?");
        $sql->bind_param('s', $staycode);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            return array('status' => 'success', 'id' => $row['id'], 'region' => $row['region'], 'username' => $row['username'], 'email' => $row['email'], 'puuid' => $row['puuid']);
        } else {
            return array('status' => 'error');
        }
    } 

    public function disconnect_account($puuid, $username = '') {
        $sql = $this->db->prepare("UPDATE users SET puuid = NULL WHERE username = ? AND puuid = ?");
        $sql->bind_param('ss', $username, $puuid);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result > 0 && $this->account_exists($username)) {
            return true;
        } else {
            return false;
        }
    } 

    public function create_account($username, $region, $email, $password, $verifier) {
        if(!$this->account_exists($email)){
            $sql = $this->db->prepare("INSERT INTO users (username, region, email, password, verifier, status) VALUES (?, ?, ?, ?, ?, 2)"); 
            $sql->bind_param('sssss', $username, $region, $email, $password, $verifier);
            $sql->execute();
            $result = $sql->get_result();

            if(is_numeric($sql->insert_id)){
                return array('status' => 'success', 'message' => 'Account successfully created!', 'id' => $sql->insert_id, 'region' => $region, 'username' => $username, 'email' => $email);
            } else {
                // @codeCoverageIgnoreStart
                return array('status' => 'error', 'message' => 'Unable to create account.');
                // @codeCoverageIgnoreEnd
            }
        } else {
            return array('status' => 'error', 'message' => 'Unable to create account.');
        }
    }

    public function verify_account($verifier = '') {
        $sql = $this->db->prepare("UPDATE users SET status = '1' WHERE verifier = ?");
        $sql->bind_param('s', $verifier);
        $sql->execute();
        $result = $sql->affected_rows;

        $sql = $this->db->prepare("UPDATE users SET verifier = NULL WHERE verifier = ?");
        $sql->bind_param('s', $verifier);
        $sql->execute();
        $result2 = $sql->affected_rows;

        if($result > 0 && $result2 > 0) {
            return array('status' => 'success', 'message' => 'Account successfully verified! You may now <a href="/login">login</a>.');
        } else {
            return array('status' => 'error', 'message' => 'Unable to verify account.');
        }
    } 

    public function get_puuid($mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT puuid FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT puuid FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            return array('status' => 'success', 'puuid' => $row['puuid']);
        } else {
            return array('status' => 'error', 'puuid' => NULL);
        }
    } 

    public function check_status($id, $username) {
        $sql = $this->db->prepare("SELECT status FROM users WHERE id = ? AND username = ?");
        $sql->bind_param('ss', $id, $username);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            
            $row = $result->fetch_assoc();

            switch ($row['status']) {
                case "0":
                    return array('status' => 'error', 'message' => 'This account has been deactivated.', 'label' => 'Deactivated');
                case "1":
                    return array('status' => 'success', 'message' => '', 'label' => 'Verified');
                case "2":
                    return array('status' => 'error', 'message' => 'Your account has not been verified yet. Please check your mails (including spam folder) to be able to use all functionalities.', 'label' => 'Unverified');
                default:
                // @codeCoverageIgnoreStart
                    return array('status' => 'error', 'message' => 'Unknown account status. Please contact an administrator.', 'label' => 'Unknown');
                // @codeCoverageIgnoreEnd
            }
        } else {
            return array('status' => 'error', 'message' => 'Unknown account status. Please contact an administrator.', 'label' => 'Unknown');
        }
    }

    public function set_reset_code($mailorname = '', $reset = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET resetdate = ?, resetter = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET resetdate = ?, resetter = ? WHERE username = ?");
        }
        $currentTime = time();
        $sql->bind_param('sss', $currentTime, $reset, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_reset_code($mailorname = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT resetter, resetdate FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT resetter, resetdate FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            $row = $result->fetch_assoc();
            return array('resetter' => $row['resetter'], 'timestamp' => $row['resetdate']);
        } else {
            return false;
        }
    }

    public function check_reset_code($reset = '') {
        $sql = $this->db->prepare("SELECT id, username, email FROM users WHERE resetter = ?");
        $sql->bind_param('s', $reset);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {

            $row = $result->fetch_assoc();

            return array('status' => 'success', 'message' => '', 'id' => $row['id'], 'username' => $row['username'], 'email' => $row['email']);
        } else {
            return array('status' => 'error', 'message' => 'Cannot find account corresponding to code.');
        }
    }

    public function reset_password($username = '', $email = '', $password = '') {
        $sql = $this->db->prepare("UPDATE users SET resetdate = NULL, password = ?, status = '1', resetter = NULL WHERE username = ? AND email = ?");
        $sql->bind_param('sss', $password, $username, $email);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result > 0) {
            return array('status' => 'success', 'message' => 'Successfully reset password.');
        } else {
            return array('status' => 'error', 'message' => 'Unable to reset password.');
        }
    } 

    public function delete_account($id, $username, $region, $email, $password) { // Accounts will be set to inactive for 48-72 hours and then automatically deleted by a mysql event
        $check = $this->check_credentials($email, $password);
        $currentTime = time();
        if($check['status'] == 'success'){
            $sql = $this->db->prepare("UPDATE users SET deldate = ?, status = '0' WHERE id = ? AND username = ? AND region = ? AND email = ? AND (status = '1' OR status = '2')");
            $sql->bind_param('issss', $currentTime, $id, $username, $region, $email);
            $sql->execute();
            $result = $sql->affected_rows;

            if($result > 0) {
                return array('status' => 'success', 'message' => 'Account successfully deactivated! It will be deleted withing the next 48-72 hours.');
            } else {
                return array('status' => 'error', 'message' => 'Unable to delete account. Please contact an administrator.');
            }
        } else {
            return array('status' => 'error', 'message' => 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.');
        }
    }

    public function force_delete_account($id, $username, $region, $email, $password) {
        $check = $this->check_credentials($email, $password);
        if($check['label'] == 'Deactivated'){
            $sql = $this->db->prepare("DELETE FROM users WHERE id = ? AND username = ? AND region = ? AND email = ?");
            $sql->bind_param('isss', $id, $username, $region, $email);
            $sql->execute();
            $result = $sql->affected_rows;

            if($result > 0) {
                return array('status' => 'success', 'message' => 'Account successfully force-deleted!');
            } else {
                // @codeCoverageIgnoreStart
                return array('status' => 'error', 'message' => 'Unable to delete account. Please contact an administrator.');
                // @codeCoverageIgnoreEnd
            }
        } else {
            return array('status' => 'error', 'message' => 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.');
        }
    }

    public function set_2fa_code($mailorname, $code) {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET 2fa = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET 2fa = ? WHERE username = ?");
        }
        $sql->bind_param('ss', $code, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_2fa_code($mailorname) {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("SELECT 2fa FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT 2fa FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            $row = $result->fetch_assoc();
            return $row['2fa'];
        } else {
            return null;
        }
    }

    public function remove_2fa_code($mailorname) {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET 2fa = NULL WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET 2fa = NULL WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result >= 0 && $this->account_exists($mailorname)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function addPoints($username, $points) {
        $sql = $this->db->prepare("UPDATE minigames SET points = points + ? WHERE username = ?");
        $sql->bind_param('is', $points, $username);
        if ($sql->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPoints($username) {
        $sql = $this->db->prepare("SELECT points FROM minigames WHERE username = ?");
        $sql->bind_param('s', $username);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows) {
            $row = $result->fetch_assoc();
            return $row['points'];
        } else {
            return false;
        }
    }
    
    /**
     * @codeCoverageIgnore
     */
    public function removePoints($username, $points) {
        $sql = $this->db->prepare("UPDATE minigames SET points = GREATEST(0, points - ?) WHERE username = ?");
        $sql->bind_param('is', $points, $username);
        if ($sql->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTopPlayers() {
        $sql = "SELECT username, points FROM minigames ORDER BY points DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
    
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result) {
                $topPlayers = array();
                while ($row = $result->fetch_assoc()) {
                    $topPlayers[] = array('username' => $row['username'], 'points' => $row['points']);
                }
                return $topPlayers;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    

    /** MySQL Event that runs every 24 hours and deletes any deactivated account (status = 0) which has been deactivated more than 2 days ago (deldate < DATE_SUB(NOW(), INTERVAL 2 DAY))
     *  ==> Accounts stay deactivated min. 48 hours - max. 72 hours
     * 
     * DELIMITER $$
     * CREATE EVENT auto_delete_deactivated_accounts
     *   ON SCHEDULE EVERY 24 HOUR
     *   ON COMPLETION PRESERVE
     *   DO BEGIN
     *     DELETE FROM users WHERE status = "0" AND deldate < DATE_SUB(NOW(), INTERVAL 2 DAY);
     *   END;
     * $$;
     * 
     * Ex.
     * | id | username    | region | email                | password                                                     | status | verifier | deldate    |
     * | 29 | todelete3   | EUW    | test@test.test       | $2y$11$AYITaa2B8hT7Zis2YbLC7OKkY.f0ZPPI/NltJPoOTex7T3ty2qVei |      0 | NULL     | 1666718981 |
     * 
     * To restore an account: UPDATE users SET status = "1", deldate = NULL WHERE email = "test@test.test";
     */
}
?>