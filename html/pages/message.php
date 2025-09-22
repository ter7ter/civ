<?php
/** @var User $user */
$last = (isset($_REQUEST['last']) ? (int)$_REQUEST['last'] : 0);
$messages = $user->get_messages($last);
$data['messages'] = [];
foreach ($messages as $message) {
    $data['messages'][] = [
        'id' => $message->id,
        'form' => $message->from ? $message->from->login : 'system',
        'to' => $message->to ? $message->to->login : 'system',
        'type' => $message->type,
        'text' => $message->text
    ];
}
$data['turn_status'] = $user->turn_status;