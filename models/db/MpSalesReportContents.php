<?php

namespace app\models\db;

use Yii;

/**
 * This is the model class for table "mp_sales_report_contents".
 *
 * @property int $id
 * @property int $sales_report_id
 * @property int $count_sold
 * @property int $price_sold_kop
 * @property string|null $timestamp_sent_to_accounting_system
 * @property int $mp_product_barcode
 * @property int $reward_sold_kop
 * @property int $position_in_report
 */
class MpSalesReportContents extends \yii\db\ActiveRecord
{
    // возвращает список товаров по которым были продажи с общим числом проданных позиций
    public static function getProductSoldCount(int $salesReportId)
    {
        $query = "
            SELECT
                mp_product_barcode,
                MSP.ms_id_new,
                sum(count_sold)
            FROM " . self::tableName() . " AS SRC
            LEFT JOIN " . ProductDownloaded::tableName() . " AS PD
                ON (SRC.mp_product_barcode = PD.barcode)
            LEFT JOIN " . MpLinkCandidates::tableName() . " AS LC
                ON (LC.mp_link_type_id = 4 AND PD.id = LC.first_mp_product_id)
            LEFT JOIN " . MpMs::tableName() . " AS MS
                ON (LC.second_mp_product_id = MS.id)
            LEFT JOIN ms_products AS MSP
                ON (MS.\"UUID\" = MSP.ms_id) 
                
            WHERE
                SRC.sales_report_id = $salesReportId
                AND count_sold > 0
            GROUP BY mp_product_barcode, MSP.ms_id_new
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mp_sales_report_contents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'reward_sold_kop', 'mp_product_barcode', 'position_in_report'], 'required'],
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'mp_product_barcode'], 'default', 'value' => null],
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'reward_sold_kop', 'position_in_report'], 'integer'],
            [['timestamp_sent_to_accounting_system'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sales_report_id' => 'Sales Report ID',
            'count_sold' => 'Count Sold',
            'price_sold_kop' => 'Price Sold Kop',
            'timestamp_sent_to_accounting_system' => 'Timestamp Sent To Accounting System',
            'mp_product_barcode' => 'Mp Product Barcode',
        ];
    }
}
