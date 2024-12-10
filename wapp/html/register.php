<?php
session_start();
include 'webapp.db.php'; // Kontrollera att filen är korrekt och innehåller databasanslutningen

$conn = connectToDatabase();
$error = $success = ""; // För feedback till användaren

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hämta och sanera inmatning
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Kontrollera att fälten är ifyllda
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Alla fält måste fyllas i!";
    } elseif ($password !== $confirm_password) {
        $error = "Lösenorden matchar inte!";
    } elseif (strlen($password) < 6) {
        $error = "Lösenordet måste vara minst 6 tecken långt.";
    } else {
        // Kontrollera om användarnamnet redan finns
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) {
            die("Fel vid förberedelse av SQL-frågan: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Användarnamnet är redan upptaget!";
        } else {
            // Lagra lösenord i klartext
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if (!$stmt) {
                die("Fel vid förberedelse av SQL-frågan: " . $conn->error);
            }
            $stmt->bind_param("ss", $username, $password);

            if ($stmt->execute()) {
                $success = "Registreringen lyckades! Du kan nu logga in.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Ett fel uppstod vid registreringen. Försök igen senare.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrera dig</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .register-form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }
        input {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="register-form">
        <h1>Registrera dig</h1>
        <form method="POST">
            <label for="username">Användarnamn:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Lösenord:</label>
            <input type="password" name="password" id="password" required>
            <label for="confirm_password">Bekräfta lösenord:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <button type="submit">Registrera</button>
        </form>
        <?php 
        if (!empty($error)) { 
            echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; 
        } 
        if (!empty($success)) { 
            echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; 
        } 
        ?>
    </div>
</body>
</html>
