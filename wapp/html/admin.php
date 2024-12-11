<?php
session_start();
include 'webapp.db.php'; // Kontrollera att denna fil finns och fungerar

// Anslut till databasen
$conn = connectToDatabase();

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Kontrollera om användaren har adminrättigheter
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData || !$userData['is_admin']) {
    echo "Du har inte behörighet att besöka denna sida.";
    exit();
}

// Variabel för meddelanden
$message = "";

// Hantera tillägg av användare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password, $is_admin);
    if ($stmt->execute()) {
        $message = "Användaren har lagts till.";
    } else {
        $message = "Fel vid tillägg av användare: " . $stmt->error;
    }
}

// Hämta användarlista
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE username LIKE ?");
    $likeQuery = "%$searchQuery%";
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT id, username, is_admin FROM users");
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adminpanel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #74ebd5, #acb6e5);
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .container {
            background: white;
            color: black;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
        }
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
        }
        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        footer {
            text-align: center;
            color: white;
            margin-top: 30px;
            font-size: 0.9em;
        }
        .table thead {
            background-color: #74ebd5;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>Adminpanel</h1>
        <p>Välkommen, <?php echo htmlspecialchars($_SESSION['user']); ?>!</p>
        <div class="admin-nav">
            <a href="shop.php" class="btn btn-primary">Gå till Shop</a>
            <a href="index.php" class="btn btn-success">Gå till Startsidan</a>
            <a href="logout.php" class="btn btn-danger">Logga ut</a>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Hantera användare -->
        <h2 class="mb-4">Användarhantering</h2>
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Sök användare..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="btn btn-primary">Sök</button>
            </div>
        </form>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Användarnamn</th>
                    <th>Admin</th>
                    <th>Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo $user['is_admin'] ? 'Ja' : 'Nej'; ?></td>
                        <td>
                            <form method="POST" action="" class="d-inline">
                                <input type="text" name="new_password" placeholder="Nytt lösenord" required class="form-control d-inline" style="width: 150px;">
                                <button type="submit" name="change_password" class="btn btn-warning btn-sm">Ändra</button>
                            </form>
                            <form method="POST" action="" class="d-inline">
                                <button type="submit" name="delete_user" value="<?php echo $user['id']; ?>" class="btn btn-danger btn-sm">Ta bort</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Adminpanel av Hacker'god.
    </footer>
</body>
</html>
<?php $conn->close(); ?>
