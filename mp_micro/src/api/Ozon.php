<?php

namespace market\api;

class Ozon
{
    private static array $productProperty;

    public static function getProductInfo(array $apiAccess, array $productsId): array
    {
        self::$productProperty = [];

        $curl = curl_init();

        $limit = 5;
        $lastId = "";

        $total = $limit;

        $productsIdList = implode(',', $productsId);

        while ($total === $limit) {
            curl_setopt_array($curl, self::getCurlArrayProductInfo($limit, $lastId, $apiAccess, $productsIdList));
            $response = curl_exec($curl);

            $response = json_decode($response, true);

            // print_r($response);

            if (isset($response->code)) {
                // TODO добавить обработку ошибки
                echo "\n\n" . $response->message . "\n\n";
                exit;
            }

            self::$productProperty = array_merge(
                self::$productProperty,
                $response['result']['items']
            );

            $total = $response['total'] ?? 0;
            $lastId = $response['last_id'] ?? 0;
        }

        return self::$productProperty;
    }

    public static function getProductProperty(array $apiAccess): array
    {
        self::$productProperty = [];

        $curl = curl_init();

        $limit = 100;
        $lastId = "";
        
        $filter = '"visibility": "ALL"';
        self::getProductPropertyByFilter($curl, $limit, $lastId, $apiAccess, $filter);

        $filter = '"visibility": "ARCHIVED"';
        self::getProductPropertyByFilter($curl, $limit, $lastId, $apiAccess, $filter);

        return self::$productProperty;
    }

    private static function getProductPropertyByFilter($curl, int $limit, string $lastId, array $apiAccess, string $filter): void
    {
        $total = $limit;
        
        while ($total === $limit) {
            curl_setopt_array($curl, self::getCurlArrayAtrPP($limit, $lastId, $apiAccess, $filter));
            $response = curl_exec($curl);

            $response = json_decode($response);



            if (isset($response->code)) {
                // TODO добавить обработку ошибки
                echo "\n\n" . $response->message . "\n\n";
                exit;
            }

            self::$productProperty = array_merge(
                self::$productProperty,
                $response->result
            );

            $total = $response->total;
            $lastId = $response->last_id;
        }
    }

    private static function getCurlArrayAtrPP(int $limit, string $lastId, array $apiAccess, $filter = ""): array
    {
        return array(
            CURLOPT_URL => 'https://api-seller.ozon.ru/v3/products/info/attributes',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "filter": {
                    ' . $filter . '
                },
                "limit": ' . $limit . ',
                "last_id": "' . $lastId . '",
                "sort_dir": "ASC"
            }',
            CURLOPT_HTTPHEADER => array(
                'Client-Id: ' . $apiAccess['clientId'],
                'Api-Key: ' . $apiAccess['key'],
                'Content-Type: application/json'
            ),
        );
    }

    private static function getCurlArrayProductInfo(int $limit, string $lastId, array $apiAccess, string $productsIdList): array
    {
        return array(
            CURLOPT_URL => 'https://api-seller.ozon.ru/v2/product/info/list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "product_id": [' . $productsIdList . ']
            }',
            CURLOPT_HTTPHEADER => array(
                'Client-Id: ' . $apiAccess['clientId'],
                'Api-Key: ' . $apiAccess['key'],
                'Content-Type: application/json'
            ),
        );
    }
}