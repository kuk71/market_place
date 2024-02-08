<?php
namespace market\model\db;

use market\app\App;
use PDO;

class MpLinkType
{
    const TBL = 'mp_link_types';

    public static function getLinkTypeIdsByMpIds(array $mpIds)
    {
        $mpIds = array_map('intval', $mpIds);
        $mpIds = implode(",", $mpIds);

        $query = "
            SELECT DISTINCT 
                id 
            FROM " . self::TBL . " 
            WHERE 
                is_all = 1
                AND mp_first_id IN ($mpIds) 
                AND mp_second_id IN ($mpIds)";

        return App::db()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public static function getMpIdByLinkId(int $id)
    {
        $query = "SELECT mp_first_id, mp_second_id FROM  " . self::TBL . " WHERE id = $id";

        $queryRes = App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return (count($queryRes) === 0) ? false : $queryRes[0];
    }
}