<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp_sales_reports".
 *
 * @property int $id
 * @property int $mp_id
 * @property string $timestamp_added
 * @property string|null $timestamp_sent_to_accounting_system
 * @property string $date_start
 * @property string $date_end
 */
class MpSalesReports extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp_sales_reports';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mp_id', 'date_start', 'date_end'], 'required'],
            [['mp_id'], 'default', 'value' => null],
            [['mp_id'], 'integer'],
            [['timestamp_added', 'timestamp_sent_to_accounting_system', 'date_start', 'date_end'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mp_id' => 'Mp ID',
            'timestamp_added' => 'Timestamp Added',
            'timestamp_sent_to_accounting_system' => 'Timestamp Sent To Accounting System',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
        ];
    }
}
