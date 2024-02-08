<?php
// точка входа в приложение
// подключает настройки, по входящим аргументам определяет какое действие запрошено

require "../vendor/autoload.php";

use market\controllers\Action;
use market\exception\MarketException;

try {
    if(!isset($argv)) {
        throw new MarketException("sys", 0);
    }

    Action::action($argv);

} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
