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
                Api::getProductProperty($apiAcc)
            );
        }

        return self::convertToArrayProductProperty($property, $mpId);
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
            foreach ($prop->images AS $image) {
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
            ];
        }

        return $productProperty;
    }
}