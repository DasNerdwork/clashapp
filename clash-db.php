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
            $sql = $this->db->prepare("SELECT id, username, password, status FROM users WHERE email = ?");
        } else {
            $sql = $this->db->prepare("SELECT id, username, password, status FROM users WHERE username = ?");
        }
        $sql->bind_param('s', $mailorname);
        $sql->execute();
        $result = $sql->get_result();
        
        if($result->num_rows) {
            
            $entry = $result->fetch_assoc(); // Fetch returnvalue of mysql query above
 
            if ('1' == $entry['status']) {
                if (password_verify($password, $entry['password'])) {
                    return array('status' => 'success', 'id' => $entry['id'], 'username' => $entry['username']);
                }
                return array('status' => 'error', 'message' => 'Email or password is invalid.'); // The Password decrypt was unsuccessful
            }
 
            return array('status' => 'error', 'message' => 'Your account is not activated yet.'); // The Users status is set to 0 (deactivated account)
        }
        return array('status' => 'error', 'message' => 'The given account does not exist.'); // Cannot find email in database
    }

    public function account_exists($email = '') {
        $sql = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $sql->bind_param('s', $email);
        $sql->execute();
        $result = $sql->get_result();

        // print_r($result);die();

        if($result->num_rows) {
            return true;
        } else {
            return false;
        }
    } 

// CREATE TABLE 'users' (  'id' int(11) NOT NULL AUTO_INCREMENT, 'username' varchar(255) NOT NULL, 'region' varchar(4) NOT NULL, 'email' varchar(255) NOT NULL,  'password' varchar(255) NOT NULL,  'status' int(1) NOT NULL DEFAULT 1 COMMENT '2=Unverified|1=Active|0=Inactive',  PRIMARY KEY ('id') ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    public function create_account($username, $region, $email, $password, $verifier) {
        //ALTER TABLE `personen` ADD `hash` VARCHAR(255) NOT NULL AFTER `fk_laden`;
        $sql = $this->db->prepare("INSERT INTO users (username, region, email, password, verifier, status) VALUES (?, ?, ?, ?, ?, 2)"); // INSERT INTO 'users' ('username', 'email', 'password', 'status') VALUES ('John Doe', 'john.doe@example.com', MD5('123456'), 1), ('Sam Doe', 'sam.doe@example.com', MD5('123456'), 0);
        $sql->bind_param('sssss', $username, $region, $email, $password, $verifier);
        $sql->execute();
        $result = $sql->get_result();

        print_r($sql);

        if(is_numeric($sql->insert_id)){
            return array('status' => 'success', 'message' => 'Account successfully created!', 'id' => $sql->insert_id);
        }else{
            return array('status' => 'error', 'message' => 'Unable to create account.');
        }
        // print_r($result);die();

        // if($result->num_rows) {
        //     return true;
        // } else {
        //     return false;
        // }
    }
}
?>