<?php

namespace market\model\db;

use market\app\App;
use PDO;

class MsSupply
{
    const TBL = 'ms_supplys';

    public static function makeSentById(int $id)
    {
        $query = "UPDATE " . self::TBL . " SET is_send = 1 WHERE id = $id";

        App::db()->prepare($query)->execute();
    }

    public
    static function getAll()
    {
        $query = "
            SELECT * FROM " . self::TBL . " WHERE organization like '%Толс%' AND is_send = 0 ORDER BY id         
        ";

        return App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public
    static function addSupply(array $supply)
    {
        $query = "
            INSERT INTO " . self::TBL . "
            (ms_id, overhead_sum, overhead_distribution, agent_name, name, created, organization) VALUES
            (:ms_id, :overhead_sum, :overhead_distribution, :agent_name, :name, :created, :organization)
        ";

        App::db()->prepare($query)->execute($supply);

        return App::db()->lastInsertId();
    }

    public
    static function clear()
    {
        $query = "DELETE FROM " . self::TBL;

        return App::db()->prepare($query)->execute();
    }


}