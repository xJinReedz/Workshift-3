<?php
/**
 * Base Model Class
 */

namespace WorkShift\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}
