<?php
session_start();

// Ta bort alla sessionens variabler
$_SESSION = [];

// Förstör sessionen på serversidan
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Förstör sessionen
session_destroy();

// Omdirigera användaren till startsidan
header('Location: index.php');
exit();
?>
