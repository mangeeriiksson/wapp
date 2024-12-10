<?php
session_start();
include 'webapp.db.php'; // Inkludera databasanslutningen

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kontrollera om varukorgen finns i sessionen
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Anslut till databasen
$conn = connectToDatabase();
$message = "";

// Hantera borttagning från varukorgen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
    $productId = intval($_POST['remove_product_id']);
    if (array_key_exists($productId, $_SESSION['cart'])) {
        unset($_SESSION['cart'][$productId]);
        $message = "Produkten togs bort från varukorgen!";
    } else {
        $message = "Den valda produkten finns inte i varukorgen.";
    }
}

// Hämta produkter i varukorgen
$cartProducts = [];
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $types = str_repeat('i', count($_SESSION['cart']));
        $stmt->bind_param($types, ...array_keys($_SESSION['cart']));
        $stmt->execute();
        $result = $stmt->get_result();
        $cartProducts = $result->fetch_all(MYSQLI_ASSOC);

        // Beräkna totalsumman
        foreach ($cartProducts as &$product) {
            $quantity = $_SESSION['cart'][$product['id']] ?? 1;
            $product['quantity'] = $quantity;
            $product['total_price'] = $product['price'] * $quantity;
        }

        $stmt->close();
    } else {
        $message = "Ett fel inträffade vid hämtning av produkter från varukorgen.";
    }
}

// Stäng anslutningen om den fortfarande är öppen
if ($conn && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Varukorg</title>
    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        header { background-color: #343a40; color: white; text-align: center; padding: 20px; margin-bottom: 20px; }
        .cart-container { margin-top: 30px; }
        .cart-card {
            background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }
        .cart-card img { max-width: 100px; height: auto; margin-right: 15px; }
        footer { background-color: #343a40; color: white; text-align: center; padding: 10px 0; margin-top: 30px; }
    </style>
</head>
<body>
<header>
    <h1>Din Varukorg</h1>
    <p>Hantera dina valda produkter!</p>
</header>

<div class="container cart-container">
    <!-- Visa meddelande -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success text-center">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Om varukorgen inte är tom -->
    <?php if (!empty($cartProducts)): ?>
        <div class="row">
            <?php foreach ($cartProducts as $product): ?>
                <div class="col-12">
                    <div class="cart-card d-flex justify-content-between align-items-center">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="details">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="text-success fw-bold">SEK <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                            <p>Antal: <?php echo $product['quantity']; ?> | Totalt: SEK <?php echo number_format($product['total_price'], 2, ',', '.'); ?></p>
                        </div>
                        <form method="POST" action="cart.php" class="ms-auto">
                            <input type="hidden" name="remove_product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Ta bort</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <h4>Totalt att betala: SEK <?php echo number_format(array_sum(array_column($cartProducts, 'total_price')), 2, ',', '.'); ?></h4>
            <a href="checkout.php" class="btn btn-primary">Gå till kassan</a>
        </div>
    <?php else: ?>
        <p class="text-center">Din varukorg är tom.</p>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Made by the hacker'god.</p>
</footer>
</body>
</html>
