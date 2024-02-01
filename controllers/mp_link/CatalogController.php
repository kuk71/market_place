<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MP;
use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkTypes;
use app\models\db\ProductDownloaded;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class CatalogController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public
    function actionIndex()
    {
        $userId = Yii::$app->user->id;

        // проверить все ли товары проверины на соединение в пары

        // получить каталог
        $catalog = [];
        self::catalog($userId, $catalog);



        // получить из БД список товаров из каталога
        $catalogDetail = self::getCatalogDetails($catalog);

        // соеденить какталог с деталями и упорядочить элементы каталога по полю mp_id
        $catalog = self::getCatalogComplete($catalog, $catalogDetail);

        unset($catalogDetail);

        return $this->render('/mp_link/catalog', ['catalog' => $catalog]);
    }

    private static function getCatalogComplete(array $catalog, array $catalogDetail): array
    {
        $catalogComplete = [];

        foreach($catalog AS $key => $item) {
            foreach($item AS $pId) {
                $catalogComplete[$key][] = $catalogDetail[$pId];
            }
        }

        return $catalogComplete;
    }

    private static function getCatalogDetails(array $catalog): array
    {
        $arraySingle = call_user_func_array('array_merge', $catalog);

        $arraySingle = array_unique($arraySingle, SORT_NUMERIC);

        $catalogDetails = ProductDownloaded::getProductByListProductId($arraySingle);

        // преобразовать массив результатов в массив проиндексированный ID товаров
        $pIds = array_column($catalogDetails, 'id');

        return array_combine($pIds, $catalogDetails);
    }

    private static function catalog(int $userId, array &$catalog)
    {
        // получить список маркет плейсов
        $mpIds = ProductDownloaded::getMpIdsByUserId($userId);

        // создать каталог на основе первого маркет плейса
        self::createCatalog($userId, $mpIds[0], $catalog);

        // добавить в каталог связанные маркет плейсы
        foreach ($mpIds as $mpId) {
            self::addCatalogMpLinks($userId, $mpId, $catalog);
        }

         // echo "<pre>"; print_r($catalog); exit;

        // собрать дубли
        self::addDoubles($catalog);

        // удалить из каталога дубли
        self::delDoubles($catalog);
    }

    private static function addDoubles(array &$catalog)
    {
        $keyForDel = [];

        // слить элементы с пересекающимися товарами
        for ($firstKey = 0; $firstKey < count($catalog); $firstKey++) {
            for ($secondKey = ($firstKey + 1); $secondKey < count($catalog); $secondKey++) {
                $intersect = array_intersect($catalog[$firstKey], $catalog[$secondKey]);

                if (count($intersect) !== 0) {
                    $catalog[$firstKey] = array_merge($catalog[$firstKey], $catalog[$secondKey]);

                    $keyForDel[] = $secondKey;
                }
            }
        }

        // удалить из каталога влитые элементы
        for ($i = count($keyForDel) - 1; $i > 0; $i--) {
            unset($catalog[$keyForDel[$i]]);
        }
    }

    private static function delDoubles(array &$catalog)
    {
        // удалить из элементов каталога дублирующиеся товары
        foreach ($catalog as $keyLinks => $links) {
            $notDouble = array_unique($links, SORT_NUMERIC);

            // упорядочить массив
            sort($notDouble);

            $catalog[$keyLinks] = $notDouble;
        }

        // удалить дублирующиеся элементы каталога
        array_unique($catalog, SORT_REGULAR);
    }

    private static function addCatalogMpLinks(int $userId, int $mpId, array &$catalog)
    {
        // получить список соединений в которых участвует $mpId
        $linkTypes = MpLinkTypes::getTypeLinkIdByFirtsMpId($mpId);

        foreach ($linkTypes as $linkType) {
            $secondMpId = $linkType['mp_second_id'];
            // добавить соединенные товары в каталог
            self::addCatalogLinkProducts($userId, $mpId, $secondMpId, $linkType['id'], $catalog);

            // добавить в католог товары из второго в соединении МП не вошедшие в соединение
            self::addCatalogNotLinkProducts($userId, $secondMpId, $linkType['id'], $catalog);
        }
    }

    private static function addCatalogNotLinkProducts(int $userId, int $secondMpId, int $linkTypeId, array &$catalog): void
    {
        // получить список товара из втрого МП участвующего в соединении не вошедших в текущее соединение
        $notLinkProducts = ProductDownloaded::getProductNotLink($userId, $linkTypeId, $secondMpId);

        foreach ($notLinkProducts as $notLinkProduct) {
            $catalog[][0] = $notLinkProduct['id'];
        }
    }

    private static function addCatalogLinkProducts(int $userId, int $firstMpId, int $secondMpId, int $linkTypeId, array &$catalog)
    {
        // получить список товаров текущего соединения
        $products = MpLinkCandidates::getLinkProduct($userId, $linkTypeId, 0);

        foreach ($products as $p) {
            self::addCatalogProduct($firstMpId, $secondMpId, $p['firstId'], $p['secondId'], $catalog);
        }
    }

    private static function addCatalogProduct(int $firstMpId, int $secondMpId, int $firstMpProductId, int $secondMpProductId, array &$catalog): void
    {
        foreach ($catalog as $keyLinks => $links) {
            foreach ($links as $link) {
                if ($link === $firstMpProductId) {
                    $catalog[$keyLinks][] = $secondMpProductId;
                }
            }
        }
    }

    private static function createCatalog(int $userId, int $mpId, array &$catalog): array
    {
        $pIds = ProductDownloaded::getProductIdsByUserIdMpId($userId, $mpId);

        foreach ($pIds as $pId) {
            $catalog[][0] = $pId;
        }

        return $catalog;
    }
}
