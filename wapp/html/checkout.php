<?php
session_start();
include 'webapp.db.php'; // Inkludera databasanslutningen

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kontrollera session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Anslut till databasen
$conn = connectToDatabase();

// Variabler
$cartProducts = [];
$totalPrice = 0;
$showConfirmation = false;
$message = "";

// Hämta produkter i varukorgen
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
            $totalPrice += $product['total_price'];
        }
        $stmt->close();
    } else {
        $message = "Ett fel inträffade vid hämtning av produkter.";
    }
}

// Hantera slutförande av köp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cartProducts)) {
    $orderId = uniqid(); // Unikt order-ID
    $stmt = $conn->prepare("INSERT INTO orders (order_id, product_id, product_name, price, quantity, total_price, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        // Lägg till varje produkt i beställningen
        foreach ($cartProducts as $product) {
            $quantity = $product['quantity'];
            $total = $product['total_price'];
            $userId = $_SESSION['user_id'];  // Se till att vi får user_id från sessionen

            // Debug: Kontrollera session och produktdata
            // var_dump($userId, $product['id'], $product['name'], $product['price'], $quantity, $total);

            $stmt->bind_param("sisddii", $orderId, $product['id'], $product['name'], $product['price'], $quantity, $total, $userId);

            if (!$stmt->execute()) {
                $message = "Fel vid insättning av beställning: " . $stmt->error;
                break;
            }
        }
        $stmt->close();

        // Rensa varukorgen
        $_SESSION['cart'] = [];
        $message = "Ditt köp har slutförts! Din order-ID är: $orderId.";
        $showConfirmation = true;

        // Omdirigera efter 5 sekunder
        header("refresh:5;url=index.php");  // Sätt omdirigeringen här innan någon annan utdata
        exit();  // Se till att inget annat körs efter header
    } else {
        $message = "Ett fel inträffade vid slutförande av köp: " . $conn->error;
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
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        header { background-color: #343a40; color: white; text-align: center; padding: 20px; margin-bottom: 20px; }
        .checkout-card { background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        footer { background-color: #343a40; color: white; text-align: center; padding: 10px; margin-top: 30px; }
    </style>
</head>
<body>
<header>
    <h1>Kassan</h1>
    <p>Granska och slutför ditt köp!</p>
</header>

<div class="container">
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
                    <div class="checkout-card d-flex justify-content-between align-items-center">
                        <div>
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p>Antal: <?php echo $product['quantity']; ?> | Pris: SEK <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                            <p class="fw-bold">Totalt: SEK <?php echo number_format($product['total_price'], 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <h4>Totalt att betala: SEK <?php echo number_format($totalPrice, 2, ',', '.'); ?></h4>
            <form method="POST">
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
