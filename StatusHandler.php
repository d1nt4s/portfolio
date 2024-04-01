<?php

namespace Dantes\Montegreen;

use Exception;

class StatusHandler
{
    protected $status;
    protected $handler;
    public static function isStatusSet($db, $update)
    {
        if (self::getStatus($db, self::getChatId($update)) !== 'nothing') {
            return true;
        } else {
            return false;
        }         
    }

    public static function getStatus($db, $chat_id)
    {
        if ($db->query("SELECT * FROM users WHERE id=?", [$chat_id])->find()) {
            return $db->query("SELECT * FROM users WHERE id=?", [$chat_id])->find()['status'];
        }
    }

    public static function getChatId($update)
    {
        if (isset($update['callback_query'])) {
            return $update['callback_query']['message']['chat']['id'];
        } elseif (isset($update['message']['text'])) {
            return $update['message']['chat']['id'];
        }
    }

    public function __construct($db, $update)
    {
        $this->status = self::getStatus($db, self::getChatId($update));
    }

    public function addStatusHandler($status_handler)
    {
        if ($status_handler->status_handler_name == $this->status) {
            $this->handler = $status_handler;
        }
    }

    public function startStatusHandler($update)
    {
        try {
            if ($this->handler != null) {
                $this->handler->process($this->getUpdateParameters($update));
            } else {
                throw new Exception("Нужный обработчик не был обнаружен!");
            }
        } catch(Exception $e) {
            error_log($e->getMessage() . PHP_EOL, 3, $GLOBALS['paths']['root'] . '/errors.log');
        }
    }

    function getUpdateParameters($update)
    {
        if (isset($update['callback_query'])) {
            return [
                'isCallback' => true,
                'chat_id' => $update['callback_query']['message']['chat']['id'],
                'message_text' => $update['callback_query']['data'],
                'id' => $update['callback_query']['id'],
                'message_id' => $update['callback_query']['message']['message_id'],
            ];
        } elseif (isset($update['message']['text'])) {
            return [
                'isCallback' => false,
                'chat_id' => $update['message']['chat']['id'],
                'message_text' => $update['message']['text'],
            ];
        } else
            return null;
    }
}