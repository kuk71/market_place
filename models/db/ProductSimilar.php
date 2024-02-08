<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "product_similar".
 *
 * @property int $id
 * @property int $user_id
 * @property int $link_type_id
 * @property int $first_mp_product_id id первого сопоставляемого товара из product_downloaded
 * @property int $second_mp_product_id id второго сопоставляемого товара из product_downloaded
 * @property float|null $similar_description
 * @property float|null $similar_name
 * @property float|null $similar_vendor_code
 * @property float $similar_kit
 * @property float $word_equal_name
 * @property float $word_equal_vendor_code
 * @property float $word_equal_description
 * @property float $word_equal_kit
 * @property int $color
 * @property int $weight_gr
 * @property int $size_1_mm
 * @property int $size_2_mm
 * @property int $size_3_mm
 * @property int $number_equal_fields
 */
class ProductSimilar extends \yii\db\ActiveRecord
{

    public static function getLinkTypeByUserId(int $userId)
    {
        $query = "
            SELECT DISTINCT
                LT.id,
                LT.mp_first_id,
                LT.mp_second_id,
                MPF.name AS mp_first_name,
                MPS.name AS mp_second_name
            FROM " . self::tableName() . " AS PS
            JOIN " . MpLinkTypes::tableName() . " AS LT
                ON (PS.mp_link_type_id = LT.id)
            JOIN " . MP::tableName() . " AS MPF
                ON (LT.mp_first_id = MPF.id)
            JOIN " . MP::tableName() . " AS MPS
                ON (LT.mp_second_id = MPS.id)
            WHERE PS.user_id = $userId
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_similar';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'link_type_id', 'first_mp_product_id', 'second_mp_product_id'], 'required'],
            [['user_id', 'link_type_id', 'first_mp_product_id', 'second_mp_product_id', 'color', 'weight_gr', 'size_1_mm', 'size_2_mm', 'size_3_mm', 'number_equal_fields'], 'integer'],
            [['similar_description', 'similar_name', 'similar_vendor_code', 'similar_kit', 'word_equal_name', 'word_equal_vendor_code', 'word_equal_description', 'word_equal_kit'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'link_type_id' => 'Link Type ID',
            'first_mp_product_id' => 'First Mp Product ID',
            'second_mp_product_id' => 'Second Mp Product ID',
            'similar_description' => 'Similar Description',
            'similar_name' => 'Similar Name',
            'similar_vendor_code' => 'Similar Vendor Code',
            'similar_kit' => 'Similar Kit',
            'word_equal_name' => 'Word Equal Name',
            'word_equal_vendor_code' => 'Word Equal Vendor Code',
            'word_equal_description' => 'Word Equal Description',
            'word_equal_kit' => 'Word Equal Kit',
            'color' => 'Color',
            'weight_gr' => 'Weight Gr',
            'size_1_mm' => 'Size 1 Mm',
            'size_2_mm' => 'Size 2 Mm',
            'size_3_mm' => 'Size 3 Mm',
            'number_equal_fields' => 'Number Equal Fields',
        ];
    }
}
