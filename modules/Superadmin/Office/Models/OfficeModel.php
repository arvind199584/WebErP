<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Office\Models;

use App\Core\Database;
use App\Modules\Superadmin\Office\DTO\OfficeDTO;
use PDO;

/**
 * Model for direct database interaction for the Office entity.
 */
class OfficeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Finds all offices, with an optional search term.
     * @param string|null $searchTerm
     * @return array
     */
    public function findAll(?string $searchTerm = null): array
    {
        $sql = "SELECT * FROM office";
        if ($searchTerm) {
            $sql .= " WHERE UPPER(OfficeName) LIKE UPPER(:searchTerm) OR UPPER(OfficeCode) LIKE UPPER(:searchTerm) OR UPPER(ContactPerson) LIKE UPPER(:searchTerm)";
        }
        $sql .= " ORDER BY OfficeName ASC";

        $stmt = $this->db->prepare($sql);

        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a single office by its ID.
     * @param int $officeId
     * @return array|false
     */
    public function findById(int $officeId)
    {
        $sql = "SELECT * FROM office WHERE Officeid = :officeId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officeId', $officeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new office record in the database.
     * The database trigger will automatically generate the OfficeCode.
     * @param OfficeDTO $dto
     * @return int The ID of the newly created office.
     */
    public function create(OfficeDTO $dto): int
    {
        $sql = "INSERT INTO office (OfficeName, Address, Phone, email, ContactPerson, has_finance, has_store, has_workshop, has_hr)
                VALUES (:OfficeName, :Address, :Phone, :email, :ContactPerson, :has_finance, :has_store, :has_workshop, :has_hr)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':OfficeName', $dto->OfficeName);
        $stmt->bindValue(':Address', $dto->Address);
        $stmt->bindValue(':Phone', $dto->Phone);
        $stmt->bindValue(':email', $dto->email);
        $stmt->bindValue(':ContactPerson', $dto->ContactPerson);
        $stmt->bindValue(':has_finance', $dto->has_finance, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_store', $dto->has_store, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_workshop', $dto->has_workshop, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_hr', $dto->has_hr, PDO::PARAM_BOOL);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates an existing office record.
     * The database trigger will automatically update the OfficeCode if the name changes.
     * @param OfficeDTO $dto
     * @return bool
     */
    public function update(OfficeDTO $dto): bool
    {
        $sql = "UPDATE office SET
                    OfficeName = :OfficeName,
                    Address = :Address,
                    Phone = :Phone,
                    email = :email,
                    ContactPerson = :ContactPerson,
                    has_finance = :has_finance,
                    has_store = :has_store,
                    has_workshop = :has_workshop,
                    has_hr = :has_hr
                WHERE Officeid = :Officeid";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':OfficeName', $dto->OfficeName);
        $stmt->bindValue(':Address', $dto->Address);
        $stmt->bindValue(':Phone', $dto->Phone);
        $stmt->bindValue(':email', $dto->email);
        $stmt->bindValue(':ContactPerson', $dto->ContactPerson);
        $stmt->bindValue(':has_finance', $dto->has_finance, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_store', $dto->has_store, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_workshop', $dto->has_workshop, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_hr', $dto->has_hr, PDO::PARAM_BOOL);
        $stmt->bindValue(':Officeid', $dto->Officeid, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deletes an office from the database.
     * @param int $officeId
     * @return bool
     */
    public function delete(int $officeId): bool
    {
        $sql = "DELETE FROM office WHERE Officeid = :officeId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officeId', $officeId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
