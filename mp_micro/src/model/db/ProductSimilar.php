<?php

namespace market\model\db;

use market\app\App;
use market\exception\MarketException;
use PDO;

class ProductSimilar
{
    const TBL = 'product_similar';

    public static function createLink(string $query, string $field, int $userId, int $linkTypeId)
    {
        $query = "UPDATE " . self::TBL . " SET $field = 1 
            WHERE 
                (first_mp_product_id, second_mp_product_id) IN ($query)
                AND user_id = $userId
                AND mp_link_type_id = $linkTypeId
            ";

        App::db()->prepare($query)->execute();
    }

    public static function linkSum(array $fields, int $userId, int $linkTypeId)
    {
        $fieldSum = implode(' + ', $fields);
        $query = "UPDATE " . self::TBL . " SET number_equal_fields = ($fieldSum) WHERE user_id = $userId AND mp_link_type_id = $linkTypeId";

        App::db()->prepare($query)->execute();
    }

    public static function getProductForSimilar(int $userId, int $linkTypeId, int $limit, int $start)
    {
        $query = "
            SELECT
                PS.id,
                FM.vendor_code AS first_vendor_code,
                FM.name AS first_name,
                FM.description AS first_description,
                FM.kit AS first_kit,
                FM.clear_vendor_code AS first_clear_vendor_code,
                FM.clear_name AS first_clear_name,
                FM.clear_description AS first_clear_description,
                FM.clear_kit AS first_clear_kit,
                SM.vendor_code AS second_vendor_code,
                SM.name AS second_name,
                SM.description AS second_description,
                SM.kit AS second_kit,
                SM.clear_vendor_code AS second_clear_vendor_code,
                SM.clear_name AS second_clear_name,
                SM.clear_description AS second_clear_description,
                SM.clear_kit AS second_clear_kit
            FROM
                " . self::TBL . " AS PS
                JOIN " . ProductDownloaded::TBL . " AS FM
                    ON (PS.first_mp_product_id = FM.id)
                JOIN " . ProductDownloaded::TBL . " AS SM
                    ON (PS.second_mp_product_id = SM.id)
                    
            WHERE
                PS.user_id = $userId
                AND PS.mp_link_type_id = $linkTypeId
            ORDER BY PS.id
            OFFSET $start
            FETCH FIRST $limit ROWS ONLY
        ";

        $queryRes = App::db()->query($query);
        if (!$queryRes) {
            throw new MarketException("db", 0);
        }

        return $queryRes->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function update(array $parametrs)
    {
        $query = "";
        foreach ($parametrs AS $params) {
            $query .= self::getUpdateQuery($params) . "; ";
        }

        if ($query != "") {
            return App::db()->exec($query);
        }
    }

    public static function getUpdateQuery(array $params)
    {
        return "
            UPDATE " . self::TBL . " SET
                                    similar_description = {$params['similar_description']}, 
                                    similar_name = {$params['similar_name']}, 
                                    similar_vendor_code = {$params['similar_vendor_code']}, 
                                    similar_kit = {$params['similar_kit']}, 
                                    word_equal_name = {$params['word_equal_name']}, 
                                    word_equal_vendor_code = {$params['word_equal_vendor_code']}, 
                                    word_equal_description = {$params['word_equal_description']}, 
                                    word_equal_kit = {$params['word_equal_kit']}
                
            WHERE
                id = {$params['id']}";
    }

    public static function clear(int $userId, int $linkId)
    {
        $query = "DELETE FROM " . self::TBL . " WHERE user_id = $userId AND mp_link_type_id = $linkId";

        App::db()->prepare($query)->execute();
    }

    public static function createNew(int $userId, int $linkTypeId, int $mpFirstId, int $mpSecondId)
    {
        $query = "
            SELECT 
                $userId,
                $linkTypeId,
                F.id AS first_mp_product_id, 
                S.id AS second_mp_product_id
            FROM 
                (SELECT id FROM " . ProductDownloaded::TBL . " WHERE user_id = $userId AND mp_id = $mpFirstId) AS F,
                (SELECT id FROM " . ProductDownloaded::TBL . " WHERE user_id = $userId AND mp_id = $mpSecondId) AS S
                ";

        $query = "INSERT INTO " . self::TBL . " (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) ($query)";

        App::db()->prepare($query)->execute();
    }
}