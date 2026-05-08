<?php
session_start();
include '../config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $conn->real_escape_string($_POST['email']); // email OR username
    $password = $_POST['password'];

    // ======================
    // 1. CHECK ADMIN TABLE
    // ======================
    $admin_sql = "SELECT * FROM admin WHERE username = '$input'";
    $admin_result = $conn->query($admin_sql);

    if ($admin_result->num_rows == 1) {
        $admin = $admin_result->fetch_assoc();

        // admin password plain text hai (as per your DB)
        if ($password === $admin['password']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['name'] = $admin['username'];
            $_SESSION['role'] = 'admin';

            header("Location: ../admin/index.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">Invalid Admin Password!</div>';
        }

    } else {

        // ======================
        // 2. CHECK USERS TABLE
        // ======================
        $sql = "SELECT * FROM users WHERE email = '$input' OR name = '$input'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == 'seller') {
                    header("Location: ../seller/index.php");
                } else {
                    header("Location: ../user/index.php");
                }
                exit();
            } else {
                $message = '<div class="alert alert-danger">Invalid Password!</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">User not found!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - ShopEase</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
* {
    font-family: 'Poppins', sans-serif;
}

body {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
}

.auth-card {
    max-width: 400px;
    width: 100%;
    padding: 40px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 10px 50px rgba(0,0,0,0.5);
    color: white;
}

h3 {
    color: #00eaff;
}

label {
    color: #d1faff;
    font-weight: 500;
}

/* ✅ FIX START */
.form-control {
    background: rgba(255,255,255,0.15);  /* thoda bright kiya */
    border: 1px solid rgba(0,255,255,0.3);
    border-radius: 10px;
    color: #ffffff;                      /* text visible */
    caret-color: #ffffff;                /* cursor visible */
}

.form-control::placeholder {
    color: #b6f7ff;                      /* placeholder visible */
    opacity: 1;
}

.form-control:focus {
    background: rgba(255,255,255,0.15);
    border: 1px solid #00eaff;
    box-shadow: 0 0 10px rgba(0, 234, 255, 0.5);
    color: #ffffff;
}
/* ✅ FIX END */

.btn-primary {
    background: linear-gradient(135deg, #00eaff, #007cf0);
    border: none;
    border-radius: 12px;
}

a { color: #00eaff; }
small { color: #ccc; }
.alert { border-radius: 10px; }
</style>
</head>
<body>

<div class="auth-card">
    <h3 class="text-center fw-bold mb-4">Welcome Back</h3>
    <?php echo $message; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Email / Username</label>
            <input type="text" name="email" class="form-control" required placeholder="Enter email or username">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Enter password">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
    </form>

    <!-- <a href="forgot_password.php" class="small text-decoration-none d-block mt-2">Forgot Password?</a> -->

    <div class="text-center mt-3">
        <small>Don't have an account? <a href="register.php">Sign Up</a></small>
    </div>
</div>

</body>
</html>