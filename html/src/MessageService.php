<?php

namespace App;

/**
 * Сервис для управления сообщениями.
 */
class MessageService
{
    /**
     * Отправить системное сообщение всем пользователям в игре.
     * @param Game $game Объект игры
     * @param string $text Текст сообщения
     */
    public function send_system_to_all(Game $game, $text)
    {
        foreach ($game->users as $user) {
            $message = new Message([
                "form_id" => false,
                "to_id" => $user->id,
                "text" => $text,
                "type" => "system",
            ]);
            $message->save();
        }
    }
}
