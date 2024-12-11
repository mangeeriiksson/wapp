<?php
session_start();
include 'webapp.db.php'; // Inkludera databasanslutningen

// Anslut till databasen
$conn = connectToDatabase();

// Starta varukorgen om den inte redan finns
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Kontrollera om användaren är admin
$is_admin = false;
if (isset($_SESSION['user'])) {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['user']);
    $stmt->execute();
    $stmt->bind_result($is_admin_result);
    $stmt->fetch();
    $is_admin = $is_admin_result;
    $stmt->close();
}

// Hantera tillägg till varukorgen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += 1; // Öka kvantitet
    } else {
        $_SESSION['cart'][$productId] = 1; // Lägg till produkten
    }
    $message = "Produkten har lagts till i varukorgen!";
}

// Hantera filuppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['product_image']) && isset($_SESSION['user'])) {
    $targetDir = "uploads/";
    $fileInfo = pathinfo($_FILES['product_image']['name']);
    $fileName = uniqid() . '.' . strtolower($fileInfo['extension']);
    $targetFile = $targetDir . $fileName;
    $fileType = mime_content_type($_FILES['product_image']['tmp_name']);

    // Kontrollera om filen är en bild och flytta till uppladdningskatalogen
    if (in_array($fileType, ['image/jpeg', 'image/png', 'image/gif']) && $_FILES['product_image']['size'] <= 500000) {
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            $message = "Filen " . htmlspecialchars($fileName) . " har laddats upp.";
        } else {
            $message = "Ett fel uppstod vid uppladdningen.";
        }
    } else {
        $message = "Ogiltig filtyp eller för stor fil.";
    }
}

// Hantera sökning
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];

// Direkt infoga användarindata i SQL-frågan (sårbar för SQL injection)
$query = "SELECT * FROM products";
if (!empty($searchQuery)) {
    $query .= " WHERE name LIKE '%" . $searchQuery . "%'";
}
$query .= " LIMIT 20";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    die("Fel vid exekvering av SQL-frågan: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>Shop</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .product-card img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .search-container {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <h1 class="text-center py-3">Välkommen till shopen!</h1>
    <div class="d-flex justify-content-end gap-2 px-3">
        <?php if (isset($_SESSION['user'])): ?>
            <a href="logout.php" class="btn btn-secondary">Logga ut</a>
            <a href="cart.php" class="btn btn-primary">Varukorg (<?php echo array_sum($_SESSION['cart']); ?>)</a>
            <?php if ($is_admin): ?>
                <a href="admin.php" class="btn btn-danger">Adminpanel</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">Logga in</a>
            <a href="register.php" class="btn btn-secondary">Registrera</a>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <!-- Sökruta -->
    <div class="search-container">
        <form method="GET" action="shop.php">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Sök efter produkter..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="btn btn-primary">Sök</button>
            </div>
        </form>
    </div>

    <!-- Filuppladdning -->
    <?php if (isset($_SESSION['user']) && $is_admin): ?>
        <div class="my-4">
            <h3>Ladda upp en produktbild</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" name="product_image" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Ladda upp</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Produkter -->
    <div class="row">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="text-success fw-bold">SEK <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <?php if (isset($_SESSION['user'])): ?>
                                <form method="POST" action="shop.php">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-primary w-100">Lägg till i varukorg</button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Logga in för att lägga till i varukorgen.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Inga produkter hittades.</p>
        <?php endif; ?>
    </div>
</div>

<footer class="text-center py-3">
    <p>&copy; <?php echo date("Y"); ?> Made by the hacker'god.</p>
</footer>

</body>
</html>
