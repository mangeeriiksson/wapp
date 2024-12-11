<?php
include 'auth.php'; // Inkludera autentiseringslogik
include 'webapp.db.php'; // Inkludera databasanslutningen

checkAuth(); // Kontrollera att användaren är inloggad

// Anslut till databasen
$conn = connectToDatabase();

// Hämta användarinformation
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Användarinformation kunde inte hämtas.");
}

// Hantera filuppladdning
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $uploadDir = 'uploads/';
    $fileInfo = pathinfo($_FILES['uploaded_file']['name']);
    $fileName = uniqid() . '.' . strtolower($fileInfo['extension']);
    $uploadFile = $uploadDir . $fileName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Skapa uppladdningskatalogen om den inte finns
    }

    $fileType = mime_content_type($_FILES['uploaded_file']['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!in_array($fileType, $allowedTypes)) {
        $uploadMessage = "Endast JPG, PNG och PDF-filer är tillåtna.";
    } elseif ($_FILES['uploaded_file']['size'] > 2 * 1024 * 1024) { // Max 2 MB
        $uploadMessage = "Filen är för stor. Maxstorlek är 2 MB.";
    } elseif (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadFile)) {
        $uploadMessage = "Filen har laddats upp: " . htmlspecialchars($fileName);
    } else {
        $uploadMessage = "Ett fel uppstod vid uppladdning.";
    }
}

// Ny funktion: Lösenordsändring utan nuvarande lösenordsverifiering
$passwordChangeMessage = '';
$flagMessage = ''; // Variabel för att visa flaggan

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPassword = trim($_POST['new_password']);
    
    // Ingen verifiering av nuvarande lösenord, uppdatera direkt
    $updateQuery = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $newPassword, $_SESSION['user']);
    
    if ($stmt->execute()) {
        $passwordChangeMessage = "Lösenordet har ändrats till: " . htmlspecialchars($newPassword);
        
        // Lägg till flaggan här
        $flagMessage = "Din flagga är: <strong>CTF{password_change_success}</strong>";
    } else {
        $passwordChangeMessage = "Ett fel uppstod vid lösenordsändring.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Användarsida</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .user-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="user-container">
        <h1>Välkommen, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p>Detta är din personliga användarsida.</p>
        <ul>
            <li><strong>Användarnamn:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
            <li><strong>Lösenord:</strong> <?php echo htmlspecialchars($user['password']); ?></li> <!-- Visar lösenordet i klartext -->
            <li><strong>Adminstatus:</strong> <?php echo $user['is_admin'] ? 'Ja' : 'Nej'; ?></li>
        </ul>

        <hr>

        <!-- Filuppladdning -->
        <h3>Ladda upp en fil</h3>
        <?php if (!empty($uploadMessage)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($uploadMessage); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="uploaded_file" class="form-label">Välj en fil:</label>
                <input type="file" name="uploaded_file" id="uploaded_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ladda upp</button>
        </form>

        <hr>

        <!-- Ny sektion för lösenordsändring -->
        <h3>Ändra lösenord</h3>
        <?php if (!empty($passwordChangeMessage)): ?>
            <div class="alert alert-info"><?php echo $passwordChangeMessage; ?></div>
        <?php endif; ?>
        <?php if (!empty($flagMessage)): ?>
            <div class="alert alert-success"><?php echo $flagMessage; ?></div>
        <?php endif; ?>
        <form method="POST" action="users.php">
            <div class="mb-3">
                <label for="new-password" class="form-label">Nytt lösenord</label>
                <input type="text" class="form-control" id="new-password" name="new_password" required>
            </div>
            <button type="submit" class="btn btn-warning w-100">Ändra lösenord</button>
        </form>

        <hr>

        <!-- Navigering -->
        <h3>Utforska</h3>
        <a href="shop.php" class="btn btn-success w-100 mb-3">Gå till shoppen</a>
        <a href="uploads.php" class="btn btn-info w-100 mb-3">Visa uppladdningar</a>

        <hr>

        <!-- Logga ut -->
        <a href="logout.php" class="btn btn-danger w-100 mt-3">Logga ut</a>
    </div>
</body>
</html>
