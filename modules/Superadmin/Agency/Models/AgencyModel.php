<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Agency\Models;

use App\Core\Database;
use App\Modules\Superadmin\Agency\DTO\AgencyDTO;
use PDO;

class AgencyModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM agencies ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM agencies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(AgencyDTO $dto): int
    {
        $sql = "INSERT INTO agencies (name, account_no, ifsc, bank_name, gst_no, pan_no, address, contact_person, email)
                VALUES (:name, :account_no, :ifsc, :bank_name, :gst_no, :pan_no, :address, :contact_person, :email)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $dto->name);
        $stmt->bindValue(':account_no', $dto->account_no);
        $stmt->bindValue(':ifsc', $dto->ifsc);
        $stmt->bindValue(':bank_name', $dto->bank_name);
        $stmt->bindValue(':gst_no', $dto->gst_no);
        $stmt->bindValue(':pan_no', $dto->pan_no);
        $stmt->bindValue(':address', $dto->address);
        $stmt->bindValue(':contact_person', $dto->contact_person);
        $stmt->bindValue(':email', $dto->email);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(AgencyDTO $dto): bool
    {
        $sql = "UPDATE agencies SET
                    name = :name,
                    account_no = :account_no,
                    ifsc = :ifsc,
                    bank_name = :bank_name,
                    gst_no = :gst_no,
                    pan_no = :pan_no,
                    address = :address,
                    contact_person = :contact_person,
                    email = :email
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $dto->name);
        $stmt->bindValue(':account_no', $dto->account_no);
        $stmt->bindValue(':ifsc', $dto->ifsc);
        $stmt->bindValue(':bank_name', $dto->bank_name);
        $stmt->bindValue(':gst_no', $dto->gst_no);
        $stmt->bindValue(':pan_no', $dto->pan_no);
        $stmt->bindValue(':address', $dto->address);
        $stmt->bindValue(':contact_person', $dto->contact_person);
        $stmt->bindValue(':email', $dto->email);
        $stmt->bindValue(':id', $dto->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM agencies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
