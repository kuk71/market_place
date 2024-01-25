<?php

namespace market\model\productProperty;

use market\api\Yandex as Api;
use market\app\App;
use market\model\db\ProductDownloaded as PD;

class Yandex
{
    use TraitNormalize;

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

    public static function normalize(int $mpId)
    {
        self::normalizeData($mpId);

        // поиск цвета в описании, названии, vendor_code по данным ozon и wb
        self::color($mpId);
    }

    private static function color(int $mpId)
    {
        $color = self::colorFind($mpId);

        PD::saveColor($color);
    }

    private static function colorFind(int $mpId)
    {
        $notNormalizeItems = PD::getNotNormalize(App::getUserId(), $mpId);
        $colors = PD::getColors();

        $fields = ['vendor_code', 'name', 'description'];

        foreach ($notNormalizeItems as $prod) {
            $normal[$prod['id']]['color'] = NULL;

            foreach ($fields as $field) {
                $findColor = [];

                foreach ($colors as $color) {
                    $position = mb_stripos($prod[$field], $color);

                    if ($position !== false) {
                        $findColor[$color] = $position;
                    }
                }

                if (count($findColor) !== 0) {
                    asort($findColor);
                    $normal[$prod['id']]['color'] = array_key_first($findColor);
                    break;
                }
            }
        }

        $color = [];
        foreach ($normal as $key => $item) {
            $color[] = [
                "id" => $key,
                "color" => $item['color']
            ];
        }

        return $color;
    }

    private static function convertToArrayProductProperty($property, int $mpId)
    {
        $productProperty = [];
        foreach ($property as $prop) {
            $description = $prop->offer->description;

            // зачистка от тегов
            $description = str_replace('<', ' <', $description);
            $description = strip_tags($description);
            $description = preg_replace('/\s+/', ' ', $description);

            $img = [];
            foreach ($prop->offer->pictures as $image) {
                $img[] = $image;
            }

            $img = json_encode($img);

            $productProperty[] = [
                "user_id" => App::getUserId(),
                "mp_id" => $mpId,
                "product_mp_id" => $prop->mapping->marketSku,
                "vendor_code" => $prop->offer->offerId,
                "name" => $prop->offer->name,
                "description" => $description,
                "kit" => null,
                "color" => null,
                "img" => $img,
                "weight" => $prop->offer->weightDimensions->weight ?? null,
                "weight_unit" => "кг",

                "length" => $prop->offer->weightDimensions->length ?? null,
                "dimension_length" => "см",
                "width" => $prop->offer->weightDimensions->width ?? null,
                "dimension_width" => "см",
                "height" => $prop->offer->weightDimensions->height ?? null,
                "dimension_height" => "см",

                "json" => json_encode($prop),
            ];
        }

        return $productProperty;
    }
}