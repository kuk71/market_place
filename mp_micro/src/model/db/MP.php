<?php
namespace market\model\db;

use market\app\App;
use PDO;

class MP
{
    const TBL = 'mp';


    /**
     * Возвращает имя маркет плейса
     *
     * @param int $id - Id мркет плейса
     * @return string|bool
     */
    public static function getNameById(int $id)
    {
        $query = "SELECT name FROM  " . self::TBL . " WHERE id = $id";

        $queryRes = App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return (count($queryRes) > 0) ? $queryRes[0]['name'] : false;
    }
}