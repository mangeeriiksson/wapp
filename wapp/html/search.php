<?php
include 'webapp.db.php'; // Kontrollera att rätt databasanslutning används

$conn = connectToDatabase();

// Hantera filuppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $uploadDir = 'uploads/';
    $fileInfo = pathinfo($_FILES['uploaded_file']['name']);
    $fileName = uniqid() . '.' . strtolower($fileInfo['extension']);
    $uploadFile = $uploadDir . $fileName;
    $fileType = mime_content_type($_FILES['uploaded_file']['tmp_name']);

    // Kontrollera filtyp och storlek
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($fileType, $allowedTypes)) {
        $uploadMessage = "Endast JPG, PNG och GIF tillåts.";
    } elseif ($_FILES['uploaded_file']['size'] > 2 * 1024 * 1024) { // 2 MB
        $uploadMessage = "Filen är för stor. Maxstorlek är 2 MB.";
    } else {
        // Flytta filen till uppladdningskatalogen
        if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadFile)) {
            $uploadMessage = "Filen har laddats upp: " . htmlspecialchars($fileName);
        } else {
            $uploadMessage = "Ett fel uppstod vid uppladdning av filen.";
        }
    }
}

// Kontrollera om en sökning har skickats
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

$products = []; // Standard för produkterna

if ($searchQuery) {
    // Direkt infoga användarindata i SQL-frågan (sårbar för SQL injection)
    $query = "SELECT id, name, price, image FROM products WHERE name LIKE '%" . $searchQuery . "%'";
    $result = $conn->query($query);

    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        die("Fel vid exekvering av SQL-frågan: " . $conn->error);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>Sökresultat</title>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .card img {
            height: 200px;
            object-fit: cover;
        }
        .upload-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .search-container {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Sökresultat för "<?php echo htmlspecialchars($searchQuery); ?>"</h1>

        <!-- Sökfält -->
        <div class="search-container mb-4">
            <form method="GET">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Sök efter produkter..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn btn-primary">Sök</button>
                </div>
            </form>
        </div>

        <!-- Filuppladdningsformulär -->
        <div class="upload-container">
            <h2>Ladda upp en fil</h2>
            <?php if (isset($uploadMessage)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($uploadMessage); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="uploaded_file" class="form-label">Välj en fil:</label>
                    <input type="file" name="uploaded_file" id="uploaded_file" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Ladda upp</button>
            </form>
        </div>

        <?php if (!empty($products)): ?>
            <div class="row mt-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <img src="<?php echo htmlspecialchars($product['image'] ?: 'default-image.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text">Pris: SEK <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                <a href="product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary">Visa produkt</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="mt-4">Inga resultat hittades.</p>
        <?php endif; ?>
    </div>
</body>
</html>
