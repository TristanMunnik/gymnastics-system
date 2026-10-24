<?php 

    session_start();

    if(isset($_SESSION['user_id'])) {
        if($_SESSION['role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: gymnast.php');
        }
        exit;
    }
    
    require_once "db.php";

    $error = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if(empty($email) || empty($password)) {
            $error = "Please fill in all fields"; 
        } else {

            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                if($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: gymnast.php');
                }
                exit;
            } else {
                $error = 'invalid email or password';
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gymnastics Academy - Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">

<main class="login-layout">
    <section class="login-card">
        <a class="login-brand" href="index.php" aria-label="Gymnastics Academy home">
            <span class="login-brand-mark" aria-hidden="true">G</span>
            <span>Gymnastics Academy</span>
        </a>
        <div class="login-heading">
            <p class="login-eyebrow">WELCOME BACK</p>
            <h1>Sign in to your account</h1>
            <p>Manage your membership and training details.</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="login-submit">Sign in <span aria-hidden="true">→</span></button>
        </form>
        <p class="login-footer">Gymnastics Academy <span>·</span> Member portal</p>
    </section>
    <aside class="login-aside" aria-label="Academy welcome">
        <div class="aside-decoration aside-decoration-one"></div>
        <div class="aside-decoration aside-decoration-two"></div>
        <div class="aside-content">
            <span class="aside-label">MOVE WITH PURPOSE</span>
            <span class="aside-glyph" aria-hidden="true">G</span>
            <h2>Strength, focus,<br>and a little more lift.</h2>
            <p>Your training journey, all in one place.</p>
        </div>
        <span class="aside-caption">GYMNASTICS ACADEMY <span>—</span> EST. 2024</span>
    </aside>
</main>

</body>
</html>