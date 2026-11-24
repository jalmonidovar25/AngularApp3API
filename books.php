<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit;
}

// Connect to MySQL database
$conn = new mysqli("localhost", "root", "", "angularapp3_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Connection failed"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

// Display all books
if ($method == "GET") {
    $result = $conn->query("SELECT * FROM books");
    $books = [];

    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    echo json_encode($books);
}

// Add a book with optional cover image
elseif ($method == "POST") {
    $title = $_POST["title"] ?? "";
    $author = $_POST["author"] ?? "";
    $description = $_POST["description"] ?? "";
    $cover = "";

    if (trim($title) == "" || trim($author) == "") {
        http_response_code(400);
        echo json_encode(["message" => "Title and author are required"]);
        exit;
    }

    // Upload the book cover
    if (isset($_FILES["cover"]) && $_FILES["cover"]["error"] != UPLOAD_ERR_NO_FILE) {
        $file = $_FILES["cover"];
        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        if ($file["error"] != UPLOAD_ERR_OK ||
            !in_array($extension, ["jpg", "jpeg", "png"]) ||
            $file["size"] > 5000000 ||
            !getimagesize($file["tmp_name"])) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid cover image"]);
            exit;
        }

        if (!is_dir("uploads")) {
            mkdir("uploads", 0755);
        }

        $cover = uniqid() . "." . $extension;

        if (!move_uploaded_file($file["tmp_name"], "uploads/" . $cover)) {
            http_response_code(500);
            echo json_encode(["message" => "Upload failed"]);
            exit;
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO books (title, author, description, cover_image)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("ssss", $title, $author, $description, $cover);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Book added successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Unable to add book"]);
    }
}

// Update an existing book
elseif ($method == "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare(
        "UPDATE books SET title=?, author=?, description=? WHERE id=?"
    );

    $stmt->bind_param(
        "sssi",
        $data["title"],
        $data["author"],
        $data["description"],
        $data["id"]
    );

    if ($stmt->execute()) {
        echo json_encode(["message" => "Book updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Unable to update book"]);
    }
}

// Delete a book
elseif ($method == "DELETE") {
    $id = (int) ($_GET["id"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Book deleted successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Unable to delete book"]);
    }
}

$conn->close();
?>