<?php
// отправляет отчёт комиссионера в Мой склад
//
namespace app\controllers\ms\export;

use app\controllers\Exeption;
use app\models\db\MpSalesReportContents;
use app\models\db\MpSalesReports;
use yii\filters\AccessControl;
use yii\rest\Controller;


class SoldController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $salesReportId = 1;
        $mpId = 1;
        $userId = 2;

        $url = "https://api.moysklad.ru/api/remap/1.2/entity/commissionreportin";
        $method = "POST";
        $organization = "1a40909c-c39b-11ee-0a80-0cc60021bf71"; // организация отправителя
        $agent = "f56c6951-cc81-11ee-0a80-070900035ee3"; // организация получателя
        $contract = "3545fb97-cc82-11ee-0a80-05530003d5c7"; // ссылка на контракт (договор комиссии)
        $store = "1a52aa73-c39b-11ee-0a80-0cc60021bf74"; // склад отправитель
        $key = "7a742ee84de14f8ca96a403aa870ecea0f46dd47"; // токен Мой склад

        // получить список товаров для добавления в отгрузку
        $positions = MpSalesReportContents::getProductSold($salesReportId, $mpId, $userId);

        // echo "<pre>"; print_r($positions); exit;

        $period = MpSalesReports::find($salesReportId)->one();

        // echo "<pre>"; print_r($period); exit;

        $positionPart = self::createPositionPart($positions);

        // echo "<pre>"; print_r($positionPart); exit;

        self::sendSalesReportContent($positionPart, $organization, $agent, $contract, $period->date_start, $period->date_end, $key, $url, $method);

        return "Данные успешно импортированы";
    }

    private static function createPositionPart(array $positions): array
    {
        $sold = [];
        $returned = [];

        $quantitySum = 0;

        foreach ($positions AS $product) {
            $quantity =  $product['count_sold'];
            $reward = $product['reward_sold_kop'];
            $vat = 0;

            if ($quantity < 0) {
                $quantity = -$quantity;
                $vat = 0;
                $reward = 0;
            }

            $position = '
            {
                "assortment": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/' . $product['ms_id_new'] . '",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/product/metadata",
                        "type": "product",
                        "mediaType": "application/json"
                    }
                },
                "quantity": ' . $quantity . ',
                "price": ' . $product['price_sold_kop'] . ',
                "vat": ' . $vat . ',
                "reward": ' . $reward . '
            }';

            if ($product['count_sold'] > 0) {
                $sold[] = $position;
            } else {
                $returned[] = $position;
            }

//            echo($product['position_in_report'] . " = " . $product['name'] . " = " . $quantity); echo("<br>");
//            $quantitySum += $quantity;
        }

//        print_r($quantitySum); exit;

        $sold = implode(",", $sold);
        $returned = implode(",", $returned);

        return ['sold' => $sold, 'returned' => $returned];
    }

    private static function sendSalesReportContent($positionPart, $organization, $agent, $contract, $periodStart, $periodEnd, $key, $url, $method)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => '{
                "shared": false,
                "applicable": true,
                "contract": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/contract/' . $contract . '",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/contract/metadata",
                        "type": "contract",
                        "mediaType": "application/json"
                    }
                },
                "agent": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/' . $agent . '",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/metadata",
                        "type": "counterparty",
                        "mediaType": "application/json"
                    }
                },
                "organization": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/organization/' . $organization . '",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/organization/metadata",
                        "type": "organization",
                        "mediaType": "application/json"
                    }
                },
                "positions": [' . $positionPart['sold'] . '],
                "returnToCommissionerPositions": [' . $positionPart['returned'] . '],
                "commissionPeriodStart": "' . $periodStart . ' 0:0:0",
                "commissionPeriodEnd": "' . $periodEnd . ' 23:59:59"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $key,
                'Accept-Encoding: gzip',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo "<pre>" . $response;
    }

}
