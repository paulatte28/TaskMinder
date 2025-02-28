<?php
session_start();
require_once '../database/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); 

    $password = trim($_POST['password']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format or empty!";

        header("Location: ../login.php");
        exit();
    }

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); 

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); 

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['id']; 
            $_SESSION['username'] = $user['email']; 

            header("Location: ../index.php"); 
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password!"; 

            header("Location: ../login.php"); 
            exit();
        }
    } else {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../login.php");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
