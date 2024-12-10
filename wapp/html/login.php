<?php
session_start();
include 'webapp.db.php'; // Inkludera databasanslutningen

$error = ""; // Felmeddelandevariabel

// Kontrollera om inloggningsformuläret har skickats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn = connectToDatabase();

    // Direkt infoga användarindata i SQL-frågan (sårbar för SQL injection)
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Sätt användarens ID och andra sessionvärden
        $_SESSION['user_id'] = $user['id'];  // Lägg till användarens ID i sessionen
        $_SESSION['user'] = $user['username']; // Användarnamn
        $_SESSION['is_admin'] = $user['is_admin']; // Adminstatus

        // Omdirigera baserat på roll
        header('Location: ' . ($user['is_admin'] ? 'admin.php' : 'users.php'));
        exit();
    } else {
        $error = "Felaktigt användarnamn eller lösenord.";
    }

    $conn->close();
}

// Kontrollera om registreringsformuläret har skickats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (strlen($username) < 3 || strlen($password) < 6) {
        $error = "Användarnamnet måste vara minst 3 tecken och lösenordet minst 6 tecken.";
    } else {
        $conn = connectToDatabase();

        // Direkt infoga användarindata i SQL-frågan (sårbar för SQL injection)
        $query = "INSERT INTO users (username, password, is_admin) VALUES ('$username', '$password', 0)";

        if ($conn->query($query)) {
            $_SESSION['user_id'] = $conn->insert_id; // Sätt användarens ID vid registrering
            $_SESSION['user'] = $username;
            $_SESSION['is_admin'] = 0;
            header('Location: users.php');
            exit();
        } else {
            $error = ($conn->errno == 1062) ? "Användarnamnet är redan registrerat." : "Ett fel uppstod vid registrering.";
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Logga in / Registrera</title>
</head>
<body>
    <div class="container mt-5">
        <h1>Logga in / Registrera</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Inloggningsformulär -->
        <form method="POST" action="login.php">
            <input type="hidden" name="login">
            <div class="mb-3">
                <label for="login-username" class="form-label">Användarnamn</label>
                <input type="text" class="form-control" id="login-username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="login-password" class="form-label">Lösenord</label>
                <input type="password" class="form-control" id="login-password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Logga in</button>
        </form>

        <hr>

        <!-- Registreringsformulär -->
        <form method="POST" action="login.php">
            <input type="hidden" name="register">
            <div class="mb-3">
                <label for="register-username" class="form-label">Användarnamn</label>
                <input type="text" class="form-control" id="register-username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="register-password" class="form-label">Lösenord</label>
                <input type="password" class="form-control" id="register-password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">Registrera</button>
        </form>
    </div>
</body>
</html>
