<?php

use app\ResearchType;
use app\User;

/** @var User $user */
$age_show = (isset($_REQUEST['age'])) ? (int)$_REQUEST['age'] : $user->age;
$research = [];
$user_research = $user->get_research();
$available_research = $user->get_available_research();
$start_research = (int)@$_REQUEST['rid'];
$data['turn_status'] = $user->turn_status;
if ($start_research && isset($available_research[$start_research]) && $user->turn_status == 'play') {
    $start_research = ResearchType::get($start_research);
    if ($user->start_research($start_research)) {
        $user->save();
    }
}
foreach (ResearchType::getAll() as $res) {
    if ($res->age != $age_show) {
        continue;
    }
    $research[$res->id] = [	'id'	=> $res->id,
                            'title' => $res->get_title(),
                            'm_top' => $res->m_top,
                            'm_left' => $res->m_left,
                            'status' => 'none'
                          ];
    if (isset($user_research[$res->id])) {
        //Уже исследовано
        $research[$res->id]['status'] = 'complete';
    }
    if (isset($available_research[$res->id])) {
        //Можем исследовать
        $research[$res->id]['status'] = 'can';
        $research[$res->id]['turns'] = $user->get_research_need_turns($res);
        if ($research[$res->id]['turns'] == false) {
            $research[$res->id]['turns'] = '--';
        }
    }
    if ($user->process_research_type && $res->id == $user->process_research_type->id) {
        $research[$res->id]['status'] = 'process';
        $research[$res->id]['turns'] = $user->get_research_need_turns();
        if (!$research[$res->id]['turns']) {
            $research[$res->id]['turns'] = '--';
        }
    }
}
$page = 'research';
