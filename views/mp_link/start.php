<?php
// получить список сосчитанных типов соединения маркет плейсов

use app\models\db\ProductSimilar;

$userId = Yii::$app->user->id;
// $userId = 0;
$availableLinkTypes = ProductSimilar::getLinkTypeByUserId($userId);

echo "<h1>Связать товары маркет плейсов</h1>";

if (count($availableLinkTypes) === 0) {
    echo "Нет данных для связывания.";
} else {
    echo "<div>";
    foreach($availableLinkTypes AS $linkType){
        if ($linkType['id'] != 4) {
            echo "<a class='btn btn-primary m-3' href='/mp_link/auto?linkType={$linkType['id']}' role='button'>{$linkType['mp_first_name']} / {$linkType['mp_second_name']}</a>";
        }
    }
    echo "</div>";

    echo "<div>";
    echo "<a class='btn btn-primary m-3' href='/mp_link/catalog' role='button'>Создать единый каталог товара</a>";
    echo "</div>";
    echo "<div>";
    echo "<a class='btn btn-primary m-3' href='/mp_link/auto-ms?linkType=4' role='button'>Ozon / Мой склад</a>";

    echo "</div>";

}