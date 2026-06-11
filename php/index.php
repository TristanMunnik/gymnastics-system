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

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if(empty($email) || empty($password)) {
            $error = "Please fill in all fields"; 
        } else {

            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if($user && password_verify($password, $user['password'])) {
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
</head>
<body>

<div class="page-center">
    <div class="card" style="width: 100%; max-width: 420px;">

        <div>
            <div style="font-size: 2.5rem;"></div>
            <h1>Gymnastics Academy</h1>
            <p class="text-muted">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                Sign In
            </button>

        </form>

    </div>
</div>

</body>
</html>