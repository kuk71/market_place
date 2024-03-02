<?php

namespace market\controllers\ms;

use market\api\MS;
use market\model\db\MoiSkladProduct;

class CreateProducts
{
    public static function createProducts()
    {
        $products = MoiSkladProduct::getAll();
        $i = 1;
        foreach ($products as $product) {
            $uuidNew = MS::createProduct("7a742ee84de14f8ca96a403aa870ecea0f46dd47", $product);

            MoiSkladProduct::addNewUuid($uuidNew, $product['id']);

            echo $i . " - " . $product['id'] . "\n";
            $i++;
            // sleep(1);
        }
    }


}