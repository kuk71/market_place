<?php
// импортирует данные отчетов о продажах из csv файла
//
namespace app\controllers\ms\api;

use app\controllers\Exeption;
use app\models\db\MP;
use app\models\db\MpSalesReportContents;
use app\models\db\MpSalesReports;
use Exception;
use Yii;
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
        $salesReportId = 2;

        $url = "https://api.moysklad.ru/api/remap/1.2/entity/demand";
        $method = "POST";
        $organization = "1a40909c-c39b-11ee-0a80-0cc60021bf71"; // организация отправителя
        $agent = "f56c6951-cc81-11ee-0a80-070900035ee3"; // организация получателя
        $contract = "3545fb97-cc82-11ee-0a80-05530003d5c7"; // ссылка на контракт (договор комиссии)
        $store = "1a52aa73-c39b-11ee-0a80-0cc60021bf74"; // склад отправитель

        // получить список товаров для добавления в отгрузку
        $positions = MpSalesReportContents::getProductSoldCount($salesReportId);
        echo "<pre>"; print_r($positions); exit;

        $positions = self::createPositionPart();



        return "Данные успешно импортированы";
    }

    private static function createPositionPart()
    {

    }

    private static function addSalesReportContent()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.moysklad.ru/api/remap/1.2/entity/demand',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "name": "Тестовая отгрузка",
    "organization": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/organization/1a40909c-c39b-11ee-0a80-0cc60021bf71",
            "type": "organization",
            "mediaType": "application/json"
        }
    },
    "agent": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/f56c6951-cc81-11ee-0a80-070900035ee3",
            "type": "counterparty",
            "mediaType": "application/json"
        }
    },
    "contract": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/contract/3545fb97-cc82-11ee-0a80-05530003d5c7",
            "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/contract/metadata",
            "type": "contract",
            "mediaType": "application/json"
        }
    },
    "store": {
        "meta": {
            "href": "https://api.moysklad.ru/api/remap/1.2/entity/store/1a52aa73-c39b-11ee-0a80-0cc60021bf74",
            "type": "store",
            "mediaType": "application/json"
        }
    },
    "code": "1243521",
    "moment": "2024-01-16 13:50:24",
    "applicable": true,
    "vatEnabled": true,
    "vatIncluded": true,
    "positions": [
        {
            "quantity": 10,
            "vat": 0,
            "assortment": {
                "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/ed109f72-cb0e-11ee-0a80-069c000fe8fd",
                    "type": "product",
                    "mediaType": "application/json"
                }
            }
        },
        {
            "quantity": 20,
            "vat": 0,
            "assortment": {
                "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/bdbb636c-cb0d-11ee-0a80-103000103560",
                    "type": "product",
                    "mediaType": "application/json"
                }
            }
        }
    ]
}',
            CURLOPT_HTTPHEADER => array(
                'Authorization: 7a742ee84de14f8ca96a403aa870ecea0f46dd47',
                'Accept-Encoding: gzip',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

}
