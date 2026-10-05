<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\DTO;

/**
 * Data Transfer Object for a User entity.
 */
class UserDTO
{
    public ?int $id;
    public int $officeid;
    public string $firstname;
    public ?string $lastname;
    public string $usrname;
    public ?string $password; // Password can be null when fetching data (we don't want to expose it)
    public ?string $phone;
    public string $email;
    public string $role; // 'superuser', 'staff', 'manager'

    public function __construct(
        int $officeid,
        string $firstname,
        string $usrname,
        string $email,
        string $role,
        ?string $lastname = null,
        ?string $phone = null,
        ?string $password = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->usrname = $usrname;
        $this->password = $password;
        $this->phone = $phone;
        $this->email = $email;
        $this->role = $role;
    }

    /**
     * Creates a DTO from a standard POST request array for user creation/update.
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            (int)$data['officeid'],
            $data['firstname'],
            $data['usrname'],
            $data['email'],
            $data['role'],
            $data['lastname'] ?? null,
            $data['phone'] ?? null,
            $data['password'] ?? null, // Password is handled separately (hashing)
            isset($data['id']) ? (int)$data['id'] : null
        );
    }

    public function getFullName(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }
}
