<?php

//unset($_SESSION['game_id']);
//unset($_SESSION['user_id']);
session_destroy();
Header("Location: index.php");
