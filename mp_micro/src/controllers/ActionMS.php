<?php

namespace market\controllers;

use market\app\App;
use market\controllers\ms\CreateProducts;
use market\controllers\ms\CreateSupply;
use market\controllers\ms\GetProducts;
use market\controllers\ms\Supply;
use market\exception\MarketException;
use market\model\db\User;

class ActionMS
{
    public static function action(array $key)
    {
        if (!isset($key[1]) || !isset($key[2])) {
            throw new MarketException("action", 0);
        }

        $action = $key[1];
        $userId = (int)$key[2];

        // проверка параметра ключа
        self::checkUser($userId);

        // передать $userId в контейнер зависимостей
        App::setUserId($userId);

        switch ($action) {
            case('getProducts'):
                GetProducts::getProducts($userId);
                break;
            case('getSupply'):
                Supply::getSupplys();
                break;
            case('createProducts'):
                CreateProducts::createProducts();
                break;
            case('createSupply'):
                CreateSupply::createSupply();
                break;
            default:
                throw new MarketException("action", 1, $action);
        }
    }

    private static function checkUser(int $userId)
    {
        if (!User::isUserById($userId)) {
            throw new MarketException("action", 3, $userId);
        }
    }
}