<?php
namespace App\Core;

use PDO;
use PDOException;
use App\Exceptions\DatabaseException;

class Database {
    // ... (önceki kodlar aynı)
    
    /**
     * Transaction desteği ile toplu işlem
     */
    public function transaction(callable $callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw new DatabaseException("Transaction hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Sayfalama desteği
     */
    public function paginate($sql, $params = [], $perPage = 15, $page = 1) {
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) FROM', $sql, 1);
        $total = $this->query($countSql, $params)->fetchColumn();
        
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $items = $this->query($sql, $params)->fetchAll(PDO::FETCH_OBJ);
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'data' => $items
        ];
    }
}