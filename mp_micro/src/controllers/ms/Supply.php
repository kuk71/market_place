<?php

namespace market\controllers\ms;

use market\model\db\MoiSkladProduct;
use market\model\db\MsSupply;
use market\model\db\MsSupplyContent;

class Supply
{
    public static function getSupplys()
    {
        MsSupply::clear();
        MsSupplyContent::clear();

        $fileNum = 1;
        while (file_exists("../../supply_{$fileNum}.json")) {
            $content = file_get_contents("../../supply_{$fileNum}.json");
            $content = json_decode($content, true);

            foreach ($content['rows'] as $row) {
                echo $row['id'], "\n";

                $overheadSum = NULL;
                $overheadDistribution = NULL;

                if (isset($row['overhead'])) {
                    $overheadSum = $row['overhead']['sum'];
                    $overheadDistribution = $row['overhead']['distribution'];
                }

                $supllyUuid = $row['id'];

                $supply = [
                    'ms_id' => $supllyUuid,
                    'agent_name' => $row['agent']['name'],
                    'overhead_sum' => $overheadSum,
                    'overhead_distribution' => $overheadDistribution,
                    'name' => $row['name'],
                    'created' => $row['moment'],
                    'organization' => $row['organization']['name'],
                ];

                $supplyId = MsSupply::addSupply($supply);

                foreach ($row['positions']['rows'] as $product) {
                    $uuid = explode("/", $product['assortment']['meta']['href']);

                    // print_r($uuid); exit;

                    $uuid = $uuid[count($uuid) - 1];

                    $product = [
                        'supply_uuid' => $supllyUuid,
                        'uuid' => $uuid,
                        'price' => (int)$product['price'],
                        'quantity' => (int)$product['quantity'],
                    ];

                    MsSupplyContent::addContent($product);
                }
            }


            echo $fileNum;

            $fileNum++;
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