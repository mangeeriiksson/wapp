<?php
session_start();

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Hantera filuppladdning
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $uploadDir = 'uploads/'; // Katalog för uppladdade filer
    $fileInfo = pathinfo($_FILES['uploaded_file']['name']);
    $fileName = uniqid() . '.' . strtolower($fileInfo['extension']); // Unikt filnamn
    $uploadFile = $uploadDir . $fileName;

    // Skapa uppladdningskatalogen om den inte finns
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);  // Skapar mappen om den inte finns
    }

    // Validera filstorlek (t.ex. max 5 MB)
    if ($_FILES['uploaded_file']['size'] > 5 * 1024 * 1024) { // Maxstorlek: 5 MB
        $uploadMessage = "Filen är för stor. Maxstorlek är 5 MB.";
    }
    // Om filen är godkänd, försök att ladda upp den
    elseif (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadFile)) {
        $uploadMessage = "Filen har laddats upp: " . htmlspecialchars($fileName);
    } else {
        $uploadMessage = "Ett fel uppstod vid uppladdningen.";
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filuppladdning</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .upload-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        .upload-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        footer { 
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>Filuppladdning</h1>
        
        <?php if (!empty($uploadMessage)): ?>
            <div class="alert alert-<?php echo (strpos($uploadMessage, 'har laddats upp') !== false) ? 'success' : 'danger'; ?> text-center">
                <?php echo htmlspecialchars($uploadMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="uploaded_file" class="form-label">Välj en fil:</label>
                <input type="file" name="uploaded_file" id="uploaded_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ladda upp</button>
        </form>

        <a href="index.php" class="btn btn-secondary w-100 mt-3">Tillbaka</a>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Made by the hacker'god.</p>
    </footer>
</body>
</html>
