<?php

namespace market\controllers\ms;

use market\model\db\MoiSkladProduct;

class GetProducts
{
    public static function getProducts(int $userId)
    {
        MoiSkladProduct::clear($userId);

        $content = file_get_contents("../../product.json");

        $content = json_decode($content, true);

        $contentParse = [];
        foreach ($content['rows'] as $row) {
            $product = [
                'user_id' => $userId,
                'ms_id' => $row['id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'article' => $row['article'],
                'brand' => self::getAttributeValue($row, 'Бренд'),
                'pathName' => $row['pathName'],
                'code128' => self::getCode128($row),
                'weight' => (float)$row['weight'],
                'length' => (float)self::getAttributeValue($row, 'Длина'),
                'width' => (float)self::getAttributeValue($row, 'Ширина'),
                'height' => (float)self::getAttributeValue($row, 'Высота'),
            ];

            MoiSkladProduct::addProduct($product);
        }
    }

    private static function getCode128(array $row): string
    {
        $code128 = '';
        if (!isset($row['barcodes'])) {
            return $code128;
        }

        foreach ($row['barcodes'] as $barcode) {
            if (isset($barcode['code128'])) {
                $code128 = $barcode['code128'];
                break;
            }
        }

        return $code128;
    }


    private static function getAttributeValue(array $row, string $attributeName)
    {
        $attributeValue = "";

        if (!isset($row['attributes'])) {
            return $attributeValue;
        }

        foreach ($row['attributes'] as $attribute) {
            if ($attribute['name'] === $attributeName) {
                $attributeValue = $attribute['value'];

                break;
            }
        }

        return $attributeValue;
    }
}