<?php

namespace app\models\db;

use Exception;
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

    public static function getProductByListProductId(array $pIds)
    {
        $pIds = array_map('intval', $pIds);

        $pIds = implode(",", $pIds);

        $query = "
            SELECT
                PD.id,
                PD.mp_id,
                MP.name AS mp_name,
                PD.product_mp_id,
                PD.name,
                PD.img, 
                PD.color,
                PD.vendor_code,
                PD.description
            FROM " . self::tableName() . " AS PD
            JOIN " . MP::tableName() . " AS MP
                ON (PD.mp_id = MP.id)
            WHERE
                PD.id IN ($pIds)
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getProductIdsByUserIdMpId(int $userId, int $mpId) {
        return self::find()
            ->select('id')
            ->where(['user_id' => Yii::$app->user->id, 'mp_id' => $mpId])
            ->column();
    }

    public static function getMpIdsByUserId(int $userId)
    {
        return self::find()
            ->select('mp_id')
            ->distinct()
            ->where(['user_id' => $userId])
            ->column();
    }

    public static function getMpIdByProductId(int $userId, int $id) {
        return self::find()
            ->select('mp_id')
            ->where(["user_id" => $userId, "id" => $id])
            ->asArray()
            ->one();
    }

    /**
     *
     * Выбирает список потенциально возможных для связи товаров для передачи в форму ручного связывания
     * @param int $userId
     * @param int $linkTypeId
     * @param int $mpId
     * @param int $numLink
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    public static function getProductForLink(int $userId, int $linkTypeId, int $mpId, int $numLink)
    {
        // $numProduct - определяет по какому из полей связывать таблицы Списка товаров и Кандидатов на соединение
        // $numLink - определяет какую позицию в таблице связей mp_link_type занимает маркет плейс
        // товары которого выбираются для связи
        $numProduct = "first_mp_product_id";
        if ($numLink === 2){
            $numProduct = "second_mp_product_id";
        }

        $query = "
            SELECT DISTINCT ON (PD.id)
                MP.name AS mp_name,
                PD.id,
                PD.mp_id,
                PD.product_mp_id,
                PD.vendor_code,
                PD.name,
                PD.description,
                PD.kit,
                PD.color,
                PD.img,
                PD.weight_gr,
                PD.size_1_mm,
                PD.size_2_mm,
                PD.size_3_mm,
                LC.$numProduct AS link_candidate
            FROM " . self::tableName() . " AS PD
            LEFT JOIN " . MpLinkCandidates::tableName() . " AS LC
                ON (PD.id = LC.$numProduct AND LC.user_id = $userId AND LC.mp_link_type_id = $linkTypeId AND LC.is_del = 0)
            LEFT JOIN " . MP::tableName() . " AS MP
                ON (PD.mp_id = MP.id)
                
            WHERE
                PD.user_id = $userId
                AND PD.mp_id = $mpId
            ORDER BY PD.id
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getProductById(int $userId, int $productId)
    {
        $query = "
            SELECT
                MP.name AS mp_name,
                PD.id,
                PD.mp_id,
                PD.product_mp_id,
                PD.vendor_code,
                PD.name,
                PD.description,
                PD.kit,
                PD.color,
                PD.img,
                PD.weight_gr,
                PD.size_1_mm, 
                PD.size_2_mm,
                PD.size_3_mm
            FROM " . self::tableName() . " AS PD
            JOIN " . MP::tableName() . " AS MP
                ON (PD.mp_id = MP.id)
            WHERE
                PD.user_id = $userId
                AND PD.id = $productId
        ";

        return Yii::$app->db->createCommand($query)->queryAll();

        return self::find()
            ->select(["id",  "mp_id", "product_mp_id", "vendor_code", "name", "description", "kit", "color", "img", "weight_gr", "size_1_mm", "size_2_mm", "size_3_mm"])
            ->where(["user_id" => $userId, "id" => $productId])
            ->asArray()
            ->all();
    }

    public static function getProductNotLink(int $userId, int $linkTypeId, int $mpId)
    {
        // получить id маркет плейсов из связи
        $mpByLinkId = MpLinkTypes::getMpIdByLink($linkTypeId);
        if (!$mpByLinkId) {
            throw new Exception("No id link");
        }

        $mpProductId = "first_mp_product_id";

        if ($mpByLinkId['mpSecondId'] === $mpId) {
            $mpProductId = "second_mp_product_id";
        }

        // запрос на получение id товаров у которых есть пара
        $productLinkId = "
            SELECT 
                $mpProductId
            FROM
                " . MpLinkCandidates::tableName() . "
            WHERE
                user_id = $userId
                AND mp_link_type_id = $linkTypeId
                AND is_del = 0
        ";

        // запрос на получение товара без пары
        $query = "
            SELECT
                MP.name AS mp_name,
                PD.id,
                PD.product_mp_id,
                PD.vendor_code,
                PD.name,
                PD.description,
                PD.kit,
                PD.color,
                PD.img,
                PD.weight_gr,
                PD.size_1_mm,
                PD.size_2_mm,
                PD.size_3_mm,
                case when NL.id IS NULL then false else true end AS \"noLink\"
            FROM " . self::tableName() . " AS PD
            LEFT JOIN  " . MpLinkNo::tableName(). " AS NL
                ON (PD.id = NL.product_id AND NL.mp_link_type_id = $linkTypeId)
            JOIN " . MP::tableName() . " AS MP
                ON (PD.mp_id = MP.id) 
            WHERE
                PD.user_id = $userId 
                AND PD.mp_id =  $mpId 
                AND PD.id NOT IN ($productLinkId)
       ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }


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
