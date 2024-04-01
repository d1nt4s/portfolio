<?php

namespace Dantes\Montegreen\Statuses\Estate\Input;

class InputEstate
{
    public $status_handler_name = "input";
    private $telegram;
    private $db;
    protected $name;
    protected $type;
    function __construct($telegram, $db)
    {
        $this->telegram = $telegram;
        $this->db = $db;

        $this->name = new Name($telegram, $db);
        $this->type = new Type($telegram, $db);
    }

    public function process($parameters)
    {
        $GLOBALS['restart_bot'] = false;

        $this->telegram->sendMessage([
            'chat_id' => $parameters['chat_id'],
            'text'=> 'Запуск InputEstate',
        ]);

        /* ТУТ ДОЛЖНА БЫТЬ ПРОВЕРКА НА КОМАНДУ /stop ЕСЛИ ВДРУГ ПОЛЬЗОВАТЕЛЬ ЗАХОТЕЛ ОСТАНОВИТЬ ПРОЦЕСС ДОБАВЛЕНИЯ ОБЬЕКТА */

        switch ($this->getStatusStage($parameters['chat_id']))
        {
            case ('create_ask_name'):
                $this->createEstateObject($parameters);
                $this->name->ask($parameters);
                $this->changeStatusStage('put_name_ask_type', $parameters['chat_id']);
                break;
            case ('put_name_ask_type'):
                $this->name->put($parameters, $this->getObjectId($parameters['chat_id']));
                $this->type->ask($parameters);
                $this->changeStatusStage('put_type_ask_city', $parameters['chat_id']);
                break;
        }

    }

    function createEstateObject($parameters)
    {
        $this->db->query("INSERT INTO estate_objects (`owner_id`) VALUES (?)", [$parameters['chat_id']]);
        $object_id = $this->db->query("SELECT LAST_INSERT_ID()")->find()['LAST_INSERT_ID()'];
        $this->db->query("UPDATE input_estate SET object_id=? WHERE user_id=?", [$object_id, $parameters['chat_id']]);
    }

    function getStatusStage($chat_id)
    {
        if ($this->db->query("SELECT * FROM input_estate WHERE user_id=?", [$chat_id])->find()) {
            return $this->db->query("SELECT * FROM input_estate WHERE user_id=?", [$chat_id])->find()['stage'];
        } 
    }

    function getObjectId($chat_id)
    {
        if ($this->db->query("SELECT * FROM input_estate WHERE user_id=?", [$chat_id])->find()) {
            return $this->db->query("SELECT * FROM input_estate WHERE user_id=?", [$chat_id])->find()['object_id'];
        } 
    }

    function changeStatusStage($status_stage, $chat_id)
    {
        if ($this->db->query("SELECT * FROM input_estate WHERE user_id=?", [$chat_id])->find()) {
            $this->db->query("UPDATE input_estate SET stage=? WHERE user_id=?", [$status_stage, $chat_id]);
        } 
    }

}