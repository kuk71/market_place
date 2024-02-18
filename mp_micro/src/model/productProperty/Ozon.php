<?php

namespace market\model\productProperty;

use market\api\Ozon as Api;
use market\app\App;

class Ozon
{
    use TraitNormalize;

    public static function normalize(int $mpId)
    {
        self::normalizeData($mpId);
    }

    public static function getProductProperty(int $mpId): array
    {
        // получить параметры доступа к API
        $apiAccesss = self::getKeysByMpId($mpId);

        $property = [];
        // загрузить данные из всех аккаунтов
        foreach ($apiAccesss as $apiAccess) {
            // получить спецификацию продуктов
            $property = array_merge(
                $property,
                Api::getProductProperty($apiAccess)
            );
        }

        $property = self::convertToArrayProductProperty($property, $mpId);

        $property = array_column($property, null, 'product_mp_id');

        // получить массив id товаров
        $productMpIds = array_column($property, 'product_mp_id');

        // получить sku. Нужно для идентификации товара в отчетах комиссионера
        $productSku = [];
        foreach ($apiAccesss as $apiAccess) {
            // получить спецификацию продуктов
            $productSku = array_merge(
                $productSku,
                Api::getProductInfo($apiAccess, $productMpIds)
            );
        }

        $productSku = array_column($productSku, 'sku', 'id');

        // передать SKU в массив свойств товара
        self::addSkuToProperty($property, $productSku);

        return $property;
    }

    private static function addSkuToProperty(&$property, &$productSku)
    {
        foreach ($productSku as $productId => $sku) {
            $property[$productId]['id_for_sold_reports'] = $sku;
        }
    }

    private static function convertToArrayProductProperty($property, int $mpId)
    {
        $productProperty = [];
        foreach ($property as $prop) {
            $color = "";
            $description = "";
            $kit = "";

            foreach ($prop->attributes as $attribute) {
                switch ($attribute->attribute_id) {
                    case(10096):
                        $color = $attribute->values[0]->value;
                        break;
                    case(4191):
                        $description = $attribute->values[0]->value;

                        // удаление HTML тэгов
                        $description = str_replace('<', ' <', $description);
                        $description = strip_tags($description);
                        $description = preg_replace('/\s+/', ' ', $description);

                        break;
                    case(4384):
                        $kit = $attribute->values[0]->value;
                        $kit = preg_replace('/\s+/', ' ', $kit);

                        break;
                }
            }

            $img = [];
            foreach ($prop->images as $image) {
                $img[] = $image->file_name;
            }

            $img = json_encode($img);

            $productProperty[] = [
                "user_id" => App::getUserId(),
                "mp_id" => $mpId,
                "product_mp_id" => $prop->id,
                "vendor_code" => $prop->offer_id ?? null,
                "name" => $prop->name,
                "description" => $description,
                "kit" => $kit,
                "weight" => $prop->weight ?? null,
                "length" => $prop->depth ?? null,
                "width" => $prop->width ?? null,
                "height" => $prop->height ?? null,
                "weight_unit" => $prop->weight_unit,
                "dimension_height" => $prop->dimension_unit,
                "dimension_width" => $prop->dimension_unit,
                "dimension_length" => $prop->dimension_unit,
                "color" => $color,
                "json" => json_encode($prop),
                "img" => $img,
                "barcode" => $prop->barcode,
            ];
        }

        return $productProperty;
    }
}