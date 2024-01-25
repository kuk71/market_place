<?php
namespace market\model\db;

use market\app\App;
use market\model\db\SimilarProductWbOzon AS sp;
use PDO;

class ProductDownloaded
{
    const TBL = 'product_downloaded';

    public static function getColors()
    {
        $query = "SELECT DISTINCT color FROM " . self::TBL . " WHERE color <> ''";

        return App::db()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public static function saveProductProperty(array $property)
    {
        // подготовить запрос к добавлению
        $dbp = self::prepareProductList();

        foreach ($property as $prop) {
            $pr = self::getProperty($prop);

            $dbp->execute($pr);
        }
    }

    public static function saveColor(array $colors)
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

    public static function clear(int $userId, int $mpId)
    {
        $query = "DELETE FROM " . self::TBL . " WHERE user_id = $userId AND mp_id = $mpId";

        App::db()->prepare($query)->execute();
    }

    public static function getNotNormalize(int $userId, int $mpId)
    {
        $query = "
            SELECT
                id,
                weight,
                weight_unit,
                length,
                dimension_length AS dl,
                width,
                dimension_width AS dw,
                height,
                dimension_height AS dh,
                name,
                vendor_code,
                description,
                kit
            FROM
                " . self::TBL . "
            WHERE
                user_id = $userId
                AND mp_id = $mpId";

        return App::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveNormalize(array $normals)
    {
        $query = "
            UPDATE " .  self::TBL . "
            SET
                weight_gr = :weight_gr,
                size_1_mm = :size_1_mm,
                size_2_mm = :size_2_mm,
                size_3_mm = :size_3_mm,
                clear_name = :clear_name,
                clear_vendor_code = :clear_vendor_code,
                clear_description = :clear_description,
                clear_kit = :clear_kit
            WHERE id = :id";

        $dbp = App::db()->prepare($query);

        foreach ($normals AS $normal) {
           $dbp->execute(self::getProperty($normal));
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

    private static function prepareProductList()
    {
        $query = "INSERT INTO " . self::TBL . "
    (user_id, mp_id, product_mp_id, vendor_code, name, description, kit, color, img, height, dimension_height, length, dimension_length, width, dimension_width, weight, weight_unit, json)
    VALUES
        (:user_id, :mp_id, :product_mp_id, :vendor_code, :name, :description, :kit, :color, :img, :height, :dimension_height, :length, :dimension_length, :width, :dimension_width, :weight, :weight_unit, :json)
        
        ON CONFLICT (user_id, mp_id, product_mp_id) DO NOTHING;";

        // print_r($query); exit;

        return App::db()->prepare($query);
    }
}