<?php

namespace market\controllers;

use market\app\App;
use market\exception\MarketException;
use market\model\db\ApiKey;
use market\model\db\MP;
use market\model\db\MpLinkType;
use market\model\db\ProductDownloaded;

class ProductProperty
{

    // загружает и осчитывает сразу все возмодные для $userId маркет плейсы и их соединения
    public static function all()
    {
        // получить все доступные $userId маркет плейсы
        $mpIds = ApiKey::getMpIdsByUserId(App::getUserId());

        if (count($mpIds) === 0) {
            throw new MarketException("action", 6, "");
        }

        self::getProductPropertyAll($mpIds);
        self::normalizeAll($mpIds);
        self::similarAll($mpIds);
    }

    public static function getProductPropertyAll(array $mpIds)
    {
        foreach ($mpIds AS $mpId)
        {
            self::getProductProperty($mpId);
            echo "$mpId\n";
        }
    }

    public static function normalizeAll(array $mpIds): void
    {
        foreach ($mpIds AS $mpId){
            $marketName = MP::getNameById($mpId);
            $marketClass = "market\\model\\productProperty\\$marketName";

            $marketPP = new $marketClass();
            $marketPP::normalize($mpId);

            echo "$mpId\n";
        }
    }

    public static function similarAll(array $mpIds)
    {
        // получить список Id связей маркет плейсов
        $mpLinkTypeIds = MpLinkType::getLinkTypeIdsByMpIds($mpIds);

        if (count($mpLinkTypeIds) === 0) {
            throw new MarketException("action", 4, "");
        }

        foreach ($mpLinkTypeIds AS $mpLinkTypeId) {
            SimilarProduct::similar($mpLinkTypeId);
        }
    }

    public static function getProductProperty(int $mpId)
    {
        $marketClass = MP::getNameById($mpId);

        if (!$marketClass) {
            throw new MarketException("action", 5, $mpId);
        }

        // получить данные
        $property = self::getProperty($marketClass, $mpId);

        // сохранить данные
        self::saveProperty($mpId, $property);
    }

    public static function normalize(int $mpId): void
    {
        $marketName = MP::getNameById($mpId);

        // mp_micro
        $marketClass = "market\\model\\productProperty\\$marketName";

        $marketPP = new $marketClass();

        $marketPP::normalize($mpId);
    }

    private static function getProperty(string $className, int $mpId)
    {
        $className = "market\\model\\productProperty\\$className";
        $marketPP = new $className();

        return $marketPP::getProductProperty($mpId);
    }

    private static function saveProperty($mpId, array $property)
    {
        ProductDownloaded::clear(App::getUserId(), $mpId);
        ProductDownloaded::saveProductProperty($property);
    }

    private static function saveProperty_old(string $className, array $property)
    {
        $className = "market\\model\\db\\" . $className;

        $marketDB = new $className();

        $marketDB->clear(App::getUserId());
        $marketDB->saveProductProperty($property);
    }
}