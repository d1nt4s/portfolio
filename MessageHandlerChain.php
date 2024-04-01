<?php

namespace Dantes\Montegreen;

class MessageHandlerChain
{
    public $handlers;
    function __construct()
    {
        $this->handlers = array();
    }

    function add_handler($handler)
    {
        array_push($this->handlers, $handler);
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

    function process_message($update)
    {

        $update_parameters = $this->getUpdateParameters($update);

        foreach ($this->handlers as $handler) {
            if ($handler->can_handle($update_parameters)) {
                $handler->handle($update_parameters);
                break;
            }
        }

    }



}