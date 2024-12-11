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

// 2FA Simple Bypass - Lägger till ett enkelt 2FA-steg
function check2FA() {
    if (isset($_POST['2fa_code'])) {
        $code = trim($_POST['2fa_code']);
        // Sårbarhet: Accepterar tom kod som bypass
        if ($code === '' || $code === '0000') {
            // 2FA Bypass lyckades - lägg till flagga
            $flag = "CTF{2fa_bypass_success}";
            echo "<div class='alert alert-success'>2FA Bypass lyckades! Din flagga är: <strong>$flag</strong></div>";
            return true;
        } else {
            die("Felaktig 2FA-kod! Försök igen.");
        }
    } else {
        // Om 2FA-koden saknas, be om den
        echo "<form method='POST'>
                <label for='2fa_code'>Ange 2FA-kod (lämna tomt för bypass):</label>
                <input type='text' id='2fa_code' name='2fa_code'>
                <button type='submit'>Verifiera</button>
              </form>";
        exit();
    }
}

// Loggfunktion för lösenordsändring - Sårbar för brute-force
function logPasswordChange($username, $newPassword) {
    $logFile = 'password_changes.log';
    $logEntry = date("Y-m-d H:i:s") . " - Användare: $username ändrade lösenord till: $newPassword\n";

    // Lägg till en flagga i loggen för brute-force-scenario
    $flag = "CTF{password_change_logged}";
    $logEntry .= "Flagga: $flag\n";

    // Skriv logg utan att verifiera lösenordsändringar
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Hämta flagga för visning vid specifika åtgärder
function getFlag($scenario) {
    $flags = [
        '2fa_bypass' => 'CTF{2fa_bypass_success}',
        'password_change' => 'CTF{password_change_success}',
        'password_log' => 'CTF{password_change_logged}'
    ];

    return $flags[$scenario] ?? 'Ingen flagga tillgänglig.';
}
?>
