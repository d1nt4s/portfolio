<?php

namespace Dantes\Montegreen\Commands;

class Filtration
{
    private $telegram;
    private $db;
    public $keyboards;

    function __construct($telegram, $db)
    {
        $this->telegram = $telegram;
        require_once $GLOBALS['paths']['config'] . '/include.php';
        $this->keyboards = get_keyboard('filtration');
        $this->db = $db;
    }

    function can_handle($parameters)
    {
        if ($parameters['message_text'] == '/list' || $parameters['message_text'] == 'Список обьектов' || str_contains($parameters['message_text'], '6556type_')) {

            $this->choice_stage($parameters, 'type', 'object_type', "Выберите тип недвижимости и нажмите 'Следующий раздел'");

        } elseif (str_contains($parameters['message_text'], '65561type_next_stage') || str_contains($parameters['message_text'], '4732city_')) { // ТУТ ДОЛЖНО БЫТЬ КАКОЕ ТО ОСОБЕННОЕ КОДОВОЕ ИМЯ, КОТОРОЕ НЕЛЬЗЯ ПОДОБРАТЬ РУКАМИ ИЛИ ПРИДУМАТЬ, КОДОВОЕ ИМЯ СОДЕРЖИТ КЛАСС-СТАДИЮ-ЕЩЕ ЧТО-ТО

            $this->choice_stage($parameters, 'city', 'object_city', "Выберите в каких городах искать недвижимость и нажмите 'Следующий раздел'");

        } elseif (str_contains($parameters['message_text'], '47321city_next_stage') || str_contains($parameters['message_text'], '9921price_')) { // ТУТ ДОЛЖНО БЫТЬ КАКОЕ ТО ОСОБЕННОЕ КОДОВОЕ ИМЯ, КОТОРОЕ НЕЛЬЗЯ ПОДОБРАТЬ РУКАМИ ИЛИ ПРИДУМАТЬ, КОДОВОЕ ИМЯ СОДЕРЖИТ КЛАСС-СТАДИЮ-ЕЩЕ ЧТО-ТО

            $this->choice_stage($parameters, 'price', 'object_price', "Выберите диапазон цены недвижимости и нажмите 'Следующий раздел'");

        } elseif (str_contains($parameters['message_text'], 'match')) { // ТУТ ДОЛЖНО БЫТЬ КАКОЕ ТО ОСОБЕННОЕ КОДОВОЕ ИМЯ, КОТОРОЕ НЕЛЬЗЯ ПОДОБРАТЬ РУКАМИ ИЛИ ПРИДУМАТЬ, КОДОВОЕ ИМЯ СОДЕРЖИТ КЛАСС-СТАДИЮ-ЕЩЕ ЧТО-ТО
            // показ списка обьектов (отправка true + что-то для перехода в handle() )
            return true;
        } else {
            return false;
        }
    }

    function handle($parameters)
    {
        // if (...) // проверка что фильтрация закончилась
    }

    function choice_stage($parameters, $choice_type, $keyboard_name, $answer)
    {

        // Если не колл-бэк, то НАЧАЛО ФИЛЬТРАЦИИ
        if (!$parameters['isCallback'] ) {

            $this->process_keyboard($parameters, $choice_type, $keyboard_name);

            $this->telegram->sendMessage([
                'chat_id' => $parameters['chat_id'],
                'text'=>  $answer,
                'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($this->keyboards[$keyboard_name]),
            ]);

        // ЕСЛИ КОЛЛБЕК ТО ЛИБО ПЕРЕХОД В СЛЕДУЮЩУЮ СТАДИЮ ЛИБО УЧИТЫВАНИЕ ВЫБОРА ПОЛЬЗОВАТЕЛЯ
        } else {

            // ЕСЛИ НЕ ПЕРЕХОД В СЛЕДУЮЩУЮ СТАДИЮ, то записываем выбор пользователя
            if (!str_contains($parameters['message_text'], 'next_stage')) {
                $this->push_choice($parameters, $choice_type);
            }

            $this->process_keyboard($parameters, $choice_type, $keyboard_name);

            // ОТВЕЧАЕТ НА callback
            $this->telegram->answerCallbackQuery([
                'show_alert' => true,
                'callback_query_id' => $parameters['id'],
            ]);

            // Изменение клавиаутуры подстать выбранным категориям пользователя
            $this->telegram->editMessageText([
                'chat_id' => $parameters['chat_id'],
                'message_id' => $parameters['message_id'],
                'text' => $answer,
                'parse_mode' => 'HTML',
                'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($this->keyboards[$keyboard_name]),
            ]);
        }
    }

    function push_choice($parameters, $choice_type)
    {
        // проверить есть ли уже выбор в базе данных и если есть удалить его оттуда, иначе добавить 
        if ($this->db->query("SELECT * FROM user_choices WHERE user_id=? AND type=? AND choice=?", [$parameters['chat_id'], $choice_type, $parameters['message_text']])->find()) {
            $this->db->query("DELETE FROM user_choices WHERE user_id=? AND type=? AND choice=?", [$parameters['chat_id'], $choice_type, $parameters['message_text']]);
        } else {
            $this->db->query("INSERT INTO user_choices (`user_id`, `type`, `choice`) VALUES (?, ?, ?)", [$parameters['chat_id'], $choice_type, $parameters['message_text']]);
        }
    }

    function process_keyboard($parameters, $choice_type, $keyboard_name)
    {
        // вывод и обработка ранее выбранных ВЫБОРОВ
        $res = $this->db->query("SELECT * FROM user_choices WHERE user_id=? AND type=?", [$parameters['chat_id'], $choice_type])->findAll();

        $choices = [];
        foreach($res as $array) {
            if (!in_array($array['choice'], $choices)) {
                array_push($choices, $array['choice']);
            }
        }

        // ОБРАБОТКА КЛАВИАТУРЫ В СООТВЕТСТВИИ С выборами пользователя
        $counter = 0;
        foreach($this->keyboards[$keyboard_name]['inline_keyboard'] as $choice) {
            if (in_array($choice[0]['callback_data'], $choices)) {
                if (substr($this->keyboards[$keyboard_name]['inline_keyboard'][$counter][0]['text'], -1) !== '✅')
                    $this->keyboards[$keyboard_name]['inline_keyboard'][$counter][0]['text'] .= '✅';
            }
            $counter++;
        }
    }

}