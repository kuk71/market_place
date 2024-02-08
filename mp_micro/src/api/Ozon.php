<?php

namespace market\api;

class Ozon
{
    public static function getProductProperty($api)
    {
        $productProperty = [];

        $curl = curl_init();

        $limit = 100;
        $lastId = "";
        $total = $limit;

        while ($total === $limit) {
            curl_setopt_array($curl, self::getCurlArrayAtrPP($limit, $lastId, $api));
            $response = curl_exec($curl);

            $response = json_decode($response);

            if (isset($response->code)) {
                // TODO добавить обработку ошибки
                echo "\n\n{$response->message}\n\n";
                exit;
            }

            $productProperty = array_merge(
                $productProperty,
                $response->result
            );

            $total = $response->total;
            $lastId = $response->last_id;
        }

        return $productProperty;
    }

    private static function getCurlArrayAtrPP(int $limit, string $lastId, array $apiAcc)
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
                },
                "limit": ' . $limit . ',
                "last_id": "' . $lastId . '",
                "sort_dir": "ASC"
            }',
            CURLOPT_HTTPHEADER => array(
                'Client-Id: ' . $apiAcc['clientId'],
                'Api-Key: ' . $apiAcc['key'],
                'Content-Type: application/json'
            ),
        );
    }
}