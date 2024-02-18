<?php

namespace market\api;

class MS
{
    public static function createProduct(string $apiKey, array $product)
    {
        $curl = curl_init();

        $json = '
        {
            "name": "' . $product['name'] . '",
            "code": "' . $product['code'] . '",
            "article": "' . $product['code'] . '",
            "weight": ' . $product['weight'] . ',
            "uom": {
                "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/uom/19f1edc0-fc42-4001-94cb-c9ec9c62ec10",
                    "metadataHref": "https://api.moysklad.ru/api/remap/1.2/entity/uom/metadata",
                    "type": "uom",
                    "mediaType": "application/json"
                }
            },
            "barcodes": [
                {
                    "code128": "' . $product['code128'] . '"
                }
            ],
            "pathName": "' . $product['pathName'] . '",
            "attributes": [
                {
                    "meta": {
                        "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/metadata/attributes/1fa71d64-cb00-11ee-0a80-054a00035c26",
                        "type": "attributemetadata",
                        "mediaType": "application/json"
                    },
                    "id": "1fa71d64-cb00-11ee-0a80-054a00035c26",
                    "name": "Длина в сантиметрах",
                    "value": ' . $product['length'] . '
                },
                {
                    "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/metadata/attributes/1fa72abb-cb00-11ee-0a80-054a00035c27",
                    "type": "attributemetadata",
                    "mediaType": "application/json"
                    },
                    "id": "1fa72abb-cb00-11ee-0a80-054a00035c27",
                    "name": "Ширина в сантиметрах",
                    "value": ' . $product['width'] . '
                },
                {
                    "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/metadata/attributes/1fa72bda-cb00-11ee-0a80-054a00035c28",
                    "type": "attributemetadata",
                    "mediaType": "application/json"
                    },
                    "id": "1fa72bda-cb00-11ee-0a80-054a00035c28",
                    "name": "Высота в сантиметрах",
                    "value": ' . $product['height'] . '
                },
                {
                    "meta": {
                    "href": "https://api.moysklad.ru/api/remap/1.2/entity/product/metadata/attributes/544c4281-cb02-11ee-0a80-0b8b0003d36d",
                     "type": "attributemetadata",
                    "mediaType": "application/json"
                    },
                    "name": "Бренд",
                    "value": "' . $product['brand'] . '"
                }
            ]
        }';

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.moysklad.ru/api/remap/1.2/entity/product',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $apiKey,
                'Accept-Encoding: gzip',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        return $response['id'];
    }

    public static function createSupply(string $apiKey, string $supply)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.moysklad.ru/api/remap/1.2/entity/supply',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $supply,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $apiKey,
                'Accept-Encoding: gzip',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if (isset($response['id'])) {
            return true;
        }

        return $response;
    }
}