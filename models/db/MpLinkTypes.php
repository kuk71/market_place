<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp_link_types".
 *
 * @property int $id
 * @property int $mp_first_id id (из таблицы mp) - первого маркет плейса участвующего в связи
 * @property int $mp_second_id id (из таблицы mp) - второго маркет плейса участвующего в связи
 */
class MpLinkTypes extends \yii\db\ActiveRecord
{

    public static function getTypeLinkIdByMpId(int $userId, int $firstMpId, int $secondMpId)
    {
        return self::find()
            ->where("
                (mp_first_id = $firstMpId AND mp_second_id = $secondMpId)
                 OR (mp_first_id = $secondMpId AND mp_second_id = $firstMpId)")
            ->asArray()
            ->all();
    }

    public static function getTypeLinkIdByProductId(int $userId, int $firstProductId, int $secondProductId)
    {
        $firstMpId = ProductDownloaded::getMpIdByProductId($userId, $firstProductId);
        $secondMpId = ProductDownloaded::getMpIdByProductId($userId, $secondProductId);

        if (count($firstMpId) === 0 || count($secondMpId) === 0) {
            return [];
        }


        $firstMpId = (int)$firstMpId['mp_id'];
        $secondMpId = (int)$secondMpId['mp_id'];

        return self::find()
            ->where("
                (mp_first_id = $firstMpId AND mp_second_id = $secondMpId)
                 OR (mp_first_id = $secondMpId AND mp_second_id = $firstMpId)")
            ->asArray()
            ->all();
    }

    public static function getMpIdByLink(int $linkTypeId)
    {
        $link = self::find()->where(['id' => $linkTypeId])->asArray()->all();

        if (count($link) === 0) {
            return false;
        }

        return ["mpFirstId" => (int)$link[0]['mp_first_id'], "mpSecondId" => (int)$link[0]['mp_second_id']];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp_link_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mp_first_id', 'mp_second_id'], 'required'],
            [['mp_first_id', 'mp_second_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mp_first_id' => 'Mp First ID',
            'mp_second_id' => 'Mp Second ID',
        ];
    }
}
