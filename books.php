
<?php
// Allow Angular to access the PHP API
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "angularapp3_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$conn->set_charset("utf8mb4");

$method = $_SERVER["REQUEST_METHOD"];

// Read all books
if ($method === "GET") {
    $result = $conn->query("SELECT * FROM books ORDER BY id");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

// Add a book
if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare(
        "INSERT INTO books (title, author, description) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss",
        $data["title"], $data["author"], $data["description"]
    );
    $stmt->execute();

    echo json_encode(["message" => "Book added"]);
}

// Update a book
if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare(
        "UPDATE books SET title=?, author=?, description=? WHERE id=?"
    );
    $stmt->bind_param("sssi",
        $data["title"], $data["author"],
        $data["description"], $data["id"]
    );
    $stmt->execute();

    echo json_encode(["message" => "Book updated"]);
}

// Delete a book
if ($method === "DELETE") {
    $id = (int) ($_GET["id"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(["message" => "Book deleted"]);
}

$conn->close();
?>
