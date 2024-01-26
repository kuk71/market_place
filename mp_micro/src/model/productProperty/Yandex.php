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

        // получает список возможных вариантов цвета
        $colors = PD::getColors();

        // поля таблицы со списком товара в которых будет искаться цвет
        // поля введены по приритету. Т.е. при нахождении цвета в одном из полей, продолжать поиск не нужно
        $fields = ['vendor_code', 'name', 'description'];

        // проход по всем товарам
        foreach ($notNormalizeItems as $product) {
            // проход по всем полям в которых ищется цвет
            $normal[$product['id']] = self::findColorsInFields($product, $fields, $colors);
        }

        $color = [];
        foreach ($normal as $key => $item) {
            $color[] = [
                "id" => $key,
                "color" => $item,
            ];
        }

        return $color;
    }

    private static function findColorsInFields(array $prod, array $fields, array $colors): string|null
    {
        $color = NULL;

        foreach ($fields as $field) {
            // проход по всем возможным цветам
            $findColor = self::getColorsFirstPosition($colors, $prod[$field]);

            // выбор одного из найденных цветов по признаку его первого вхождения в поле
            if (count($findColor) !== 0) {
                asort($findColor);
                $color = array_key_first($findColor);

                // цвет найден - поиск в других полях не нужен
                break;
            }
        }

        return $color;
    }

    /**
     * Находит первые позиции искомого цвета в поле
     *
     * @param array $colors - список цветов для поиска
     * @param $haystack - строка в которой ищется цвет
     * @return array - $findColor['Цвет'] = Первая позиция в поле
     */
    private static function getColorsFirstPosition(array $colors, $haystack): array
    {
        $findColor = [];

        foreach ($colors as $color) {
            $position = mb_stripos($haystack, $color);

            if ($position !== false) {
                $findColor[$color] = $position;
            }
        }

        return $findColor;
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