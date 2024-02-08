<?php

namespace market\api;

class Yandex
{
    public static function getProductProperty($api)
    {
        $productProperty = [];

        $curl = curl_init();

        $limit = 100;
        $pageToken = "";
        $nextPageToken = "";

        while (!is_null($nextPageToken)) {
            curl_setopt_array($curl, self::getCurlArrayAtrPP($limit, $nextPageToken, $api));
            $response = curl_exec($curl);

            $response = json_decode($response);

            if ($response->status !== "OK") {
                // TODO добавить обработку ошибки
                echo "\n\n{$response->status->errors[0]->message}\n\n";
                exit;
            }

            $productProperty = array_merge(
                $productProperty,
                $response->result->offerMappings
            );

            $nextPageToken = ($response->result->paging->nextPageToken ?? NULL);
        }

        return $productProperty;
    }

    private static function getCurlArrayAtrPP(int $limit, string $pageToken, array $apiAcc)
    {
        if ($pageToken !== "") {
            $pageToken = "&page_token=$pageToken";
        }

        $url = "https://api.partner.market.yandex.ru/businesses/{$apiAcc['businessId']}/offer-mappings?limit={$limit}{$pageToken}";

        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $apiAcc['key']
            ),
        );
    }
}