<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp_link_no".
 *
 * @property int $id
 * @property int $user_id
 * @property int $mp_link_type_id
 * @property int $mp_id
 * @property int $product_id
 */
class MpLinkNo extends \yii\db\ActiveRecord
{
    public static function setNoLink(int $userId, int $linkTypeId, int $productId, bool $noLink)
    {
        if ($noLink) {
            self::insertNoLink($userId, $linkTypeId, $productId);
        } else {
            self::delNoLink($userId, $linkTypeId, $productId);
        }
    }

    public static function delNoLink(int $userId, int $linkTypeId, int $productId)
    {
        $query = "
            DELETE FROM " . self::tableName() . " 
            WHERE
                user_id = :user_id
                AND mp_link_type_id = :mp_link_type_id
                AND product_id = :product_id";

        $params = [
            ':user_id' => $userId,
            ':mp_link_type_id' => $linkTypeId,
            ':product_id' => $productId,
        ];

        Yii::$app->db->createCommand($query)->bindValues($params)->execute();
    }

    private static function insertNoLink(int $userId, int $linkTypeId, int $productId)
    {
        $query = "
            INSERT INTO " . self::tableName() . " 
                        (user_id, mp_link_type_id, product_id) 
                VALUES (:user_id, :mp_link_type_id, :product_id)
            ON CONFLICT (user_id, mp_link_type_id, product_id) 
            DO NOTHING;";

        $params = [
            'user_id' => $userId,
            'mp_link_type_id' => $linkTypeId,
            'product_id' => $productId,
        ];

        Yii::$app->db->createCommand($query)->bindValues($params)->execute();
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp_link_no';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'mp_link_type_id', 'mp_id', 'product_id'], 'required'],
            [['user_id', 'mp_link_type_id', 'mp_id', 'product_id'], 'default', 'value' => null],
            [['user_id', 'mp_link_type_id', 'mp_id', 'product_id'], 'integer'],
            [['user_id', 'mp_link_type_id', 'mp_id', 'product_id'], 'unique', 'targetAttribute' => ['user_id', 'mp_link_type_id', 'mp_id', 'product_id']],
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
            'mp_link_type_id' => 'Mp Link Type ID',
            'mp_id' => 'Mp ID',
            'product_id' => 'Product ID',
        ];
    }
}
