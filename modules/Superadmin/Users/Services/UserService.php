<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\Services;

use App\Modules\Superadmin\Users\DTO\UserDTO;
use App\Modules\Superadmin\Users\Models\UserModel;
use Exception;

// Manual includes for demonstration. Use a proper autoloader in a real app.
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../../../../core/Database.php';

/**
 * Service layer for User-related business logic.
 */
class UserService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Authenticates a user.
     * @param string $username
     * @param string $password
     * @return UserDTO|null The user DTO on success, null on failure.
     */
    public function authenticate(string $username, string $password): ?UserDTO
    {
        $userData = $this->userModel->findByUsername($username);

        if ($userData && password_verify($password, $userData['password'])) {
            return new UserDTO(
                (int)$userData['officeid'],
                $userData['firstname'],
                $userData['usrname'],
                $userData['email'],
                $userData['role'],
                $userData['lastname'],
                $userData['phone'],
                null, // Never return password
                (int)$userData['id']
            );
        }

        return null;
    }

    /**
     * Starts a session for the given user.
     * @param UserDTO $user
     */
    public function startUserSession(UserDTO $user): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../../../sessions_new';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }
            session_save_path($sessionPath);
            session_start();
        }

        // Regenerate session ID to prevent Session Fixation attacks
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user->id,
            'username' => $user->usrname,
            'fullname' => $user->getFullName(),
            'role' => $user->role,
            'officeid' => $user->officeid,
        ];
    }

    /**
     * Gets the currently logged-in user from the session.
     * @return array|null
     */
    public static function getCurrentUser(): ?array
    {
        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../../../sessions_new';
            if (is_dir($sessionPath)) {
                session_save_path($sessionPath);
            }
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Checks if the current user is a superuser.
     * @return bool
     */
    public static function isSuperuser(): bool
    {
        $user = self::getCurrentUser();
        return $user !== null && $user['role'] === 'superuser';
    }

    /**
     * Logs out the current user.
     */
    public function logout(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    // --- CRUD Methods (restricted to superuser) ---

    public function getAllUsers(?string $searchTerm = null): array
    {
        if (!self::isSuperuser()) {
            throw new Exception("You do not have permission to view all users.");
        }
        return $this->userModel->findAll($searchTerm);
    }

    public function getUserById(int $id): ?UserDTO
    {
        if (!self::isSuperuser()) {
            throw new Exception("You do not have permission to view user details.");
        }
        $user = $this->userModel->findById($id);
        if (!$user) return null;

        return new UserDTO(
            (int)$user['officeid'], $user['firstname'], $user['usrname'], $user['email'],
            $user['role'], $user['lastname'], $user['phone'], null, (int)$user['id']
        );
    }

    public function createUser(UserDTO $dto): int
    {
        if (!self::isSuperuser()) {
            throw new Exception("You do not have permission to create users.");
        }
        if (empty($dto->password)) {
            throw new Exception("Password is required for new users.");
        }
        if (strlen($dto->password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }
        if (!preg_match('/[A-Z]/', $dto->password) || !preg_match('/[a-z]/', $dto->password) || !preg_match('/[0-9]/', $dto->password)) {
            throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
        }
        return $this->userModel->create($dto);
    }

    public function updateUser(UserDTO $dto): bool
    {
        if (!self::isSuperuser()) {
            throw new Exception("You do not have permission to update users.");
        }
        if (!empty($dto->password)) {
            if (strlen($dto->password) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }
            if (!preg_match('/[A-Z]/', $dto->password) || !preg_match('/[a-z]/', $dto->password) || !preg_match('/[0-9]/', $dto->password)) {
                throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
            }
        }
        return $this->userModel->update($dto);
    }

    public function deleteUser(int $id): bool
    {
        if (!self::isSuperuser()) {
            throw new Exception("You do not have permission to delete users.");
        }
        // Add a check to prevent superuser from deleting themselves
        $currentUser = self::getCurrentUser();
        if ($currentUser['id'] === $id) {
            throw new Exception("You cannot delete your own account.");
        }
        return $this->userModel->delete($id);
    }
}
