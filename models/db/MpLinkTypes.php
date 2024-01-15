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
