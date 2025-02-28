<?php
session_start();
include '../database/database.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email)  || empty($password) || empty($confirm_password)) {
        die("Please fill in all fields.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("User already exists.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);



    if ($stmt->execute()) {
        echo "Registration successful!";
        header("Location: ../views/login.php");

    } else {
        echo "Error: " . $stmt->error;
        header("Location: ../views/register.php");

    }

    $stmt->close();
    $conn->close();
}
?>
