<?php

namespace App\Models;

use System\Database;
use PDO;

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getNavigationItems()
    {
        try {
            $stmt = $this->db->query("SELECT fullname, image_url, status, job, role FROM users WHERE role = 'admin' OR role = 'superadmin'");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

     public function getAllUsers()
    {
        $stmt = $this->db->query("SELECT * FROM users");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserById($userId)
    {
        $query = $this->db->query("SELECT * FROM users WHERE id = :id");
        $query->execute(['id' => $userId]);

        return $query->fetchObject(); 
    }

    
    public function deleteUser($userId)
    {
        
        if ($_SESSION["role"] === "admin" || $_SESSION["role"] === "superadmin") {
            $query = $this->db->query("DELETE FROM users WHERE id = :id");
            return $query->execute(['id' => $userId]);
        }

        return false;
    }

    public function getUserByCredentials($login, $password)
    {
        $sql = "SELECT * FROM users WHERE login = :login AND status = 'active' LIMIT 1";

        try {
            $stmt = $this->db->query($sql);
            $stmt->bindValue(':login', $login, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && password_verify($password, $result['password'])) {
                return $result;
            }
            return null;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}
?>