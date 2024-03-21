<?php

namespace market\api;

class Wb
{
    public static function fillDimension(int $subjectId, string $apiKey)
    {
        $curl = curl_init();

        $url = "https://suppliers-api.wildberries.ru/content/v2/object/charcs/$subjectId?locale=ru";

        curl_setopt_array($curl, self::getCurlAtrForDimensionPackage($apiKey, $url));
        $responseJson = curl_exec($curl);

        $response = json_decode($responseJson);

        $dimension = self::getParamsDimension($response->data);

        return $dimension;
    }

    private static function getParamsDimension($charcs)
    {
        $dimension = [];

        foreach ($charcs as $charc) {
            if (strpos($charc->name, 'Высота упаковки') !== false) {
                $dimension['height'] = $charc->unitName;
                continue;
            }

            if (strpos($charc->name, 'Ширина упаковки') !== false) {
                $dimension['width'] = $charc->unitName;
                continue;
            }

            if (strpos($charc->name, 'Длина упаковки') !== false) {
                $dimension['length'] = $charc->unitName;
                continue;
            }
        }

        return $dimension;
    }

    public static function getProductProperty(string $apiKey)
    {
        $curl = curl_init();
        $url = "https://suppliers-api.wildberries.ru/content/v2/get/cards/list?locale=ru";
        $method = "POST";
        $key = $apiKey;

        $limit = 100;
        $total = $limit;

        $nmID = 0;
        $updatedAt = "";

        $productProperty = [];
        while ($total === $limit) {
            curl_setopt_array(
                $curl,
                self::getCurlAtrProductProperty($key, $url, $method, $limit, $nmID, $updatedAt)
            );

            $responseJson = curl_exec($curl);
            $response = json_decode($responseJson);

            $productProperty = array_merge(
                $productProperty,
                $response->cards
            );

            $total = $response->cursor->total;
            $nmID = $response->cursor->nmID;
            $updatedAt = $response->cursor->updatedAt;
        }

        return $productProperty;
    }

    public static function getTrashProductProperty(string $apiKey)
    {
        $curl = curl_init();
        $url = "https://suppliers-api.wildberries.ru/content/v2/get/cards/trash?locale=ru";
        $method = "POST";
        $key = $apiKey;

        $limit = 1000;
        $total = $limit;

        $nmID = 0;
        $updatedAt = "";

        $productProperty = [];
        while ($total === $limit) {
            curl_setopt_array(
                $curl,
                self::getCurlAtrProductProperty($key, $url, $method, $limit, $nmID, $updatedAt)
            );

            $responseJson = curl_exec($curl);
            $response = json_decode($responseJson);

            $productProperty = array_merge(
                $productProperty,
                $response->cards
            );

            $total = $response->cursor->total;
            $nmID = $response->cursor->nmID;

            $updatedAt = "";

            if (isset( $response->cursor->updatedAt)) {
                $updatedAt = $response->cursor->updatedAt;
            }
        }

        return $productProperty;
    }

    private static function getCurlAtrProductProperty(
        string $key, string $url, string $method, int $limit, int $nmID, string $updatedAt)
    {
        $cursor["limit"] = $limit;

        if ($updatedAt !== "") {
            $cursor["updatedAt"] = $updatedAt;
            $cursor["nmID"] = $nmID;
        }

        $cursor = json_encode($cursor);

        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => '{
                "settings": {
                    "cursor": ' . $cursor . ',
                    "filter": {
                        "withPhoto": -1
                    }
                }
            }',
            CURLOPT_HTTPHEADER => array(
                "Authorization: $key",
                "Content-Type: application/json"
            ),
        );
    }

    private static function getCurlAtrForDimensionPackage(string $key, string $url): array
    {
        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: $key"
            ),
        );
    }
}