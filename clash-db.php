<?php

class DB {
    private $dbHost     = "localhost";
    private $dbUsername = "clashuser";
    private $dbPassword = "F-))#pp!dat7g&CA";
    private $dbName     = "clashdb";
  
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
}
?>