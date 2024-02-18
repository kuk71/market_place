<?php

namespace market\model\db;

use market\app\App;
use PDO;

class MsSupplyContent
{
    const TBL = 'ms_supply_contents';

    public static function getBySupplayId(string $supplyUuid)
    {
        $query = "
            SELECT
                MSC.*,
                MSP.ms_id_new
            FROM " . self::TBL . " MSC
            JOIN " . MoiSkladProduct::TBL . " MSP
                ON (MSC.uuid = MSP.ms_id)
            WHERE
                MSC.supply_uuid = '$supplyUuid'
                
        ";

        return App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addContent(array $content)
    {
        $query = "
            INSERT INTO " . self::TBL . "
            (supply_uuid, uuid, price, quantity) VALUES
            (:supply_uuid, :uuid, :price, :quantity)
        ";

        return App::db()->prepare($query)->execute($content);
    }

    public static function clear()
    {
        $query = "DELETE FROM " . self::TBL;

        return App::db()->prepare($query)->execute();
    }


}