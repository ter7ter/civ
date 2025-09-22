<?php
// Default title if not set
if (!isset($page_title)) {
    $page_title = 'Игра';
}
?>
<!doctype html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #212529 !important; 
        }
        #cellinfo {
            background-color: #343a40 !important; 
            color: #f8f9fa;
            height: 655px; /* Original height */
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #495057;
            margin-top: 0 !important; /* Override legacy negative margin */
        }
        #game-info-window {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 280px;
            background-color: rgba(33, 37, 41, 0.85) !important; 
            padding: 10px;
            border-radius: .375rem; 
            z-index: 20;
            color: #f8f9fa;
            border: 1px solid #495057;
        }
        .cell-info-img {
            float: none !important; /* Disable float for flexbox layout */
        }
        #message-window {
            border: 1px solid #495057;
            border-radius: .375rem;
        }
    </style>
</head>
<body>
