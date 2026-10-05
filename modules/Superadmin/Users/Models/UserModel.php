<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\Models;

use App\Core\Database;
use App\Modules\Superadmin\Users\DTO\UserDTO;
use PDO;

/**
 * Model for direct database interaction for the User entity.
 */
class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Finds a single user by their username.
     * @param string $username
     * @return array|false
     */
    public function findByUsername(string $username)
    {
        $sql = "SELECT * FROM users WHERE usrname = :usrname";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usrname', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a single user by their ID.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $sql = "SELECT id, officeid, firstname, lastname, usrname, phone, email, role FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Finds all users, with an optional search term.
     * @param string|null $searchTerm
     * @return array
     */
    public function findAll(?string $searchTerm = null): array
    {
        $sql = "SELECT u.id, u.officeid, o.OfficeName, u.firstname, u.lastname, u.usrname, u.email, u.role
                FROM users u
                JOIN office o ON u.officeid = o.Officeid";

        if ($searchTerm) {
            $sql .= " WHERE UPPER(u.firstname) LIKE UPPER(:searchTerm)
                      OR UPPER(u.lastname) LIKE UPPER(:searchTerm)
                      OR UPPER(u.usrname) LIKE UPPER(:searchTerm)
                      OR UPPER(u.email) LIKE UPPER(:searchTerm)";
        }

        $sql .= " ORDER BY u.firstname, u.lastname ASC";

        $stmt = $this->db->prepare($sql);

        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new user record with a hashed password.
     * @param UserDTO $dto
     * @return int The ID of the newly created user.
     */
    public function create(UserDTO $dto): int
    {
        $hashedPassword = password_hash($dto->password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (officeid, firstname, lastname, usrname, password, phone, email, role)
                VALUES (:officeid, :firstname, :lastname, :usrname, :password, :phone, :email, :role)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':firstname', $dto->firstname);
        $stmt->bindValue(':lastname', $dto->lastname);
        $stmt->bindValue(':usrname', $dto->usrname);
        $stmt->bindValue(':password', $hashedPassword);
        $stmt->bindValue(':phone', $dto->phone);
        $stmt->bindValue(':email', $dto->email);
        $stmt->bindValue(':role', $dto->role);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates an existing user record.
     * @param UserDTO $dto
     * @return bool
     */
    public function update(UserDTO $dto): bool
    {
        $sql = "UPDATE users SET
                    officeid = :officeid,
                    firstname = :firstname,
                    lastname = :lastname,
                    usrname = :usrname,
                    phone = :phone,
                    email = :email,
                    role = :role
                WHERE id = :id";

        // Conditionally add password to the update query if it's provided
        if (!empty($dto->password)) {
            $sql = str_replace("WHERE id = :id", ", password = :password WHERE id = :id", $sql);
        }

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':firstname', $dto->firstname);
        $stmt->bindValue(':lastname', $dto->lastname);
        $stmt->bindValue(':usrname', $dto->usrname);
        $stmt->bindValue(':phone', $dto->phone);
        $stmt->bindValue(':email', $dto->email);
        $stmt->bindValue(':role', $dto->role);
        $stmt->bindValue(':id', $dto->id, PDO::PARAM_INT);

        if (!empty($dto->password)) {
            $hashedPassword = password_hash($dto->password, PASSWORD_BCRYPT);
            $stmt->bindValue(':password', $hashedPassword);
        }

        return $stmt->execute();
    }

    /**
     * Deletes a user from the database.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
