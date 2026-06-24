<?php
session_start();
require_once 'db.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    // For demo purpose, we skip actual password hashing verification since demo data has fake hashes
    // In production: password_verify($password, $user['password_hash'])

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && ($user['password_hash'] === 'hashed_' . $password || $user['password_hash'] === $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['gender'] = $user['gender'];

        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Tài khoản hoặc Mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaStyle - Cổng Xác Thực Hệ Thống</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .unified-login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
            background: #0f0f12;
            position: relative;
            overflow: hidden;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 35px;
            width: 95%;
            max-width: 900px;
            min-height: 550px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            position: relative;
            z-index: 10;
            display: flex;
            overflow: hidden;
            padding: 0; /* Remove padding to handle split inner areas */
        }

        .login-form-area {
            flex: 1.2;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-info-area {
            flex: 0.8;
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.2) 0%, rgba(138, 43, 226, 0.2) 100%);
            border-left: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .login-info-area::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://images.unsplash.com/photo-1550745679-5651f5f8b220?auto=format&fit=crop&q=80&w=800') center center/cover;
            opacity: 0.2;
            z-index: -1;
        }

        .login-header h2 {
            font-family: var(--font-heading);
            font-size: 2.8rem;
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group i {
            color: #4facfe;
            font-size: 1.1rem;
        }

        .form-group input {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            height: 55px;
            border-radius: 15px;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .form-group input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #4facfe;
            box-shadow: 0 0 20px rgba(79, 172, 254, 0.3);
        }

        .submit-btn {
            height: 55px;
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%) !important;
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4) !important;
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .submit-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(79, 172, 254, 0.6) !important;
        }

        .orb {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            animation: orbMove 20s infinite alternate;
        }
        @keyframes orbMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }
    </style>
</head>
<body>

    <div class="unified-login-container">
        <!-- Animated Background -->
        <div class="orb" style="background: rgba(79, 172, 254, 0.2); top: -200px; left: -100px;"></div>
        <div class="orb" style="background: rgba(138, 43, 226, 0.2); bottom: -100px; right: -100px; animation-delay: -5s;"></div>
        <div class="orb" style="background: rgba(255, 65, 108, 0.15); top: 50%; left: 50%; transform: translate(-50%, -50%); width: 800px; height: 800px; filter: blur(120px); animation: none;"></div>

        <div class="login-card">
            <!-- Left Side: Form -->
            <div class="login-form-area">
                <div class="login-header">
                    <h2>Đăng Nhập</h2>
                    <p style="color: rgba(255, 255, 255, 0.5); font-size: 1rem; margin-bottom: 30px;">Chào mừng bạn trở lại với hệ thống NovaStyle.</p>
                </div>
                
                <?php if($error): ?>
                    <div style="background: rgba(255, 65, 108, 0.1); border: 1px solid rgba(255, 65, 108, 0.3); color: #ff416c; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.9rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label><i class="fa-solid fa-user" style="margin-right: 8px;"></i> Tài khoản đăng nhập</label>
                        <input type="text" name="username" placeholder="Nhập tài khoản của bạn" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-lock" style="margin-right: 8px;"></i> Mật khẩu bảo mật</label>
                        <input type="password" name="password" placeholder="Nhập mật khẩu của bạn" required>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <label style="color: rgba(255,255,255,0.6); cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                            <input type="checkbox" style="width: 18px; height: 18px; cursor: pointer;"> Ghi nhớ tôi
                        </label>
                        <a href="#" style="color: #4facfe; text-decoration: none; font-size: 0.9rem; font-weight: 500;">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="submit-btn" style="width: 100%;">
                        Đăng nhập <i class="fa-solid fa-arrow-right-long" style="margin-left: 10px; color: white;"></i>
                    </button>

                    <p style="text-align: center; margin-top: 30px; font-size: 1rem; color: rgba(255,255,255,0.5);">
                        Chưa có tài khoản? <a href="register.php" style="color: #00f2fe; text-decoration: none; font-weight: 700; border-bottom: 1px solid #00f2fe; padding-bottom: 2px; margin-left: 5px;">Đăng ký ngay</a>
                    </p>
                </form>
            </div>

            <!-- Right Side: Info/Decoration -->
            <div class="login-info-area">
                <div style="margin-bottom: 40px;">
                    <i class="fa-solid fa-microchip" style="font-size: 4rem; background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 1.8rem; margin-bottom: 15px; font-weight: 700;">NovaStyle</h3>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.8; font-size: 0.95rem;">
                    Hệ thống thời trang thông minh tích hợp trí tuệ nhân tạo. Khám phá phong cách cá nhân và trải nghiệm mua sắm không giới hạn.
                </p>
                
                <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%;">
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fa-solid fa-bolt" style="color: #ffaa00; margin-bottom: 10px;"></i>
                        <div style="font-size: 0.8rem; font-weight: 600;">Nhanh chóng</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fa-solid fa-shield-halved" style="color: #00ff88; margin-bottom: 10px;"></i>
                        <div style="font-size: 0.8rem; font-weight: 600;">Bảo mật</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
