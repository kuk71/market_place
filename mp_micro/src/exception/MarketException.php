<?php
//
namespace market\exception;

use Exception;

class MarketException extends Exception
{
    function __construct(string $type = "", int $code = 0, string $addition = "")
    {
        $this->message = $this->getMessageText($type, $code, $addition);
    }

    private function getMessageText(string $type, int $code, string $addition = "")
    {
        // TODO - заменить на константы

        $message = [
            'sys' => [
                0 => 'Register_argc_argv is disabled',
            ],
            'action' => [
                0 => 'No key',
                1 => 'Unknown key: ' . $addition,
                2 => 'Unknown market ' . $addition,
                3 => 'User not found ' . $addition,
                4 => 'Link not found ' . $addition,
                5 => 'Market not found ' . $addition,
                6 => 'No API key in DB ' . $addition,
            ],
            'db' => [
                0 => 'db error',
            ],
            'runtime' => [
                0 => 'Unknown dimension: ' . $addition,
            ]

        ];

        if (isset($message[$type][$code])) {
            return $message[$type][$code];
        }

        return "Undefined error";
    }
}