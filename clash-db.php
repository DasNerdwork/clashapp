<?php

class DB {
    private $dbHost     ***REMOVED***
    private $dbUsername = "***REMOVED***";
    private $dbPassword = "***REMOVED***";
    private $dbName     = "***REMOVED***";
  
    public function __construct() {
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
            $sql = $this->db->prepare("SELECT id, region, username, email, password, status FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT id, region, username, email, password, status FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            
            $row = $result->fetch_assoc(); // Fetch returnvalue to an array ($row) of mysql query above
 
            if ($row['status'] == '1' || $row['status'] == '2') {
                if (password_verify($password, $row['password'])) {
                    return array('status' => 'success', 'id' => $row['id'], 'region' => $row['region'], 'username' => $row['username'], 'email' => $row['email']);
                }
                return array('status' => 'error', 'message' => 'Email or password is invalid.'); // The Password decrypt was unsuccessful
            }
 
            return array('status' => 'error', 'message' => 'Your account was deactivated. If you did not take this action please reach out to an administrator.'); // The Users status is set to 0 (deactivated account)
        }
        return array('status' => 'error', 'message' => 'The given account does not exist.'); // Cannot find email in database
    }

    public function account_exists($email = '', $username = '') {
        $sql = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $sql->bind_param('ss', $email, $username);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            return true;
        } else {
            return false;
        }
    } 

    public function create_account($username, $region, $email, $password, $verifier) {
        $sql = $this->db->prepare("INSERT INTO users (username, region, email, password, verifier, status) VALUES (?, ?, ?, ?, ?, 2)"); 
        $sql->bind_param('sssss', $username, $region, $email, $password, $verifier);
        $sql->execute();
        $result = $sql->get_result();

        if(is_numeric($sql->insert_id)){
            return array('status' => 'success', 'message' => 'Account successfully created!', 'id' => $sql->insert_id);
        }else{
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

    public function check_status($id, $username) {
        $sql = $this->db->prepare("SELECT status FROM users WHERE id = ? AND username = ?");
        $sql->bind_param('ss', $id, $username);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows) {
            
            $row = $result->fetch_assoc();

            switch ($row['status']) {
                case "0":
                    return array('status' => 'deactivated', 'message' => 'This account has been deactivated.');
                case "1":
                    return array('status' => 'verified', 'message' => '');
                case "2":
                    return array('status' => 'unverified', 'message' => 'Your account has not been verified yet. Please check your mails (including spam folder) to be able to use all functionalities.');
                default:
                    return array('status' => 'unknown', 'message' => 'Unknown account status. Please contact an administrator.');
            }
        } else {
            return array('status' => 'unknown', 'message' => 'Cannot find user in database. Please contact an administrator.');
        }
    }

    public function set_reset_code($mailorname = '', $reset = '') {
        if(str_contains($mailorname, '@')){
            $sql = $this->db->prepare("UPDATE users SET resetdate = ?, resetter = ? WHERE email = ?");
        } else {
            $sql = $this->db->prepare("UPDATE users SET resetdate = ?, resetter = ? WHERE username = ?");
        }
        $sql->bind_param('sss', time(), $reset, $mailorname);
        $sql->execute();
        $result = $sql->affected_rows;

        if($result > 0) {
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
            return;
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

    public function reset_password($id = '', $username = '', $email = '', $password = '') {
        $sql = $this->db->prepare("UPDATE users SET resetdate = NULL, password = ?, status = '1', resetter = NULL WHERE id = ? AND username = ? AND email = ?");
        $sql->bind_param('siss', $password, $id, $username, $email);
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
        if($check['status'] == 'success'){
            $sql = $this->db->prepare("UPDATE users SET deldate = ?, status = '0' WHERE id = ? AND username = ? AND region = ? AND email = ? AND (status = '1' OR status = '2')");
            $sql->bind_param('issss', time(), $id, $username, $region, $email);
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