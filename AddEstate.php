<?php

namespace Dantes\Montegreen\Commands;

use Exception;

class AddEstate
{
    private $telegram;
    private $db;
    public $keyboards;
    private $how_to_add = <<<EOD
    Каждому пользователю предоставляется возможность выложить бесплатно 3 обьекта!
    Дополнительные 5 обьектов стоят 2.99$.
    Вы можете оплатить в боте зарубежной или российской картой.
    Каждый обьект существует ровно один год.

    Для добавления обьекта вам необходимо будет заполнить характеристики обьекта,
    а также добавить до 10 фотографий размером до 5МБ каждая.
    Каждый этап процесса будет делательно проинструктирован, тем более что в нем нет ничего сложного!

    После добавления ваш обьект будет проверен нашим специалистом на предмет цензурности.
    При наличии хотя бы одного обьекта в системе вам будут доступны функции управления, т.е
    вы сможете просматривать, изменять и удалять свои обьекты!

    По всем вопросам, предложениям и претензиям пожалуйста обращайтесь сюда @d1ntes.
    Мы открыты ко всем вашим мыслям!

    EOD;

    function __construct($telegram, $db)
    {
        $this->telegram = $telegram;
        $this->db = $db;

        require_once $GLOBALS['paths']['config'] . '/include.php';
        $this->keyboards = get_keyboard('add_estate');
    }

    function can_handle($parameters)
    {
        /* Инструктируем пользователя о функционале добавления обьекта. */
        if ($parameters['message_text'] == '/add' || $parameters['message_text'] == 'Добавить свой обьект') {
            $this->instruction($parameters);
        /* Предоставляем ему выбор: Добавить обьект или купить места. */
        } elseif (str_contains($parameters['message_text'], 'ADD_ESTATE_CONTINUE')) {
            $this->input_or_buy($parameters);
        /* Процесс добавления обьекта, осуществляемый в \Input\InputEstate.php */
        } elseif (str_contains($parameters['message_text'], 'ADD_ESTATE_INPUT_NEW_OBJECT')) {
            $this->set_status($parameters, 'input');
        /* Процесс покупки мест под обьекты, осуществляемый в ... */
        } elseif (str_contains($parameters['message_text'], 'ADD_ESTATE_BUY_OBJECT_PLACES')) {
            $this->set_status($parameters, 'buy');
        }
    }

    function handle($parameters)
    {

    }

    function instruction($parameters)
    {
        // ПРОВЕРИТЬ ЕСТЬ ЛИ ПОЛЬЗОВАТЕЛЬ В БАЗЕ, И ЕСЛИ НЕТ СОЗДАТЬ ЗАПИСЬ О НЕМ
        $this->createNewUser($parameters);

        $this->telegram->sendMessage([
            'chat_id' => $parameters['chat_id'],
            'text'=>  $this->how_to_add,
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($this->keyboards['instruction']),
        ]);
    }

    function input_or_buy($parameters)
    {
        $this->telegram->answerCallbackQuery([
            'show_alert' => true,
            'callback_query_id' => $parameters['id'],
        ]);

        $available_places = $this->getAvailablePlaces($parameters);
        $return_message = "Количество доступных мест для обьектов равняется {$available_places}.";

        if ($available_places == 0) {
            $keyboard = new \Telegram\Bot\Keyboard\Keyboard($this->keyboards['buy']);
        } else {
            $keyboard = new \Telegram\Bot\Keyboard\Keyboard($this->keyboards['input_or_buy']);
        }

        $this->telegram->sendMessage([
            'chat_id' => $parameters['chat_id'],
            'text'=> $return_message,
            'reply_markup' => $keyboard,
        ]);
    }

    function set_status($parameters, $status)
    {
        $this->telegram->answerCallbackQuery([
            'show_alert' => true,
            'callback_query_id' => $parameters['id'],
        ]);

        /* Включаем status ввода обьекта */
        try {
            /* Проверяем, что пользователь существует */       
            if ($this->db->query("SELECT * FROM users WHERE id=?", [$parameters['chat_id']])->find()) {
                /* Проверяем, что другие status не активираны и значение равно NULL*/
                if ($this->db->query("SELECT * FROM users WHERE id=?", [$parameters['chat_id']])->find()['status'] === 'nothing') {
                    $this->db->query("UPDATE users SET status=? WHERE id=?", [$status, $parameters['chat_id']]);
                    $this->db->query("INSERT input_estate (user_id, stage) VALUES (?, ?)", [$parameters['chat_id'], "create_ask_name"]);

                    $this->telegram->sendMessage([
                        'chat_id' => $parameters['chat_id'],
                        'text'=> "Внимание! Вы находитесь в статусе загрузки обьекта. Прочие функции бота недоступны. Для возвращения в обычный режим, пожалуйста, завершите загрузку обьекта!",
                    ]);
                    $GLOBALS['restart_bot'] = true;
                } else {
                    throw new Exception("Предыдущий status не закончен! Status {$status} не может быть установлен");
                }
            } else {
                throw new Exception("Пользователь {$parameters['chat_id']} нет в таблице users!");
            }
        } catch (Exception $e) {
            error_log($e->getMessage() . PHP_EOL, 3, $GLOBALS['paths']['root'] . '/errors.log');
        }

    }

    function createNewUser($parameters)
    {
        if (!$this->db->query("SELECT * FROM users WHERE id=?", [$parameters['chat_id']])->find()) {
            $this->db->query("INSERT INTO users (`id`, `available_places`, `last_message`) VALUES (?, ?, ?)", [$parameters['chat_id'], 3, $parameters['message_text']]);
        } 
    }

    function getAvailablePlaces($parameters)
    {
        if ($this->db->query("SELECT * FROM users WHERE id=?", [$parameters['chat_id']])->find()) {
            return $this->db->query("SELECT * FROM users WHERE id=?", [$parameters['chat_id']])->find()['available_places'];
        } 
    }

}