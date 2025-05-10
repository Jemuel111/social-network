<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$errors = [];
$username = '';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty($_POST['username'])) {
        $errors[] = "Username or email is required";
    } else {
        $username = clean_input($_POST['username']);
    }
    
    // Validate password
    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        // Check if input is email or username
        $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        // Determine if it's a user or admin login
        $role = isset($_POST['role']) ? $_POST['role'] : 'user';
        
        if ($role === 'admin') {
            $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ? AND role = 'admin'");
        } else {
            $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ? AND role != 'admin'");
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($_POST['password'], $user['password'])) {
                // Set session and redirect
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $role; // Set user role
                
                header("Location: " . ($role === 'admin' ? 'admin/admin_panel.php' : 'index.php'));
                exit;
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "Username or email not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Zyntra</title>
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
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, #3C2D57 100%);
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        /* Background Elements */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
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
            color: white;
            max-width: 80%;
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
        
        /* Login Card Styles */
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .login-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-card .card-footer {
            background: transparent;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
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
        #hidden-admin-link {
            cursor: pointer;
        }
        #hidden-admin-link:hover {
            text-decoration: none !important;
        }
        /* Hide certain elements on small screens */
        @media (max-width: 576px) {
            .d-sm-none {
                display: none !important;
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
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
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
                    <h2 class="tagline">The next-level network where human connection meets intelligent design.</h2>
                </div>
            </div>
            <!-- Right Side - Login Form -->
            <div class="col-md-6">
                <div class="card login-card shadow-lg">
                    <div class="card-header bg-transparent text-center">
                        <img src="assets/images/logo-white.png" alt="Zyntra Logo" class="img-fluid" style="max-height: 60px;">
                    </div>
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4 text-white">Log In to Zyntra</h4>
                        
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
                                <label for="username" class="form-label text-white">Username or Email</label>
                                <input type="text" class="form-control logreg" id="username" name="username" value="<?php echo $username; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-white">Password</label>
                                <input type="password" class="form-control logreg" id="password" name="password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label text-white-50" for="remember">Remember me</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center text-white-50">
                        Don't have an account? <a href="register.php" style="color: var(--accent);">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Admin/User login toggle
        document.getElementById('hidden-admin-link').addEventListener('click', function(e) {
            e.preventDefault();
            // Change the form to admin login
            document.querySelector('form').innerHTML = `
                <input type="hidden" name="role" value="admin">
                <div class="mb-3">
                    <label for="username" class="form-label text-white">Admin Username</label>
                    <input type="text" class="form-control logreg" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label text-white">Password</label>
                    <input type="password" class="form-control logreg" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Log In as Admin</button>
                </div>
                <div class="text-center mt-3">
                    <a href="#" id="user-login-link" style="color: var(--accent);">Switch to User Login</a>
                </div>
            `;
            // Hide the admin login link
            document.getElementById('admin-login-link').style.display = 'none';
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'user-login-link') {
                e.preventDefault();
                // Change the form back to user login
                document.querySelector('form').innerHTML = `
                    <div class="mb-3">
                        <label for="username" class="form-label text-white">Username or Email</label>
                        <input type="text" class="form-control logreg" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Password</label>
                        <input type="password" class="form-control logreg" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label text-white-50" for="remember">Remember me</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                    </div>
                `;
                // Show the admin login link again
                document.getElementById('admin-login-link').style.display = 'block';
            }
        });

        // Function to ensure background elements adjust properly on resize
        function adjustBackground() {
            const vh = window.innerHeight * 0.01;
            const vw = window.innerWidth * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
            document.documentElement.style.setProperty('--vw', `${vw}px`);
            
            // Adjust blob sizes based on screen dimensions
            const minDimension = Math.min(window.innerWidth, window.innerHeight);
            const blobScale = minDimension / 1000; // Adjust this divisor as needed
            
            // Apply dynamic scaling to elements if needed
            document.querySelectorAll('.shape').forEach(shape => {
                // You can add dynamic scaling logic here if needed
            });
        }
        
        // Run on page load and resize
        window.addEventListener('load', adjustBackground);
        window.addEventListener('resize', adjustBackground);
        window.addEventListener('orientationchange', adjustBackground);
    </script>
</body>
</html>