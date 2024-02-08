<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp_ms".
 *
 * @property int $id
 * @property int $user_id
 * @property string $UUID
 * @property string|null $code
 * @property string|null $name
 * @property string|null $external_code
 * @property string|null $article
 * @property string|null $barcode
 * @property float|null $weight_kg
 * @property float|null $length_sm
 * @property float|null $width_sm
 * @property float|null $height_sm
 */
class MpMs extends \yii\db\ActiveRecord
{
    /**
     * Ищет товар в Моум складе по совязи  с товаром $pId
     *
     * @param int $userId
     * @param int $pId
     * @return void
     */
    public static function getByProductIdLink(int $userId, int $linkTypeId, int $pId)
    {
        $query = "
            SELECT
                MS.\"UUID\",
                MS.code,
                MS.name,
                MS.external_code,
                MS.article,
                MS.barcode
            FROM " . self::tableName() . " AS MS
            JOIN " . MpLinkCandidates::tableName() . " MLC
                ON (
                    MS.id = MLC.second_mp_product_id 
                    AND MLC.first_mp_product_id = $pId 
                    AND MLC.user_id = $userId 
                    AND MLC.mp_link_type_id = $linkTypeId
                    AND MLC.is_del = 0)
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    /**
     * {@inheritdoc}
     */
    public
    static function tableName()
    {
        return 'mp_ms';
    }

    /**
     * {@inheritdoc}
     */
    public
    function rules()
    {
        return [
            [['user_id', 'UUID'], 'required'],
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['UUID', 'code', 'name', 'external_code', 'article', 'barcode'], 'string'],
            [['weight_kg', 'length_sm', 'width_sm', 'height_sm'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public
    function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'UUID' => 'Uuid',
            'code' => 'Code',
            'name' => 'Name',
            'external_code' => 'External Code',
            'article' => 'Article',
            'barcode' => 'Barcode',
            'weight_kg' => 'Weight Kg',
            'length_sm' => 'Length Sm',
            'width_sm' => 'Width Sm',
            'height_sm' => 'Height Sm',
        ];
    }

    public
    static function getProductForLink(int $userId, int $linkTypeId, int $mpId)
    {
        $query = "
            SELECT DISTINCT ON (PD.id)
                'Мой склад' AS mp_name,
                PD.id,
                4 AS mp_id,
                '' AS product_mp_id,
                PD.code AS vendor_code,
                PD.name,
                '' AS description,
                '' AS kit,
                PD.color,
                '' AS img,
                PD.weight_gr,
                PD.size_1_mm,
                PD.size_2_mm,
                PD.size_3_mm,
                LC.first_mp_product_id AS link_candidate
            FROM " . self::tableName() . " AS PD
            LEFT JOIN " . MpLinkCandidates::tableName() . " AS LC
                ON (PD.id = LC.first_mp_product_id AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId AND LC.is_del = 0)
            WHERE
                PD.user_id = $userId
            ORDER BY PD.id
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }
}
