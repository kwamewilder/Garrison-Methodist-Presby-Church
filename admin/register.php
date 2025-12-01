<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = register($username, $email, $password, $full_name);

        if ($result['success']) {
            $success = $result['message'];
            // Clear form
            $username = $email = $full_name = '';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GMPC ADMIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #5DADEC 0%, #3a8fd9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .register-header {
            background: linear-gradient(135deg, #5DADEC 0%, #3a8fd9 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .register-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            border-radius: 50%;
            background: white;
            padding: 10px;
        }

        .register-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .register-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .register-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #5DADEC;
            box-shadow: 0 0 0 3px rgba(93, 173, 236, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #5DADEC 0%, #3a8fd9 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(93, 173, 236, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .register-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            font-size: 0.85rem;
            color: #666;
        }

        .register-footer a {
            color: #5DADEC;
            text-decoration: none;
            font-weight: 500;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="../images/logo.png" alt="GMPC Logo">
            <h1>Create Account</h1>
            <p>Join GMPC ADMIN Team</p>
        </div>

        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control"
                               placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Choose a username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="Enter your email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Create a password (min 8 characters)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Confirm your password" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="divider">
                <span>Already have an account?</span>
            </div>

            <div style="text-align: center;">
                <a href="login.php" style="color: #5DADEC; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-sign-in-alt"></i> Login Here
                </a>
            </div>
        </div>

        <div class="register-footer">
            <p>&copy; <?php echo date('Y'); ?> Garrison Methodist Presbyterian Church</p>
            <p><a href="../index.php"><i class="fas fa-home"></i> Back to Website</a></p>
        </div>
    </div>
</body>
</html>
