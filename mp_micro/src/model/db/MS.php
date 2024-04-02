<?php

namespace market\model\db;

use market\app\App;
use PDO;

class MS
{
    const TBL = 'mp_ms';


    public static function getAll(int $userId)
    {
        $query = "SELECT * FROM mp_ms WHERE user_id = $userId";

        return App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveNormalize(array $normals)
    {
        $query = "
            UPDATE mp_ms
            SET
                weight_gr = :weight_gr,
                size_1_mm = :size_1_mm,
                size_2_mm = :size_2_mm,
                size_3_mm = :size_3_mm,
                clear_name = :clear_name,
                clear_code = :clear_code,
                clear_article = :clear_article
            WHERE id = :id";

        $dbp = App::db()->prepare($query);

        foreach ($normals AS $normal) {
            $dbp->execute(self::getProperty($normal));
        }
    }

    public static function saveColor(array $colors): void
    {
        $query = "
            UPDATE " .  self::TBL . "
            SET
                color = :color
            WHERE id = :id";

        $dbp = App::db()->prepare($query);

        foreach ($colors AS $color) {
            $dbp->execute(self::getProperty($color));
        }
    }

    private static function getProperty($propetyArr)
    {
        $propety = [];

        foreach ($propetyArr as $key => $value) {
            $propety[":$key"] = $value;
        }

        return $propety;
    }
}