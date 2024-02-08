<?php

namespace market\controllers;

use market\app\App;
use market\exception\MarketException;
use market\model\db\MpLinkType;
use market\model\db\ProductDownloaded;
use market\model\db\ProductSimilar as PS;

class SimilarProduct
{
    public static function similar(int $linkTypeId)
    {
        // получить ID связываемых маркет плейсов
        $mpIds = MpLinkType::getMpIdByLinkId($linkTypeId);

        if (!$mpIds) {
            throw new MarketException("action", 4, $linkTypeId);
        }

        $mpFirstId = (int)$mpIds['mp_first_id'];
        $mpSecondId = (int)$mpIds['mp_second_id'];



        PS::clear(App::getUserId(), $linkTypeId);
        PS::createNew(App::getUserId(), $linkTypeId, $mpFirstId, $mpSecondId);

        self::similarNum($mpFirstId, $mpSecondId, $linkTypeId);

        if ($mpSecondId !== 4) {
            self::similarText($linkTypeId);
        }

    }

    private static function similarNum(int $mpFirstId, int $mpSecondId, int $linkTypeId)
    {
        $fields = [
            "color", "weight_gr", "size_1_mm", "size_2_mm", "size_3_mm",
        ];

        foreach ($fields as $field) {
            self::createLink($field, $mpFirstId, $mpSecondId, $linkTypeId);
        }

        PS::linkSum($fields, App::getUserId(), $linkTypeId);
    }

    private static function createLink(string $field, int $mpFirstId, int $mpSecondId, int $linkTypeId): void
    {
        $query = ProductDownloaded::createQueryForFindSimilarProduct(App::getUserId(), $field, $mpFirstId, $mpSecondId);

        PS::createLink($query, $field, App::getUserId(), $linkTypeId);
    }

    private static function similarText(int $linkTypeId)
    {
        $limit = 1000;
        $start = 0;
        $similars [] = 1;
        $j = 0;

        while (count($similars) > 0) {
            // получить список связываемых товаров с параметрами для связывания
            $similars = PS::getProductForSimilar(App::getUserId(), $linkTypeId, $limit, $start);

            $params = [];

            foreach ($similars as $similar) {
                $j++;
                $params[] = self::accord($similar);
                echo $similar['id'] . " - " . $j . "\n";
            }

            PS::update($params);

            $start += $limit;
        }
    }

    private static function accord(array $similar)
    {
        $fields = ['name', 'vendor_code', 'description', 'kit'];

        self::convertNullToSpace($similar, $fields);

        $similar_description = 0;
        $similar_name = 0;
        $similar_vendor_code = 0;
        $similar_kit = 0;

        similar_text($similar['first_description'], $similar['second_description'], $similar_description);
        similar_text($similar['first_name'], $similar['second_name'], $similar_name);
        similar_text($similar['first_vendor_code'], $similar['second_vendor_code'], $similar_vendor_code);
        similar_text($similar['first_kit'], $similar['second_kit'], $similar_kit);

        $compareClearFields = self::compareClearFields($similar, $fields);

        return [
            'id' => $similar['id'],
            'similar_description' => $similar_description,
            'similar_name' => $similar_name,
            'similar_vendor_code' => $similar_vendor_code,
            'similar_kit' => $similar_kit,
            'word_equal_name' => $compareClearFields['word_equal_name'],
            'word_equal_vendor_code' => $compareClearFields['word_equal_vendor_code'],
            'word_equal_description' => $compareClearFields['word_equal_description'],
            'word_equal_kit' => $compareClearFields['word_equal_kit'],
        ];
    }

    private static function convertNullToSpace(array &$similar, array $fields)
    {
        foreach ($fields as $field) {
            $similar['first_' . $field] = $similar['first_' . $field] ?? "";
            $similar['second_' . $field] = $similar['second_' . $field] ?? "";
        }
    }

    private static function compareClearFields(array $similar, array $fields): array
    {
        foreach ($fields as $field) {
            $fieldName = 'clear_' . $field;
            $fieldFirst = explode(" ", $similar['first_' . $fieldName]);
            $fieldSecond = explode(" ", $similar['second_' . $fieldName]);

            $fieldName = "word_equal_" . $field;

            if (count($fieldFirst) === 0 || count($fieldSecond) === 0) {
                $compare[$fieldName] = 0;
                continue;
            }

            if (count($fieldFirst) > count($fieldSecond)) {
                $compare[$fieldName] = self::diffArray($fieldSecond, $fieldFirst);
            } else {
                $compare[$fieldName] = self::diffArray($fieldFirst, $fieldSecond);
            }
        }

        return $compare;
    }

    private static function diffArray($first, $second)
    {
        $num = count(array_diff($first, $second));
        $arrLen = count($first);

        return ($arrLen - $num) / $arrLen * 100;
    }
}