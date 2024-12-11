<?php
session_start();
include 'webapp.db.php'; // Inkludera databasanslutningen

// Anslut till databasen
$conn = connectToDatabase();

// Starta varukorgen om den inte redan finns
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Variabler för att lagra data
$cartProducts = [];
$totalPrice = 0;
$showConfirmation = false;
$message = "";

// Hämta produkter i varukorgen
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    // Typbindning för frågan
    $types = str_repeat('i', count($_SESSION['cart']));
    $stmt->bind_param($types, ...array_keys($_SESSION['cart']));
    $stmt->execute();
    $result = $stmt->get_result();
    $cartProducts = $result->fetch_all(MYSQLI_ASSOC);

    // Beräkna totalsumman
    foreach ($cartProducts as $product) {
        $totalPrice += $product['price'];
    }
}

// Hantera slutförande av köp
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($cartProducts)) {
        // Simulera lagring av beställningen (lägg till i en orders-tabell)
        $orderId = uniqid(); // Skapa ett unikt order-ID

        foreach ($cartProducts as $product) {
            $stmt = $conn->prepare("INSERT INTO orders (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            $quantity = $_SESSION['cart'][$product['id']] ?? 1; // Standard till 1 om ej definierad
            $stmt->bind_param("sisdi", $orderId, $product['id'], $product['name'], $product['price'], $quantity);
            $stmt->execute();
        }

        // Rensa varukorgen
        $_SESSION['cart'] = [];
        $message = "Ditt köp har slutförts! Tack för din beställning. Din order-ID är: $orderId.";
        $showConfirmation = true;

        // Omdirigera till startsidan efter 5 sekunder
        header("refresh:5;url=index.php");
    } else {
        $message = "Din varukorg är tom. Inget köp att slutföra.";
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Kassan</title>
    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        header { background-color: #343a40; color: white; padding: 20px 0; text-align: center; }
        .checkout-container { margin-top: 30px; }
        .checkout-card {
            background: white; border: 1px solid #ddd; border-radius: 8px;
            padding: 15px; margin-bottom: 15px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }
        footer { background-color: #343a40; color: white; text-align: center; padding: 10px 0; margin-top: 30px; }
    </style>
</head>
<body>
<header>
    <h1>Kassan</h1>
    <p>Granska och slutför ditt köp!</p>
</header>

<div class="container checkout-container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $showConfirmation ? 'success' : 'danger'; ?> text-center">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php if ($showConfirmation): ?>
            <p class="text-center">Du omdirigeras till startsidan...</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$showConfirmation && !empty($cartProducts)): ?>
        <div class="row">
            <?php foreach ($cartProducts as $product): ?>
                <div class="col-12">
                    <div class="checkout-card d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="details">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="text-success fw-bold">SEK <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <h4>Totalt: SEK <?php echo number_format($totalPrice, 0, ',', '.'); ?></h4>
            <form method="POST" action="checkout.php">
                <button type="submit" class="btn btn-success">Slutför köp</button>
            </form>
        </div>
    <?php elseif (!$showConfirmation): ?>
        <p class="text-center">Din varukorg är tom.</p>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Made by the hacker'god.</p>
</footer>
</body>
</html>
<?php
$conn->close();
?>
