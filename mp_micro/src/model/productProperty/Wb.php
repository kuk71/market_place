<?php

namespace market\model\productProperty;

use market\api\Wb as Api;
use market\exception\MarketException;
use market\model\db\ApiKey;
use market\model\db\ProductDownloaded as db;
use market\app\App;

class Wb
{
    use TraitNormalize;

    const  API_NAME = 'wb';

    public static function normalize(int $mpId)
    {
        self::normalizeData($mpId);
    }

    public static function getProductProperty(int $mpId)
    {
        // получить параметры доступа к API
        $apiAccs = self::getKeysByMpId($mpId);

        $property = [];
        // загрузить данные из всех аккаунтов
        foreach ($apiAccs as $apiAcc) {
            // получить спецификацию продуктов
            $property = array_merge(
                $property,
                Api::getProductProperty($apiAcc['key'])
            );
        }

        // получить размерности упаковки
        $dimension = self::getDimensionPackage($property, $apiAcc['key']);

        // привести к массиву
        return self::convertToArrayProductProperty($property, $dimension, $mpId);
    }

    private static function getDimensionPackage($property, string $apiKey)
    {
        // создать массив размероностей
        $dimensions = self::createDimensionPackageArr($property);

        // зполнить массив данными
        foreach ($dimensions as $subjectId => $dimension) {
            $dimensions[$subjectId] = Api::fillDimension($subjectId, $apiKey);
        }

        return $dimensions;
    }


    private static function createDimensionPackageArr(array $propertys)
    {
        $dimension = [];
        foreach ($propertys as $item) {
            $dimension[$item->subjectID] = [];
        }

        return $dimension;
    }

    private static function convertToArrayProductProperty($productsPropertys, $dimension, int $mpId)
    {
        $productArr = [];

        foreach ($productsPropertys as $pP) {
            $color = "";
            $weight = 0;
            $weight_unit = "";
            $set = "";

            foreach ($pP->characteristics as $char) {
                switch ($char->id) {
                    case(88952):
                        $weight_unit = "гр";
                        $weight = $char->value;
                        break;
                    case(88953):
                        $weight_unit = "kg";
                        $weight = $char->value;
                        break;
                    case(14177449):
                        if (is_array($char->value)) {
                            $color = implode(", ", $char->value);
                        } else {
                            $color = $char->value;
                        }
                        break;
                    case(378533):
                        $set = implode(", ", $char->value);
                        break;
                }
            }

            $description = preg_replace('/\s+/', ' ', $pP->description);

            $img = [];
            foreach ($pP->photos AS $image) {
                $img[] = $image->big;
            }

            $img = json_encode($img);

            $productArr[] = [
                "user_id" => App::getUserId(),
                "mp_id" => $mpId,
                "product_mp_id" => $pP->nmID,
                "vendor_code" => $pP->vendorCode ?? null,
                "name" => $pP->title,
                "description" => $description,
                "kit" => $set,
                "color" => $color,
                "img" => $img,
                "weight" => $weight,
                "weight_unit" => $weight_unit,

                "length" => $pP->dimensions->length ?? null,
                "dimension_length" => $dimension[$pP->subjectID]['length'],
                "width" => $pP->dimensions->width ?? null,
                "dimension_width" => $dimension[$pP->subjectID]['width'],
                "height" => $pP->dimensions->height ?? null,
                "dimension_height" => $dimension[$pP->subjectID]['height'],

                "json" => json_encode($pP),
            ];
        }

        return $productArr;
    }
}