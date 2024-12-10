<?php
session_start();

// Kontrollera om användaren är inloggad
function checkAuth($requiredAdmin = false) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    // Om adminåtkomst krävs, kontrollera användarens adminstatus
    if ($requiredAdmin && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1)) {
        die("Du har inte behörighet att besöka denna sida.");
    }
}

// Funktion för att omdirigera användaren baserat på roll
function redirectBasedOnRole() {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: users.php");
    }
    exit();
}
?>
