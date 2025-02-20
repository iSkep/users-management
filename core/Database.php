<?php

class Database
{
    private mysqli $conn;

    public function __construct(string $host, string $user, string $password, string $dbname)
    {
        $this->conn = new mysqli($host, $user, $password, $dbname);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function executeQuery(string $query, string $types = "", array $params = [], bool $fetchAll = false)
    {
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("Database error: " . $this->conn->error);
        }

        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        if ($fetchAll) {
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function getLastInsertId(): int
    {
        return $this->conn->insert_id;
    }

    public function close()
    {
        $this->conn->close();
    }
}
