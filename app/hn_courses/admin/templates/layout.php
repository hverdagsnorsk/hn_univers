<?php
// layout wrapper
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Admin</title>

    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f5f7fb;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #111;
            color: #fff;
            padding: 20px;
        }

        .sidebar h2 {
            margin-top: 0;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            color: #aaa;
            text-decoration: none;
            border-radius: 6px;
        }

        .sidebar a:hover {
            background: #222;
            color: #fff;
        }

        /* MAIN */
        .main {
            flex: 1;
            padding: 30px;
        }

    </style>
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>HN Admin</h2>

        <a href="?action=courses">📚 Kurs</a>
        <a href="?action=resources">📁 Ressurser</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <?= $content ?>
    </div>

</div>

</body>
</html>