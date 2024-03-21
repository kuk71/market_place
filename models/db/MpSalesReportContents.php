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
 * @property string $mp_product_barcode
 * @property int $reward_sold_kop
 * @property int $position_in_report
 * @property int|null $sku
 * @property string|null $name
 * @property string|null $article
 * @property int|null $scores_kop
 */
class MpSalesReportContents extends \yii\db\ActiveRecord
{




    public static function getProductSold($salesReportId, $mpId, $userId)
    {
        // ozon
        $query = "
            SELECT
                SRC.count_sold,
                SRC.price_sold_kop,
                SRC.reward_sold_kop,
                MSP.ms_id_new
                , SRC.position_in_report
                , SRC.name
            FROM mp_sales_report_contents AS SRC
            LEFT JOIN product_downloaded AS PD
                ON (SRC.article = PD.vendor_code AND PD.user_id = $userId AND PD.mp_id = $mpId)
            LEFT JOIN " . MpLinkCandidates::tableName() . " AS LC
                ON (LC.mp_link_type_id = 4 AND PD.id = LC.first_mp_product_id AND LC.is_del = 0)
            LEFT JOIN " . MpMs::tableName() . " AS MS
                ON (LC.second_mp_product_id = MS.id)
            LEFT JOIN ms_products AS MSP
                ON (MS.\"UUID\" = MSP.ms_id) 
                
            WHERE
                SRC.sales_report_id = $salesReportId
                --AND SRC.position_in_report >= 43
                -- AND SRC.position_in_report <= 350
                
                -- AND position_in_report = 47
                -- AND count_sold > 0
            ORDER BY SRC.position_in_report
        ";

        // WB
        $query = "
            SELECT
                SRC.count_sold,
                SRC.price_sold_kop,
                SRC.reward_sold_kop,
                MSP.ms_id_new
                , SRC.position_in_report
                , SRC.name
            FROM mp_sales_report_contents AS SRC

            LEFT JOIN product_downloaded AS PD
                ON (SRC.sku = PD.product_mp_id AND PD.user_id = $userId AND PD.mp_id = $mpId)
            LEFT JOIN mp_link_candidates AS LCLO -- соединение с озоном
                ON (PD.id = LCLO.second_mp_product_id AND LCLO.mp_link_type_id = 1 AND LCLO.is_del = 0)
            LEFT JOIN mp_link_candidates AS LC
                ON (LC.mp_link_type_id = 4 AND LC.is_del = 0 AND LCLO.first_mp_product_id = LC.first_mp_product_id)
            LEFT JOIN " . MpMs::tableName() . " AS MS
                ON (LC.second_mp_product_id = MS.id)
            LEFT JOIN ms_products AS MSP
                ON (MS.\"UUID\" = MSP.ms_id) 
                
            WHERE
                SRC.sales_report_id = $salesReportId
                --AND SRC.position_in_report >= 43
                -- AND SRC.position_in_report <= 350
                
                -- AND position_in_report = 47
                -- AND count_sold > 0
                -- AND MSP.ms_id_new IS NULL
            ORDER BY SRC.position_in_report
        ";

        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getProductSoldCount(int $salesReportId, int $mpId, int $userId)
    {
        // для ozon
        $query = "
            SELECT
                MSP.ms_id_new,
                sum(count_sold) AS total
            FROM mp_sales_report_contents AS SRC
            LEFT JOIN product_downloaded AS PD
                ON (SRC.article = PD.vendor_code AND PD.user_id = $userId AND PD.mp_id = $mpId)
            LEFT JOIN " . MpLinkCandidates::tableName() . " AS LC
                ON (LC.mp_link_type_id = 4 AND LC.is_del = 0 AND PD.id = LC.first_mp_product_id)
            LEFT JOIN " . MpMs::tableName() . " AS MS
                ON (LC.second_mp_product_id = MS.id)
            LEFT JOIN ms_products AS MSP
                ON (MS.\"UUID\" = MSP.ms_id) 
                
            WHERE
                SRC.sales_report_id = $salesReportId
                AND count_sold > 0
            GROUP BY MSP.ms_id_new
        ";

        // для WB
        $query = "
            SELECT  
                MSP.ms_id_new
                , sum(count_sold) AS total
            -- , SRC.sku
            FROM mp_sales_report_contents AS SRC
			LEFT JOIN product_downloaded AS PD
                ON (SRC.sku = PD.product_mp_id AND PD.user_id = $userId AND PD.mp_id = $mpId)
			LEFT JOIN mp_link_candidates AS LCS
                ON (LCS.mp_link_type_id = 1 AND LCS.is_del = 0 AND PD.id = LCS.second_mp_product_id)
            LEFT JOIN mp_link_candidates AS LCF
                ON (LCF.mp_link_type_id = 4  AND LCF.is_del = 0 AND LCS.first_mp_product_id = LCF.first_mp_product_id)
            LEFT JOIN mp_ms AS MS
                ON (LCF.second_mp_product_id = MS.id)
           LEFT JOIN ms_products AS MSP
                ON (MS.\"UUID\" = MSP.ms_id) 
           WHERE 
                SRC.sales_report_id = $salesReportId
                AND SRC.count_sold > 0
                -- AND MSP.ms_id_new IS NULL
           GROUP BY MSP.ms_id_new";

        // print_r($query); exit;

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
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'mp_product_barcode', 'reward_sold_kop', 'position_in_report', 'scores_kop'], 'required'],
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'reward_sold_kop', 'position_in_report', 'sku', 'scores_kop'], 'default', 'value' => null],
            [['sales_report_id', 'count_sold', 'price_sold_kop', 'reward_sold_kop', 'position_in_report', 'sku', 'scores_kop'], 'integer'],
            [['timestamp_sent_to_accounting_system'], 'safe'],
            [['mp_product_barcode', 'name', 'article'], 'string'],
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
            'reward_sold_kop' => 'Reward Sold Kop',
            'position_in_report' => 'Position In Report',
            'sku' => 'Sku',
            'name' => 'Name',
            'article' => 'Article',
        ];
    }
}
