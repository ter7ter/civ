<?php
ob_start();
require_once("includes.php");

// Устанавливаем обработчик ошибок, который превращает их в исключения
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    session_start();
    if (isset($_REQUEST['method'])) {
        $page = $_REQUEST['method'];
    } else {
        $page = 'map';
    }

    $page = preg_replace('/[^a-z0-9_]+/', '', $page);
    MyDB::start_transaction();
    if ($page == 'login' && isset($_REQUEST['gid']) && isset($_REQUEST['uid'])) {
        $game = Game::get((int)$_REQUEST['gid']);
        if (!$game) {
            die('game error');
        }

        if ($game->turn_type == 'onewindow') {
            $user_id = $game->getActivePlayer();
            if (!$user_id) {
                // Fallback to the selected user if no active player is found for some reason
                $user_id = (int)$_REQUEST['uid'];
            }
            $user = User::get($user_id);
        } else {
            $user = User::get((int)$_REQUEST['uid']);
        }

        if (!$user || $user->game != $game->id) {
            die('user error');
        }
        $_SESSION['game_id'] = $game->id;
        $_SESSION['user_id'] = $user->id;
        $page = 'map';
    }
    if (!file_exists("pages/{$page}.php")) {
        die('404 Not found');
    }
    $page_no_login = ['selectgame', 'creategame', 'gameinfo', 'login', 'editgame'];
    if (isset($_SESSION['game_id'])) {
        $game = Game::get($_SESSION['game_id']);
        $user = User::get($_SESSION['user_id']);
    } elseif (!in_array($page, $page_no_login)) {
        $page = 'selectgame';
    }
    $error = false;
    $data = [];
    include "pages/{$page}.php";

    if (!isset($GLOBALS['transaction_ended'])) {
        MyDB::end_transaction();
    }

    if (isset($_REQUEST['json'])) {
        if ($error) {
            $response = ['status' => 'error',
                'error' => $error];
        } else {
            $response = ['status' => 'ok',
                'data' => $data];
        }
        print json_encode($response);
    } else {
        include "templ/{$page}.php";
    }

} catch (Throwable $e) {
    // Ловим любую ошибку или исключение
    if (isset($_REQUEST['json'])) {
        // Если это был JSON запрос, возвращаем ошибку в JSON
        http_response_code(500);
        $error_details = [
            'status' => 'error',
            'error' => "Критическая ошибка на сервере: " . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        // Очищаем буфер вывода, чтобы наша ошибка была единственным выводом
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        print json_encode($error_details);
    } else {
        // Если это обычный запрос, выводим ошибку в HTML
        echo "<pre>Критическая ошибка на сервере:\n";
        echo htmlspecialchars($e->getMessage()) . "\n";
        echo "Файл: " . htmlspecialchars($e->getFile()) . "\n";
        echo "Строка: " . htmlspecialchars($e->getLine()) . "\n";
        echo "\nСтек вызовов:\n" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }
} finally {
    // Восстанавливаем стандартный обработчик ошибок
    restore_error_handler();
}

if (ob_get_level() > 0) {
    ob_end_flush();
}