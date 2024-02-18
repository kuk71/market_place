<?php

namespace market\controllers\ms;

use market\api\MS;
use market\model\db\MoiSkladProduct;
use market\model\db\MsSupply;
use market\model\db\MsSupplyContent;

class CreateSupply
{
    public static function createSupply()
    {
        $supplys = MsSupply::getAll();

        foreach ($supplys AS $supply) {
            $products = MsSupplyContent::getBySupplayId($supply['ms_id']);

            $positions = "";
            foreach($products AS $product) {
                if ($positions !== "") {
                    $positions .= ",";
                }
                $positions .= "
                    {
                        \"quantity\": {$product['quantity']},
                        \"price\": {$product['price']},
                        \"discount\": 0,
                        \"vat\": 0,
                        \"assortment\": {
                            \"meta\": {
                                \"href\": \"https://api.moysklad.ru/api/remap/1.2/entity/product/{$product['ms_id_new']}\",
                                \"metadataHref\": \"https://api.moysklad.ru/api/remap/1.2/entity/product/metadata\",
                                \"type\": \"product\",
                                \"mediaType\": \"application/json\"
                            }
                        }
                    }";
            }

            $overhead = "";

            if (!is_null($supply['overhead_sum'])) {
                $overhead  = '
                    "overhead": {
                        "sum": ' . $supply['overhead_sum'] . ',
                        "distribution": "' . $supply['overhead_distribution']. '"
                    },';
            }

            $createSupply = '
            {
                "name": "' . $supply['name'] . '",
                "moment": "' . $supply['created'] . '",
                "applicable": true,
                "vatEnabled": false,
                "vatIncluded": true,
                "organization": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/organization/1a40909c-c39b-11ee-0a80-0cc60021bf71",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/organization/metadata",
                        "type": "organization",
                        "mediaType": "application/json"
                    }
                },
                "agent": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/1a52e773-c39b-11ee-0a80-0cc60021bf75",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/metadata",
                        "type": "counterparty",
                        "mediaType": "application/json"
                    }
                },
                "store": {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/store/1a52aa73-c39b-11ee-0a80-0cc60021bf74",
                        "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/store/metadata",
                        "type": "store",
                        "mediaType": "application/json"
                    }
                },
                ' . $overhead . '
                "positions": [' . $positions . ' ]
            }';

            $success = MS::createSupply("7a742ee84de14f8ca96a403aa870ecea0f46dd47", $createSupply);

            if ($success !== true) {
                echo "\n\n";
                print_r($createSupply);
                echo "\n\n";
                print_r($success);
                echo "\n\n";
                exit;
            }

            //print_r($success); exit;

            MsSupply::makeSentById($supply['id']);

            echo $supply['id'] . "\n";
        }
    }
}