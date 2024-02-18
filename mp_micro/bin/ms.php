<?php
// точка входа в приложение
// подключает настройки, по входящим аргументам определяет какое действие запрошено

use market\controllers\ActionMS;
use market\exception\MarketException;

require "../vendor/autoload.php";



try {
    if(!isset($argv)) {
        throw new MarketException("sys", 0);
    }

    ActionMS::action($argv);

} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}