<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "product_downloaded".
 *
 * @property int $id
 * @property int $user_id
 * @property int $mp_id
 * @property int $product_mp_id ID товара присвоенные маркетплесом
 * @property string|null $vendor_code ID товара заданный продавцом в системе маркет плейса 
 * @property string|null $name
 * @property string|null $description
 * @property string|null $kit
 * @property string|null $clear_name
 * @property string|null $clear_description
 * @property string|null $clear_vendor_code
 * @property string|null $clear_kit
 * @property string|null $color
 * @property string|null $img
 * @property float $weight
 * @property string $weight_unit
 * @property float $length
 * @property string|null $dimension_length
 * @property float $width
 * @property string|null $dimension_width
 * @property float $height
 * @property string|null $dimension_height
 * @property int|null $weight_gr
 * @property int|null $size_1_mm
 * @property int|null $size_2_mm
 * @property int|null $size_3_mm
 * @property string $json
 */
class ProductDownloaded extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_downloaded';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'mp_id', 'product_mp_id', 'weight', 'weight_unit', 'length', 'width', 'height', 'json'], 'required'],
            [['user_id', 'mp_id', 'product_mp_id', 'weight_gr', 'size_1_mm', 'size_2_mm', 'size_3_mm'], 'integer'],
            [['vendor_code', 'name', 'description', 'kit', 'clear_name', 'clear_description', 'clear_vendor_code', 'clear_kit'], 'string'],
            [['img', 'json'], 'safe'],
            [['weight', 'length', 'width', 'height'], 'number'],
            [['color', 'weight_unit', 'dimension_length', 'dimension_width', 'dimension_height'], 'string', 'max' => 255],
            [['user_id', 'mp_id', 'product_mp_id'], 'unique', 'targetAttribute' => ['user_id', 'mp_id', 'product_mp_id']],
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
            'mp_id' => 'Mp ID',
            'product_mp_id' => 'Product Mp ID',
            'vendor_code' => 'Vendor Code',
            'name' => 'Name',
            'description' => 'Description',
            'kit' => 'Kit',
            'clear_name' => 'Clear Name',
            'clear_description' => 'Clear Description',
            'clear_vendor_code' => 'Clear Vendor Code',
            'clear_kit' => 'Clear Kit',
            'color' => 'Color',
            'img' => 'Img',
            'weight' => 'Weight',
            'weight_unit' => 'Weight Unit',
            'length' => 'Length',
            'dimension_length' => 'Dimension Length',
            'width' => 'Width',
            'dimension_width' => 'Dimension Width',
            'height' => 'Height',
            'dimension_height' => 'Dimension Height',
            'weight_gr' => 'Weight Gr',
            'size_1_mm' => 'Size 1 Mm',
            'size_2_mm' => 'Size 2 Mm',
            'size_3_mm' => 'Size 3 Mm',
            'json' => 'Json',
        ];
    }
}
