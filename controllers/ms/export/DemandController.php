<?php
// отправляет отгрузку в Мой склад
//
namespace app\controllers\ms\export;

use app\controllers\Exeption;
use app\models\db\MpSalesReportContents;
use yii\filters\AccessControl;
use yii\rest\Controller;


class DemandController extends Controller
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
        $salesReportId = 112;

        $url = "https://api.moysklad.ru/api/remap/1.2/entity/demand";
        $method = "POST";
        $name = "2 - Тестовая отгрузка WB";
        $organization = "1a40909c-c39b-11ee-0a80-0cc60021bf71"; // организация отправителя
        $agent = "607457a2-e110-11ee-0a80-0379000986a3"; // организация получателя
        $contract = "74a26e48-e110-11ee-0a80-040b000904c3"; // ссылка на контракт (договор комиссии)
        $store = "1a52aa73-c39b-11ee-0a80-0cc60021bf74"; // склад отправитель
        $key = "7a742ee84de14f8ca96a403aa870ecea0f46dd47"; // токен Мой склад

        // получить список товаров для добавления в отгрузку
        $positions = MpSalesReportContents::getProductSoldCount($salesReportId, 2, 2);

        // echo "<pre>"; print_r($positions); exit;

        $positionPart = self::createPositionPart($positions);

        // echo "<pre>"; print_r($positionPart); exit;

        self::sendDemandContent($positionPart, $name, $organization, $agent, $contract, $store, $key, $url, $method);

        return "Данные успешно импортированы";
    }

    private static function createPositionPart(array $positions): string
    {
        $positionPart = [];
        foreach ($positions AS $product) {
            $positionPart[] = '
            {
                "quantity": ' . $product['total'] . ',
                "vat": 0,
                "assortment": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/' . $product['ms_id_new'] . '",
                        "type": "product",
                        "mediaType": "application/json"
                    }
                }
            }';
        }

        return implode(",", $positionPart);
    }

    private static function sendDemandContent($positionPart, $name, $organization, $agent, $contract, $store, $key, $url, $method)
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
    "name": "' . $name . '",
    "organization": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/organization/' . $organization . '",
            "type": "organization",
            "mediaType": "application/json"
        }
    },
    "agent": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/' . $agent . '",
            "type": "counterparty",
            "mediaType": "application/json"
        }
    },
    "contract": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/contract/' . $contract . '",
            "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/contract/metadata",
            "type": "contract",
            "mediaType": "application/json"
        }
    },
    "store": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/store/' . $store . '",
            "type": "store",
            "mediaType": "application/json"
        }
    },
    "code": "1243521",
    "moment": "2024-01-16 13:50:24",
    "applicable": true,
    "vatEnabled": true,
    "vatIncluded": true,
    "positions": [' . $positionPart . ']
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
