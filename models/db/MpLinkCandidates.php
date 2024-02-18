<?php

namespace app\models\db;

use PHPUnit\Exception;
use Yii;

/**
 * This is the model class for table "mp_link_candidates".
 *
 * @property int $id
 * @property int $user_id id пользователя которому принадлежат магазины
 * @property int $mp_link_type_id id типа связи из таблицы mp_link_types
 * @property int $first_mp_product_id id продукта из первого магазина участвующего в связи
 * @property int $second_mp_product_id id продукта из второго магазина участвующего в связи
 * @property int $link_num Номер попытки связывания
 * @property int $is_del 1 - связь разорвана
 */
class MpLinkCandidates extends \yii\db\ActiveRecord
{


    public static function addLink(int $userId, int $typeLinkId, int $firstProductId, int $secondProductId)
    {
        $query = "
        INSERT INTO " . self::tableName() . " (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) 
                                VALUES (:userId, :typeLinkId, :firstProductId, :secondProductId)
            ON CONFLICT (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) 
            DO UPDATE SET is_del = 0;";

        $params = [
            ":userId" => $userId,
            ":typeLinkId" => $typeLinkId,
            ":firstProductId" => $firstProductId,
            ":secondProductId" => $secondProductId,
        ];

        Yii::$app->db->createCommand($query)->bindValues($params)->execute();

        // удалить информацию о связанных товарах из таблицы с не связанными товарами
        MpLinkNo::delNoLink($userId, $typeLinkId, $firstProductId);
        MpLinkNo::delNoLink($userId, $typeLinkId, $secondProductId);
    }

    public static function delLink(int $userId, int $linkId)
    {
        $query = "UPDATE " . self::tableName() . " SET is_del = 1 WHERE user_id = :userId AND id = :linkId";

        $params = [
            ":userId" => $userId,
            ":linkId" => $linkId,
        ];

        return Yii::$app->db->createCommand($query)->bindValues($params)->execute();
    }

    public static function createLinkProductFirst(int $userId, int $linkTypeId)
    {

        if ($linkTypeId === 3) {
            // соединение WB / Ya - соединяется через Ozon
            $queryOzonWB = "SELECT first_mp_product_id, second_mp_product_id FROM mp_link_candidates WHERE user_id = :userId AND mp_link_type_id = 1 AND is_del = 0";
            $queryOzonYa = "SELECT first_mp_product_id, second_mp_product_id FROM mp_link_candidates WHERE user_id = :userId AND mp_link_type_id = 2 AND is_del = 0";

            $products = "
                SELECT
                   $userId, 
                   :linkTypeId,
                   W.second_mp_product_id,
                   Y.second_mp_product_id
                FROM ($queryOzonWB) AS W
                JOIN ($queryOzonYa) AS Y
                    ON (W.first_mp_product_id = Y.first_mp_product_id)";
        } else {
            $products = self::getSimilarProductQuery();
        }

        $query = "INSERT INTO 
                    " . self::tableName() . " 
                        (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) 
                        ($products)
                        ON CONFLICT (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) DO NOTHING";


        $params = [
            ":userId" => $userId,
            ":linkTypeId" => $linkTypeId,
        ];

        Yii::$app->db->createCommand($query)->bindValues($params)->execute();
    }

    public static function createLinkProductFirstMs(int $userId, int $linkTypeId)
    {
        $products = self::getSimilarProductQueryMs();

        $query = "INSERT INTO 
                    " . self::tableName() . " 
                        (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) 
                        ($products)
                        ON CONFLICT (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) DO NOTHING";

        $params = [
            ":userId" => $userId,
            ":linkTypeId" => $linkTypeId,
        ];

        Yii::$app->db->createCommand($query)->bindValues($params)->execute();
    }

    public static function getSimilarProductQueryMs()
    {
        return "
            SELECT
                L.user_id,
                L.mp_link_type_id,
                L.first_mp_product_id,
                L.second_mp_product_id
            FROM " . ProductSimilar::tableName() . " AS L
                JOIN " . ProductDownloaded::tableName() . " AS F 
                    ON (L.first_mp_product_id = F.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
                JOIN " . MpMs::tableName() . " AS S
                    ON (L.second_mp_product_id = S.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
            WHERE 
                S.barcode = F.barcode 
                OR (S.barcode like ('%' || F.id_for_sold_reports || '%') AND F.id_for_sold_reports != '0') 
                --OR (F.vendor_code = S.name OR F.vendor_code = S.code OR F.vendor_code = S.article)
                --OR (F.name = S.name OR F.name = S.code OR F.name = S.article)
                --OR L.number_equal_fields > 4
            ORDER BY L.first_mp_product_id ASC";



        return "
            SELECT
                L.user_id,
                L.mp_link_type_id,
                L.first_mp_product_id,
                L.second_mp_product_id
            FROM " . ProductSimilar::tableName() . " AS L
                JOIN " . ProductDownloaded::tableName() . " AS F 
                    ON (L.first_mp_product_id = F.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
                JOIN " . MpMs::tableName() . " AS S
                    ON (L.second_mp_product_id = S.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
            WHERE 
                F.json->>'barcode' = S.barcode 
                OR (F.vendor_code = S.name OR F.vendor_code = S.code OR F.vendor_code = S.article)
                OR (F.name = S.name OR F.name = S.code OR F.name = S.article)
                OR L.number_equal_fields > 4
            ORDER BY L.first_mp_product_id ASC";
    }


    public static function getSimilarProductQuery()
    {
        return "
            SELECT
                L.user_id,
                L.mp_link_type_id,
                L.first_mp_product_id,
                L.second_mp_product_id
            FROM " . ProductSimilar::tableName() . " AS L
                JOIN " . ProductDownloaded::tableName() . " AS F 
                    ON (L.first_mp_product_id = F.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
                JOIN " . ProductDownloaded::tableName() . " AS S
                    ON (L.second_mp_product_id = S.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
            WHERE 
                (
                    L.color = 1 AND 
                        (number_equal_fields > 3 OR (number_equal_fields > 2 AND similar_description > 90))
                ) 
                OR (F.vendor_code = S.vendor_code)
                OR (similar_description > 90 AND similar_vendor_code > 90 AND number_equal_fields > 3)
                OR (L.color = 1 AND word_equal_kit = 100 AND word_equal_name > 70 AND F.kit <> '' AND S.kit <> '')
                OR (L.color = 1 AND (word_equal_description + word_equal_name + word_equal_vendor_code + word_equal_kit) > 250 AND (similar_description + similar_name + similar_vendor_code + similar_kit) > 230)
                OR ((word_equal_description + word_equal_name + word_equal_vendor_code + word_equal_kit) > 300 AND word_equal_vendor_code = 100  AND number_equal_fields > 0)
                OR ((word_equal_description + word_equal_name + word_equal_vendor_code + word_equal_kit) > 370 AND word_equal_name = 100  AND (word_equal_description + word_equal_name + word_equal_vendor_code + word_equal_kit) > 250 AND number_equal_fields > 0)
            ORDER BY L.first_mp_product_id ASC";
    }

    public static function getLinkProduct(int $userId, int $linkTypeId, int $linkNum)
    {
        $whereLinkNum = "AND link_num = $linkNum";
        if ($linkNum === 0) {
            $whereLinkNum = "";
        }

        $query = "
            SELECT
                LC.id AS \"linkId\",
                FM.id AS \"firstId\",
                FM.product_mp_id AS \"firstMpProductId\",
                FMP.id AS \"firstMpId\",
                FMP.name AS \"firstMpName\",
                FM.vendor_code AS \"firstVendorCode\",
                FM.name AS \"firstName\",
                FM.description AS \"firstDescription\",
                FM.kit AS \"firstSet\",
                FM.color AS \"firstColor\",
                FM.size_1_mm AS \"firstSize1mm\",
                FM.size_2_mm AS \"firstSize2mm\",
                FM.size_3_mm AS \"firstSize3mm\",
                FM.weight_gr AS \"firstWeightGr\",
                FM.img AS \"firstImg\",
                SM.id AS \"secondId\",
                SM.product_mp_id AS \"secondMpProductId\",
                SMP.id AS \"secondMpId\",
                SMP.name AS \"secondMpName\",
                SM.vendor_code AS \"secondVendorCode\",
                SM.name AS \"secondName\",
                SM.description AS \"secondDescription\",
                SM.kit AS \"secondSet\",
                SM.color AS \"secondColor\",
                SM.size_1_mm AS \"secondSize1mm\",
                SM.size_2_mm AS \"secondSize2mm\",
                SM.size_3_mm AS \"secondSize3mm\",
                SM.weight_gr AS \"secondWeightGr\",
                SM.img AS \"secondImg\"
            FROM " . self::tableName() . " AS LC
                JOIN " . ProductDownloaded::tableName() . " AS FM 
                    ON (LC.first_mp_product_id = FM.id AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId)
                JOIN " . ProductDownloaded::tableName() . " AS SM
                    ON (LC.second_mp_product_id = SM.id AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId)
                JOIN " . MP::tableName() . " FMP
                    ON (FMP.id = FM.mp_id)
                JOIN " . MP::tableName() . " SMP
                    ON (SMP.id = SM.mp_id)
                    
            WHERE
                is_del = 0
                $whereLinkNum
            ORDER BY
                FM.id
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getLinkProductMs(int $userId, int $linkTypeId, int $linkNum)
    {
        $whereLinkNum = "AND link_num = $linkNum";

        if ($linkNum === 0) {
            $whereLinkNum = "";
        }

        $query = "
            SELECT
                LC.id AS \"linkId\",
                FM.id AS \"firstId\",
                FM.product_mp_id AS \"firstMpProductId\",
                FMP.id AS \"firstMpId\",
                FMP.name AS \"firstMpName\",
                FM.vendor_code AS \"firstVendorCode\",
                FM.name AS \"firstName\",
                FM.description AS \"firstDescription\",
                FM.kit AS \"firstSet\",
                FM.color AS \"firstColor\",
                FM.size_1_mm AS \"firstSize1mm\",
                FM.size_2_mm AS \"firstSize2mm\",
                FM.size_3_mm AS \"firstSize3mm\",
                FM.weight_gr AS \"firstWeightGr\",
                FM.img AS \"firstImg\",
                FM.barcode AS \"firstBarcode\",
                FM.id_for_sold_reports,
                SM.id AS \"secondId\",
                SM.external_code AS \"secondMpProductId\",
                SMP.id AS \"secondMpId\",
                SMP.name AS \"secondMpName\",
                SM.code AS \"secondVendorCode\",
                SM.name AS \"secondName\",
                SM.article AS \"secondDescription\",
                '' AS \"secondSet\",
                SM.color AS \"secondColor\",
                SM.size_1_mm AS \"secondSize1mm\",
                SM.size_2_mm AS \"secondSize2mm\",
                SM.size_3_mm AS \"secondSize3mm\",
                SM.weight_gr AS \"secondWeightGr\",
                '' AS \"secondImg\",
                SM.barcode AS \"secondBarcode\"
            FROM " . self::tableName() . " AS LC
                JOIN " . ProductDownloaded::tableName() . " AS FM 
                    ON (LC.first_mp_product_id = FM.id AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId)
                JOIN " . MpMs::tableName() . " AS SM
                    ON (LC.second_mp_product_id = SM.id AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId)
                JOIN " . MP::tableName() . " FMP
                    ON (FMP.id = FM.mp_id)
                JOIN " . MP::tableName() . " SMP
                    ON (SMP.id = 4)
                    
            WHERE
                is_del = 0
                $whereLinkNum
            ORDER BY
                FM.id
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function addLinkSecond(int $userId, int $linkTypeId, string $queryPairNotLink)
    {
        $linkNum = 2;

        $query = "
            SELECT DISTINCT
                    $linkTypeId,
                    $linkNum,
                    $userId,
                    S.first_mp_product_id,
                    S.second_mp_product_id
                FROM " . ProductSimilar::tableName() . " AS S
                
                WHERE 
                    (
                        (S.first_mp_product_id, S.second_mp_product_id) IN ($queryPairNotLink) 
                    )
                    AND (
                        (S.number_equal_fields > 0
                        AND (
                            similar_description > 50
                            OR similar_name > 50
                            OR 	similar_vendor_code > 50
                            OR similar_kit > 50)
                        OR (
                            similar_description > 90
                            OR similar_name > 90
                            OR 	similar_vendor_code > 90
                            OR similar_kit > 90
                        )
                        )
                        
                    )
        ";

        $query = "
            INSERT INTO 
                    mp_link_candidates 
                        (mp_link_type_id, link_num, user_id, first_mp_product_id, second_mp_product_id) 
                        ($query)
            ON CONFLICT (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) DO NOTHING            
        ";

        return Yii::$app->db->createCommand($query)->execute();
    }

    public static function addLinkSecondMs(int $userId, int $linkTypeId, string $queryPairNotLink)
    {
        $linkNum = 2;

//        SELECT
//                L.user_id,
//                L.mp_link_type_id,
//                L.first_mp_product_id,
//                L.second_mp_product_id
//            FROM " . ProductSimilar::tableName() . " AS L
//                JOIN " . ProductDownloaded::tableName() . " AS F
//                    ON (L.first_mp_product_id = F.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
//                JOIN " . MpMs::tableName() . " AS S
//                    ON (L.second_mp_product_id = S.id AND L.user_id = :userId AND L.mp_link_type_id = :linkTypeId)
//            WHERE
//                F.json->>'barcode' = S.barcode
//                OR (F.vendor_code = S.name OR F.vendor_code = S.code OR F.vendor_code = S.article)
//                OR (F.name = S.name OR F.name = S.code OR F.name = S.article)
//                OR L.number_equal_fields > 4
//            ORDER BY L.first_mp_product_id ASC";

        $query = "
            SELECT DISTINCT
                    $linkTypeId,
                    $linkNum,
                    $userId,
                    L.first_mp_product_id,
                    L.second_mp_product_id
                FROM " . ProductSimilar::tableName() . " AS L
                JOIN " . ProductDownloaded::tableName() . " AS F 
                   ON (L.first_mp_product_id = F.id AND L.user_id = $userId AND L.mp_link_type_id = $linkTypeId)
                JOIN " . MpMs::tableName() . " AS S
                    ON (L.second_mp_product_id = S.id AND L.user_id = $userId AND L.mp_link_type_id = $linkTypeId)
                WHERE 
                    (L.first_mp_product_id, L.second_mp_product_id) IN ($queryPairNotLink)
                    AND (
                        F.vendor_code ilike S.code
                        OR F.vendor_code ilike S.name
                        OR F.vendor_code ilike S.article
                        OR F.name ilike S.code
                        OR F.name ilike S.name
                        OR F.name ilike S.article
                        
                        OR (
                            F.vendor_code != ''
                            AND (
                                (
                                    F.vendor_code ilike '%' || S.code || '%'
                                    AND S.code != ''
                                )
                                
                                OR (
                                    F.vendor_code ilike '%' || S.name || '%'
                                    AND S.name != ''
                                )
                                    
                                OR (
                                    F.vendor_code ilike '%' || S.article || '%'
                                    AND S.article != ''
                                )
                            )
                        )
                        
                        OR (
                            F.name != ''
                            AND (
                                (
                                    F.name ilike '%' || S.code || '%'
                                    AND S.code != ''
                                )
                                
                                OR (
                                    F.name ilike '%' || S.name || '%'
                                    AND S.name != ''
                                )
                                    
                                OR (
                                    F.name ilike '%' || S.article || '%'
                                    AND S.article != ''
                                )
                            )
                        )
                        
                        OR (
                            S.code != ''
                            AND (
                                (
                                    S.code ilike '%' || F.vendor_code || '%'
                                    AND F.vendor_code != ''
                                ) 
                                OR (
                                    S.code ilike '%' || F.name || '%'
                                    AND F.name != ''
                                ) 
                            )
                        )
                        
                        OR (
                            S.name != ''
                            AND (
                                (
                                    S.name ilike '%' || F.vendor_code || '%'
                                    AND F.vendor_code != ''
                                ) 
                                OR (
                                    S.name ilike '%' || F.name || '%'
                                    AND F.name != ''
                                ) 
                            )
                        )
                        
                        OR (
                            S.code != ''
                            AND (
                                (
                                    S.code ilike '%' || F.vendor_code || '%'
                                    AND F.vendor_code != ''
                                ) 
                                OR (
                                    S.code ilike '%' || F.name || '%'
                                    AND F.name != ''
                                ) 
                            )
                        )

                    )
        ";

        $query = "
            INSERT INTO 
                    mp_link_candidates 
                        (mp_link_type_id, link_num, user_id, first_mp_product_id, second_mp_product_id) 
                        ($query)
            ON CONFLICT (user_id, mp_link_type_id, first_mp_product_id, second_mp_product_id) DO NOTHING            
        ";

        return Yii::$app->db->createCommand($query)->execute();
    }

    // получает список вариантов объединения id товаров не попавших в пары
    public static function getQueryPairNotLink(int $userId, int $linkTypeId)
    {
        $mpId = MpLinkTypes::findOne($linkTypeId);
        if (!$mpId) {
            return false;
        }

        $queryLinkFirstMp = "
            SELECT
                first_mp_product_id
            FROM
                " . self::tableName() . "
            WHERE
                user_id = $userId AND mp_link_type_id = $linkTypeId AND is_del = 0
        ";

        $queryNotLinkProductFirstMp = "
            SELECT
                id
            FROM
                " . ProductDownloaded::tableName() . "
            WHERE
                id NOT IN ($queryLinkFirstMp)
                AND user_id = $userId
                AND mp_id = {$mpId['mp_first_id']}
        ";

        $queryLinkSecondMp = "
            SELECT
                second_mp_product_id
            FROM
                " . self::tableName() . "
            WHERE
                user_id = $userId AND mp_link_type_id = $linkTypeId AND is_del = 0
        ";

        $queryNotLinkProductSecondMp = "
            SELECT
                id
            FROM
                " . ProductDownloaded::tableName() . "
            WHERE
                id NOT IN ($queryLinkSecondMp)
                AND user_id = $userId
                AND mp_id = {$mpId['mp_second_id']}
        ";

        return "
            SELECT
                F.id AS first_mp_product_id,
                S.id AS second_mp_product_id
            FROM
                ($queryNotLinkProductFirstMp) AS F, ($queryNotLinkProductSecondMp) AS S
        ";
    }

    // получает список вариантов объединения id товаров не попавших в пары для соединения Ozon / Мой склад
    public static function getQueryPairNotLinkMs(int $userId, int $linkTypeId)
    {
        $mpId = MpLinkTypes::findOne($linkTypeId);
        if (!$mpId) {
            return false;
        }

        $queryLinkFirstMp = "
            SELECT
                first_mp_product_id
            FROM
                " . self::tableName() . "
            WHERE
                user_id = $userId AND mp_link_type_id = $linkTypeId AND is_del = 0
        ";

        $queryNotLinkProductFirstMp = "
            SELECT
                id
            FROM
                " . ProductDownloaded::tableName() . "
            WHERE
                id NOT IN ($queryLinkFirstMp)
                AND user_id = $userId
                AND mp_id = {$mpId['mp_first_id']}
        ";

        $queryLinkSecondMp = "
            SELECT
                second_mp_product_id
            FROM
                " . self::tableName() . "
            WHERE
                user_id = $userId AND mp_link_type_id = $linkTypeId AND is_del = 0
        ";

        $queryNotLinkProductSecondMp = "
            SELECT
                id
            FROM
                " . MpMs::tableName() . "
        ";


//        -- WHERE
//            --    id NOT IN ($queryLinkSecondMp)
//    --    AND user_id = $userId

        return "
            SELECT
                F.id AS first_mp_product_id,
                S.id AS second_mp_product_id
            FROM
                ($queryNotLinkProductFirstMp) AS F, ($queryNotLinkProductSecondMp) AS S
        ";
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp_link_candidates';
    }
}
