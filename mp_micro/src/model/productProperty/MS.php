<?php

namespace market\model\productProperty;

use market\app\App;
use market\model\db\MS AS dbMS;
use market\model\db\ProductDownloaded as PD;

class MS
{
    use TraitNormalize;

    public static function normalize(int $mpId): void
    {
        self::normalizeData();

        // поиск цвета в описании, названии, vendor_code по данным ozon и wb
        self::color($mpId);
    }

    private static function normalizeData() {
        $notNormalizeItems = dbMS::getAll(App::getUserId());

        exit;

        // нормализация размеров
        $normal = self::normalizeSize($notNormalizeItems);

        // нормализация описаний
        self::normalizeText($notNormalizeItems, $normal);

        // сохранить нормализованные данные
        dbMS::saveNormalize($normal);
    }

    private static function normalizeSize(array $notNormalizeItems)
    {
        $normal = [];

        foreach ($notNormalizeItems as $item) {
            // пересчитать вес
            $weightGr = self::recalculatingSize((float)$item["weight_kg"], 'kg');

            //пересчитать размеры упаковки
            $sizePackageMm = [];
            $sizePackageMm[] = self::recalculatingSize((float)$item["length_sm"], "sm");
            $sizePackageMm[] = self::recalculatingSize((float)$item["width_sm"], "sm");
            $sizePackageMm[] = self::recalculatingSize((float)$item["height_sm"], "sm");

            // упорядочить размеры упаковки по возрастанию
            sort($sizePackageMm);

            $normal[$item['id']] = [
                'id' => $item['id'],
                'weight_gr' => $weightGr,
                'size_1_mm' => $sizePackageMm[0],
                'size_2_mm' => $sizePackageMm[1],
                'size_3_mm' => $sizePackageMm[2],
            ];
        }

        return $normal;
    }

    private static function normalizeText(array $notNormalizeItems, array &$normal): void
    {
        $fields = ['name', 'code', 'article'];

        foreach($notNormalizeItems AS $item) {
            foreach ($fields as $field) {
                $normalize = "";

                if (!is_null($item[$field])) {
                    $normalize = preg_replace("/[^\p{L}\p{N}]/u", " ", $item[$field]);
                    $normalize = preg_replace('/\s+/', ' ', $normalize);
                    $normalize = mb_strtolower($normalize);
                }

                $normal[$item['id']]["clear_" . $field] = $normalize;
            }
        }
    }

    private static function color(int $mpId)
    {
        $color = self::colorFind();

        dbMS::saveColor($color);
    }

    private static function colorFind()
    {
        $notNormalizeItems = dbMS::getAll(App::getUserId());

        // получает список возможных вариантов цвета
        $colors = PD::getColors();

        // поля таблицы со списком товара в которых будет искаться цвет
        // поля введены по приритету. Т.е. при нахождении цвета в одном из полей, продолжать поиск не нужно
        $fields = ['code', 'name', 'article'];

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

    private static function getColorsFirstPosition(array $colors, $haystack): array
    {
        $findColor = [];

        foreach ($colors as $color) {
            $position = false;

            if (!is_null($haystack)) {
                $position = mb_stripos($haystack, $color);
            }

            if ($position !== false) {
                $findColor[$color] = $position;
            }
        }

        return $findColor;
    }

}