<?php

namespace market\model\db;

use market\app\App;
use PDO;

class User
{
    const TBL = 'users';

    public static function isUserById(int $userId)
    {
        $query = "SELECT id FROM " . self::TBL . " WHERE id = $userId";

        $queryRes = App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return (count($queryRes) === 1);
    }
}