<?php
/**
 * Hanterar MariaDB-databasen för applikationen med MySQLi.
 */

function connectToDatabase() {
    // Databasens anslutningsuppgifter
    $host = 'mariadb';
    $dbName = 'webapp';
    $username = 'user';
    $password = 'password';

    // Anslut till MariaDB
    $conn = new mysqli($host, $username, $password, $dbName);

    if ($conn->connect_error) {
        die("Fel vid anslutning till databasen: " . $conn->connect_error);
    }

    return $conn;
}

function setupDatabase($conn) {
    // Skapa produkter-tabellen om den inte finns
    $productsTable = "
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255) NOT NULL
        );
    ";
    if (!$conn->query($productsTable)) {
        die("Fel vid skapande av tabellen 'products': " . $conn->error);
    }

    // Kontrollera om produkter-tabellen är tom
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $rowCount = $result->fetch_assoc()['count'] ?? 0;

    if ($rowCount == 0) {
        $conn->query("
            INSERT INTO products (name, price, image) VALUES
            ('Produkt 1', 199.99, 'images/product1.jpg'),
            ('Produkt 2', 299.50, 'images/product2.jpg'),
            ('Produkt 3', 399.00, 'images/product3.jpg'),
            ('Produkt 4', 249.90, 'images/product4.jpg'),
            ('Produkt 5', 499.99, 'images/product5.jpg')
        ") or die("Fel vid insättning av produkter: " . $conn->error);
    }

    // Skapa användare-tabellen om den inte finns
    $usersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0
        );
    ";
    if (!$conn->query($usersTable)) {
        die("Fel vid skapande av tabellen 'users': " . $conn->error);
    }

    // Lägg till adminanvändaren om den inte finns
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $adminExists = $result->fetch_assoc()['count'] ?? 0;

    if (!$adminExists) {
        $defaultPassword = 'admin123'; // Lösenord i klartext
        $conn->query("
            INSERT INTO users (username, password, is_admin) VALUES ('admin', '$defaultPassword', 1)
        ") or die("Fel vid skapande av adminanvändare: " . $conn->error);
    }

    // Skapa orders-tabellen
    createOrdersTable($conn);

    // Skapa password_resets-tabellen
    createPasswordResetsTable($conn);
}

function createOrdersTable($conn) {
    $ordersTable = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(50) NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            total_price DECIMAL(10,2) AS (price * quantity) STORED,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    if (!$conn->query($ordersTable)) {
        die("Fel vid skapande av tabellen 'orders': " . $conn->error);
    }
}

function createPasswordResetsTable($conn) {
    $passwordResetsTable = "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ";
    if (!$conn->query($passwordResetsTable)) {
        die("Fel vid skapande av tabellen 'password_resets': " . $conn->error);
    }
}

// Hämta databasanslutningen och sätt upp tabeller
$conn = connectToDatabase();
setupDatabase($conn);

// Stäng anslutningen
$conn->close();
?>
