<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp".
 *
 * @property int $id
 * @property string $name
 */
class MP extends \yii\db\ActiveRecord
{
    public static function getMpIdByName(string $mpName)
    {
        return self::find()->where(['name' => $mpName])->scalar();
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }
}
