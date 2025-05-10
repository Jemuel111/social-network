<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$errors = [];
$username = $email = $full_name = '';

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty($_POST['username'])) {
        $errors[] = "Username is required";
    } else {
        $username = clean_input($_POST['username']);
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } else {
        $email = clean_input($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Email already exists";
            }
        }
    }
    
    // Validate full name
    if (empty($_POST['full_name'])) {
        $errors[] = "Full name is required";
    } else {
        $full_name = clean_input($_POST['full_name']);
    }
    
    // Validate password
    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($_POST['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, insert user
    if (empty($errors)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $full_name);
        
        if ($stmt->execute()) {
            // Set session and redirect
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Zyntra</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #5D479A;
            --secondary: #694786;
            --accent: #F187EA;
            --dark: #1A1347;
            --light: #A486B0;
            --bs-body-bg: linear-gradient(135deg, var(--dark) 0%, #3C2D57 100%);
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, #3C2D57 100%);
            min-height: 100vh;
            position: relative;
            
        }
        
        /* Background Elements */
        .background-container {
            position: fixed; /* Keep fixed to prevent scrolling */
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scrolling if content exceeds */
            z-index: 0;
        }
        
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 1;
            opacity: 0.4;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translate(0, 0px) rotate(0deg); }
            50% { transform: translate(0, 15px) rotate(5deg); }
            100% { transform: translate(0, 0px) rotate(0deg); }
        }
        
        .floating-slow {
            animation: floating-slow 4s ease-in-out infinite;
        }
        
        @keyframes floating-slow {
            0% { transform: translate(0, 0px) rotate(0deg); }
            50% { transform: translate(0, 10px) rotate(-5deg); }
            100% { transform: translate(0, 0px) rotate(0deg); }
        }
        
        .floating-fast {
            animation: floating-fast 2.5s ease-in-out infinite;
        }
        
        @keyframes floating-fast {
            0% { transform: translate(0, 0px) rotate(0deg); }
            50% { transform: translate(0, 20px) rotate(10deg); }
            100% { transform: translate(0, 0px) rotate(0deg); }
        }
        
        .rotate-slow {
            animation: rotate-slow 10s linear infinite;
        }
        
        @keyframes rotate-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .rotate-reverse {
            animation: rotate-reverse 15s linear infinite;
        }
        
        @keyframes rotate-reverse {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }
        
        .shape {
            position: absolute;
            z-index: 1;
        }
        
        .circle-bg {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .grid-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.2;
        }
        
        .hexagon {
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }
        
        .triangle {
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
        }
        
        .diamond {
            clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        }
        
        .content-placeholder {
            position: relative;
            z-index: 10;
            min-height: 100vh; /* Ensure it takes full viewport height */
            padding-bottom: 2rem; /* Add space at bottom */
        }
        
        .gradient-purple {
            background: linear-gradient(135deg, #5D479A, #F187EA);
        }
        
        .gradient-purple-alt {
            background: linear-gradient(135deg, #694786, #F187EA);
        }
        
        .gradient-purple-light {
            background: linear-gradient(135deg, #5D479A, #A486B0);
        }
        
        /* Register Card Styles */
        .register-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            margin: 30px;
        }
        
        .register-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .register-card .card-footer {
            background: transparent;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-control, .btn {
            border-radius: 10px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(241, 135, 234, 0.25);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border: none;
            padding: 0.75rem 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(241, 135, 234, 0.3);
        }
        
        .btn-primary:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }
        
        .btn-primary:hover:before {
            left: 100%;
        }
        
        .brand-name {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #fff, #F187EA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .tagline {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: var(--accent);
        }
        
        /* Hide certain elements on small screens */
        @media (max-width: 576px) {
            .d-sm-none {
                display: none !important;
            }
            .blob, .shape {
                position: fixed;
            }
            .blob {
                opacity: 0.3;
            }
            
            .brand-section {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .brand-name {
                font-size: 2.5rem;
            }
            
            .tagline {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Container - Fixed and full viewport -->
    <div class="background-container">
        <!-- Background Blobs - Positioned with percentages -->
        <div class="blob" style="background: #F187EA; width: 30vw; height: 30vw; max-width: 500px; max-height: 500px; top: -10vh; right: 10%;"></div>
        <div class="blob" style="background: #5D479A; width: 35vw; height: 35vw; max-width: 600px; max-height: 600px; bottom: -15vh; left: 5%;"></div>
        
        <!-- Grid Background - Full viewport -->
        <div class="grid-bg">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>
        
        <!-- Decorative Elements - Positioned with percentages -->
        <div class="circle-bg rotate-slow d-none d-sm-block" style="width: 20vw; height: 20vw; max-width: 300px; max-height: 300px; top: 0; right: 0;"></div>
        <div class="circle-bg rotate-reverse d-none d-sm-block" style="width: 25vw; height: 25vw; max-width: 400px; max-height: 400px; bottom: 0; left: 0;"></div>
        
        <!-- Medium circle in the center -->
        <div class="circle-bg" style="width: 15vw; height: 15vw; max-width: 200px; max-height: 200px; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1;"></div>
        
        <!-- Floating shapes -->
        <div class="shape d-none d-sm-block" style="top: 20%; left: 15%;">
            <div class="hexagon floating-slow gradient-purple" style="width: 5vw; height: 5vw; max-width: 60px; max-height: 60px; opacity: 0.2;"></div>
        </div>
        
        <div class="shape d-none d-sm-block" style="bottom: 30%; right: 10%;">
            <div class="triangle floating gradient-purple" style="width: 4vw; height: 4vw; max-width: 50px; max-height: 50px; opacity: 0.2;"></div>
        </div>
        
        <div class="shape d-none d-sm-block" style="top: 40%; right: 20%;">
            <div class="diamond floating-fast gradient-purple" style="width: 3vw; height: 3vw; max-width: 40px; max-height: 40px; opacity: 0.2;"></div>
        </div>
        
        <!-- Additional shapes for more visual interest -->
        <div class="shape d-none d-sm-block" style="top: 70%; left: 25%;">
            <div class="triangle floating-fast gradient-purple-alt" style="width: 3.5vw; height: 3.5vw; max-width: 45px; max-height: 45px; opacity: 0.15; transform: rotate(180deg);"></div>
        </div>
        
        <div class="shape d-none d-sm-block" style="top: 25%; right: 35%;">
            <div class="hexagon floating gradient-purple-alt" style="width: 4vw; height: 4vw; max-width: 55px; max-height: 55px; opacity: 0.15;"></div>
        </div>
        
        <div class="shape d-none d-sm-block" style="bottom: 15%; right: 30%;">
            <div class="diamond floating-slow gradient-purple-light" style="width: 4.5vw; height: 4.5vw; max-width: 58px; max-height: 58px; opacity: 0.15;"></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container content-placeholder">
        <div class="row align-items-center vh-100">
            <!-- Left Side - Brand -->
            <div class="col-md-6 d-none d-md-block">
                <div class="brand-section">
                    <h1 class="brand-name">
                        <a href="#" id="hidden-admin-link" style="text-decoration: none; color: inherit;">Zyntra</a>
                    </h1>
                    <h2 class="tagline">Join our community and connect <br> with like-minded people.</h2>
                </div>
            </div>
            <!-- Right Side - Register Form -->
            <div class="col-md-6">
                <div class="card register-card shadow-lg">
                    <div class="card-header bg-transparent text-center">
                        <img src="assets/images/logo-white.png" alt="Zyntra Logo" class="img-fluid" style="max-height: 60px;">
                    </div>
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4 text-white">Create Your Account</h4>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="full_name" class="form-label text-white">Full Name</label>
                                <input type="text" class="form-control logreg" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label text-white">Email</label>
                                <input type="email" class="form-control logreg" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label text-white">Username</label>
                                <input type="text" class="form-control logreg" id="username" name="username" value="<?php echo $username; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-white">Password</label>
                                <input type="password" class="form-control logreg" id="password" name="password" style="margin-bottom: 0;" required>
                                <small class="" style="color: var(--accent);">Must be at least 8 characters</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label text-white">Confirm Password</label>
                                <input type="password" class="form-control logreg" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center text-white-50">
                        Already have an account? <a href="login.php" style="color: var(--accent);">Log In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hidden admin login via brand name
        document.getElementById('hidden-admin-link').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'login.php';
        });

        // Function to ensure background elements adjust properly on resize
        function adjustBackground() {
            const body = document.body;
            const background = document.querySelector('.background-container');
            
            // Set minimum height to document height
            background.style.minHeight = Math.max(
                body.scrollHeight, 
                window.innerHeight
            ) + 'px';
        }

        // Call on load and resize
        window.addEventListener('load', adjustBackground);
        window.addEventListener('resize', adjustBackground);
        window.addEventListener('scroll', adjustBackground);
    </script>
</body>
</html>