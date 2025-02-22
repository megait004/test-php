<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT u.*, ur.role FROM tbUser u
                  LEFT JOIN tbUserInRole ur ON u.username = ur.username
                  WHERE u.username = :username AND u.active = true";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function register($username, $password) {
        try {
            $this->conn->beginTransaction();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO tbUser (username, password, active)
                     VALUES (:username, :password, true)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->execute();

            $query = "INSERT INTO tbUserInRole (username, role) VALUES (:username, 'Member')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>