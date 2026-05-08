<?php
session_start();
include '../config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']); // ✅ NEW
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Backend validation (important)
    if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $message = '<div class="alert alert-danger">Name must contain only alphabets!</div>';
    } 
    elseif (empty($address)) {
        $message = '<div class="alert alert-danger">Address field cannot be empty!</div>';
    }
    elseif (strlen($address) < 10) {
        $message = '<div class="alert alert-danger">Address must be at least 10 characters long!</div>';
    }
    else {

        $checkEmail = "SELECT id FROM users WHERE email = '$email'";
        $result = $conn->query($checkEmail);

        if ($result->num_rows > 0) {
            $message = '<div class="alert alert-danger">Email already registered!</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ✅ UPDATED INSERT QUERY
            $sql = "INSERT INTO users (name, address, email, password, role) 
                    VALUES ('$name', '$address', '$email', '$hashed_password', '$role')";

            if ($conn->query($sql) === TRUE) {
                header("location:login.php");
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* 🌌 DARK NEON BACKGROUND */
body {
    background: radial-gradient(circle at top, #0f172a, #020617);
    display: flex;
    align-items: center;
    height: 100vh;
    font-family: 'Segoe UI', sans-serif;
}

/* 💎 GLASS NEON CARD */
.auth-card {
    max-width: 450px;
    width: 100%;
    margin: auto;
    padding: 40px;
    border-radius: 20px;

    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(20px);

    border: 1px solid rgba(0,255,255,0.2);
    box-shadow: 0 0 25px rgba(0,255,255,0.15);

    color: #e2e8f0;
    transition: 0.3s;
}

.auth-card:hover {
    box-shadow: 0 0 40px rgba(0,255,255,0.4);
    transform: translateY(-5px);
}

/* 🔥 Heading */
h3 {
    color: #00f7ff;
}

/* ✨ Inputs */
.form-control, .form-select {
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(0,255,255,0.2);
    color: #fff;
    border-radius: 10px;
}

.form-control:focus, .form-select:focus {
    border-color: #00f7ff;
    box-shadow: 0 0 10px #00f7ff;
    background: rgba(0,0,0,0.6);
    color: #fff;
}

/* 🚀 Button */
.btn-primary {
    background: linear-gradient(135deg, #00f7ff, #2563eb);
    border: none;
    font-weight: bold;
    border-radius: 12px;
    transition: 0.3s;
}

.btn-primary:hover {
    transform: scale(1.05);
    box-shadow: 0 0 20px #00f7ff;
}

/* Links */
a {
    color: #00f7ff;
}

a:hover {
    text-shadow: 0 0 8px #00f7ff;
}

/* Labels */
.form-label {
    color: #cbd5f5;
}

</style>
</head>

<body>

<div class="auth-card">
    <h3 class="text-center fw-bold mb-4">Create Account</h3>
    <?php echo $message; ?>
    
    <form method="POST" action="" onsubmit="return validateForm()">
        
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" required
                   onkeypress="return onlyAlphabets(event)">
        </div>

        <!-- ✅ NEW ADDRESS FIELD -->
        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" id="address" class="form-control" rows="2" placeholder="Enter your address" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
        </div>

        <div class="mb-3">
            <label class="form-label">I want to be a:</label>
            <select name="role" class="form-select">
                <option value="customer">Customer (I want to buy)</option>
                <option value="seller">Seller (I want to sell)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Sign Up</button>
    </form>

    <div class="text-center mt-3">
        <small>Already have an account? <a href="login.php">Login</a></small>
    </div>
</div>

<script>

// 🔤 Only alphabets (no number, no special char)
function onlyAlphabets(e) {
    let char = String.fromCharCode(e.which);
    if (!/^[a-zA-Z ]+$/.test(char)) {
        return false;
    }
    return true;
}

// 📧 Email + Address validation
function validateForm() {
    let email = document.getElementById("email").value;
    let address = document.getElementById("address").value;

    let pattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;

    if (!pattern.test(email)) {
        alert("Enter valid email!");
        return false;
    }

    if (address.trim() === "") {
        alert("Address field cannot be empty!");
        return false;
    }

    if (address.length < 10) {
        alert("Address must be at least 10 characters!");
        return false;
    }

    return true;
}

</script>

</body>
</html>