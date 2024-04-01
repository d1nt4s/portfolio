<?php

namespace Dantes\Montegreen\Commands;

class Start
{
    public $telegram;
    public $keyboards;

    private $hello = <<<EOD
    Найди себе недвижимость с ботом Montenegro Green!
     - Для того, чтобы посмотреть все обьекты выбери
    вкладку "Посмотреть все обьекты"
     - Чтобы выбрать определенный обьект зайди во вкладку
     "Обьекты" и выбери нужный
     - Чтобы подобрать себе обьект по определенным хар-ам
     набери /filtrate и выбери характеристики
    EOD;

    function __construct($telegram)
    {
        $this->telegram = $telegram;
        require_once $GLOBALS['paths']['config'] . '/include.php';
        $this->keyboards = get_keyboard('start');
    }

    function can_handle($parameters)
    {
        if ($parameters['message_text'] == '/start' || $parameters['message_text'] == 'Как пользоваться ботом') {
            return true;
        } else {
            return false;
        }
    }

    function handle($parameters)
    {
        $this->telegram->sendMessage([
            'chat_id' => $parameters['chat_id'],
            'text'=> $this->hello,
            'reply_markup' => json_encode($this->keyboards['base_keyboard']),
        ]);
    }

}