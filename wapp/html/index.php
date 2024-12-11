<?php
include 'webapp.db.php'; // Inkludera databasanslutningen

// Initiera variabler
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];
$error = false;

try {
    // Anslut till databasen
    $conn = connectToDatabase();

    // Hämta användare (testutskrift - kan tas bort i produktion)
    $result = $conn->query("SELECT * FROM users");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . htmlspecialchars($row['id']) . " - Username: " . htmlspecialchars($row['username']) . "<br>";
        }
    }

    // Om en sökning har skickats, hämta matchande produkter
    if (!empty($searchQuery)) {
        // Direkt infoga användarindata i SQL-frågan (sårbar för SQL injection)
        $query = "SELECT * FROM products WHERE name LIKE '%" . $searchQuery . "%'";
        $result = $conn->query($query);
        if ($result) {
            $products = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    $error = true; // Sätt felindikatorn om något går snett
} finally {
    if (isset($conn)) {
        $conn->close(); // Stäng anslutningen
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Produktsök</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        header {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header-buttons a {
            margin: 0 5px;
            color: white;
            text-decoration: none;
            padding: 5px 15px;
            border-radius: 5px;
            background-color: #007bff;
        }
        .header-buttons a:hover {
            background-color: #0056b3;
        }
        .search-container {
            margin: 20px auto;
            max-width: 600px;
        }
        .product-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <h1>Hack1</h1>
    <p>Våga hacka!</p>
    <div class="header-buttons">
        <a href="shop.php">Se Produkter</a>
        <a href="login.php">Logga in</a>
        <a href="uploads.php">Ladda upp</a>
    </div>
</header>

<!-- Sökformulär -->
<div class="search-container">
    <form method="GET" action="">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Sök efter produkter..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-primary">Sök</button>
        </div>
    </form>
</div>

<!-- Produktresultat -->
<div class="container">
    <div class="row">
        <?php if ($error): ?>
            <div class="alert alert-danger text-center">Ett fel inträffade. Försök igen senare.</div>
        <?php elseif (!empty($products)): ?>
            <h3 class="text-center mb-4">Sökresultat för "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? 'default-image.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid mb-2">
                        <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="text-success fw-bold">SEK <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                        <a href="product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-primary w-100">Se detaljer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($searchQuery): ?>
            <div class="col-12 text-center">
                <p>Inga produkter hittades för "<?php echo htmlspecialchars($searchQuery); ?>".</p>
            </div>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>Använd sökfältet för att hitta produkter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer>
    <p>&copy; <?php echo date("Y"); ?> Hacka mig inte! :(</p>
</footer>

</body>
</html>
