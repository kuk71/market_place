<?php

namespace market\model\productProperty;

use market\exception\MarketException;
use market\model\db\ApiKey;
use market\model\db\ProductDownloaded as db;
use market\app\App;

trait TraitNormalize
{
    private static function getKeysByMpId(int $mpId) {
        $apiAccs = ApiKey::getKeysByMpId(App::getUserId(), $mpId);

        if (count($apiAccs) === 0) {
            throw new MarketException("action", 6, $mpId);
        }

        foreach ($apiAccs AS $key => $apiAcc) {
            $apiAccs[$key]  = json_decode($apiAcc, true);
        }

        return $apiAccs;
    }

    public static function normalizeData(int $mpId)
    {
        $notNormalizeItems = db::getNotNormalize(App::getUserId(), $mpId);

        // нормализация размеров
        $normal = self::normalizeSize($notNormalizeItems);

        // нормализация описаний
        self::normalizeText($notNormalizeItems, $normal);

        // сохранить нормализованные данные
        db::saveNormalize($normal);
    }

    private static function normalizeText(array $notNormalizeItems, array &$normal)
    {
        $fields = ['name', 'vendor_code', 'description', 'kit'];

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

    private static function normalizeSize(array $notNormalizeItems)
    {
        $normal = [];

        foreach ($notNormalizeItems as $item) {
            // пересчитать вес
            $weightGr = self::recalculatingSize((float)$item["weight"], $item["weight_unit"]);

            //пересчитать размеры упаковки
            $sizePackageMm = [];
            $sizePackageMm[] = self::recalculatingSize((float)$item["length"], $item["dl"]);
            $sizePackageMm[] = self::recalculatingSize((float)$item["width"], $item["dw"]);
            $sizePackageMm[] = self::recalculatingSize((float)$item["height"], $item["dh"]);

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

    private static function recalculatingSize(float $size, string $dimension)
    {
        $multiplier = 1;
        switch ($dimension) {
            case("kg"):
            case("кг"):
            case('м'):
                $multiplier = 1000;
                break;
            case('см'):
                $multiplier = 10;
                break;
            case('g'):
            case('mm'):
            case('гр'):
            case('мм'):
            case(''):
                $multiplier = 1;
                break;
            default:
                throw new MarketException("runtime", 0, $dimension);
        }

        return (int)($size * $multiplier);
    }
}