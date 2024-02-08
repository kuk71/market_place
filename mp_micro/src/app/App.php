<?php

namespace market\app;

use market\exception\MarketException;
use PDO;

class App
{
    private static $configFile = __DIR__ . "/../../config/config.php";
    private static $config;
    private static $db;

    private static $api;

    private static $userId;

    public static function db()
    {
        if (!isset(self::$db)) {
            self::$db = self::getDb();
        }

        return self::$db;
    }

    public static function getMarketClassName(string $marketName)
    {
        if (!isset(self::getConfig()['market'][$marketName])){
            return false;
        }

        return self::getConfig()['market'][$marketName];
    }

    private static function getDb()
    {
        $cnf = self::getConfig()['db'];

        return new PDO("pgsql:host={$cnf['host']};dbname={$cnf['db']}", $cnf['user'], $cnf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private static function getConfig()
    {
        if (!isset(self::$config)) {
            self::$config = require self::$configFile;
        }

        return self::$config;
    }

    public static function setUserId(int $userId)
    {
        self::$userId = $userId;
    }

    public static function getUserId()
    {
        return self::$userId;
    }
}