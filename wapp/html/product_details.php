<?php
session_start();
include 'webapp.db.php'; // Kontrollera att detta är korrekt och innehåller databasanslutningen

// Hämta produktens ID från URL:en och validera det
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo "<p style='text-align:center; font-size:1.2rem;'>Ogiltigt produkt-ID. <a href='index.php'>Tillbaka till startsidan</a></p>";
    exit();
}

// Anslut till databasen
$conn = connectToDatabase();

// Hämta produktinformation från databasen
$query = "SELECT name, description, price, image FROM products WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Fel vid förberedelse av SQL-frågan: " . $conn->error);
}

$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product_details = $result->fetch_assoc();

if (!$product_details) {
    echo "<p style='text-align:center; font-size:1.2rem;'>Produkten hittades inte. <a href='index.php'>Tillbaka till startsidan</a></p>";
    exit();
}

// Stäng förberedelsen
$stmt->close();

// Roliga citat
$fun_quotes = [
    "Livets bästa köp är alltid spontana!",
    "Vem sa att pengar inte kan köpa lycka? De har inte sett detta!",
    "Hacka ditt liv, börja med detta!",
    "Varför vänta? Gör ditt liv 100% bättre nu!",
];
$random_quote = $fun_quotes[array_rand($fun_quotes)];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>Produktdetaljer</title>
    <style>
        body {
            background: linear-gradient(135deg, #74ebd5, #ACB6E5);
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }
        .product-details-container {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            max-width: 900px;
            margin: 20px auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-name {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 20px;
            color: #333;
        }
        .product-price {
            color: #28a745;
            font-size: 1.5rem;
            margin-top: 20px;
        }
        .product-description {
            font-size: 1.1rem;
            margin-top: 20px;
        }
        .quote {
            font-style: italic;
            color: #555;
            margin-top: 40px;
            text-align: center;
        }
        .add-to-cart-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.3s;
        }
        .add-to-cart-btn:hover {
            transform: scale(1.1);
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container product-details-container">
    <h1 class="text-center"><?php echo htmlspecialchars($product_details['name']); ?></h1>
    <img src="<?php echo htmlspecialchars($product_details['image']); ?>" alt="<?php echo htmlspecialchars($product_details['name']); ?>" class="product-image">
    <div class="product-price">SEK <?php echo number_format($product_details['price'], 0, ',', '.'); ?></div>
    <div class="product-description"><?php echo htmlspecialchars($product_details['description']); ?></div>

    <!-- Lägg till i kundvagn-knapp -->
    <form method="POST" action="cart.php">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        <button type="submit" class="btn add-to-cart-btn mt-4 w-100">Lägg i kundvagn</button>
    </form>

    <div class="quote"><?php echo htmlspecialchars($random_quote); ?></div>
</div>

</body>
</html>
