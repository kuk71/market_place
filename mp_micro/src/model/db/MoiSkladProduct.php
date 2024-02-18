<?php

namespace market\model\db;

use market\app\App;
use PDO;

class MoiSkladProduct
{
    const TBL = 'ms_products';

    public static function addNewUuid(string $uuidNew, int $productId)
    {
        $query = "UPDATE " . self::TBL . " SET ms_id_new = :ms_id_new WHERE id = $productId";

        $prepare = App::db()->prepare($query);
        $prepare->execute(["ms_id_new" => $uuidNew]);
    }

    public static function getAll()
    {
        $query = "
            SELECT * FROM " . self::TBL . " WHERE \"pathName\" like 'Толстой%' AND ms_id_new IS NULL 
            UNION
            SELECT DISTINCT
                MP.* 
            FROM public.ms_supplys AS MS
            JOIN ms_supply_contents AS MSC
                ON (MS.ms_id = MSC.supply_uuid)
            JOIN ms_products AS MP
                ON (MSC.uuid = MP.ms_id)
            WHERE 
                organization like '%Толстой%'
                AND MP.\"pathName\" like '%Шев%'
                AND ms_id_new IS NULL
            
            
            ORDER BY id ASC
        ";

        return App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addProduct(array $product)
    {
        $query = "
            INSERT INTO " . self::TBL . "
            (ms_id, name, code, article, brand, \"pathName\", code128, weight, length, width, height, user_id) VALUES
            (:ms_id, :name, :code, :article, :brand, :pathName, :code128, :weight, :length, :width, :height, :user_id)
        ";

        return App::db()->prepare($query)->execute($product);
    }

    public static function clear(int $userId)
    {
        $query = "DELETE FROM " . self::TBL . " WHERE user_id = $userId";

        return App::db()->prepare($query)->execute();
    }


}