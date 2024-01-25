<?php

namespace market\controllers;

use market\app\App;
use market\exception\MarketException;
use market\model\db\User;

class Action
{
    public static function action(array $key)
    {
        if (!isset($key[1]) || !isset($key[2])) {
            throw new MarketException("action", 0);
        }

        $action = $key[1];
        $userId = (int)$key[2];

        if (isset($key[3])) $mpLinkTypes = self::getMpLinkTypes($key[3]);

        // проверка параметра ключа
        self::checkUser($userId);

        // передать $userId в контейнер зависимостей
        App::setUserId($userId);

        switch ($action) {
            case('get'):
                ProductProperty::getProductProperty($mpLinkTypes[0]);
                break;
            case('normal'):
                ProductProperty::normalize($mpLinkTypes[0]);
                break;
            case('similar'):
                SimilarProduct::similar($mpLinkTypes[0]);
                break;
            case('all'):
                ProductProperty::all();
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

    private static function getMpLinkTypes(string $strMpLinkTypes)
    {
        $mpLinkTypes = explode(",", $strMpLinkTypes);

        foreach ($mpLinkTypes as $key => $mpLinkType) {
            $mpLinkTypes[$key] = (int)$mpLinkType;
        }

        return $mpLinkTypes;
    }
}