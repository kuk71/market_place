<?php
namespace market\model\db;

use market\app\App;
use PDO;

class ApiKey
{
    const TBL = 'api_keys';

    public static function getKeysByMpId(int $userId, int $mpId)
    {
        $query = "
            SELECT 
                api_key 
            FROM 
                " . self::TBL . " 
            WHERE 
                user_id = $userId 
                AND mp_id = $mpId";

        return App::db()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }


    public static function getMpIdsByUserId(int $userId)
    {
        $query = "
            SELECT DISTINCT 
                mp_id 
            FROM " . self::TBL . " 
            WHERE
                user_id = $userId";

        return App::db()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}