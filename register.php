<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $gender = $_POST['gender'] ?? '';
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;

    if (!$terms) {
        $error = "Bạn phải xác nhận đồng ý với các điều khoản!";
    } elseif ($password !== $confirmPassword) {
        $error = "Mật khẩu xác nhận không khớp!";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $error = "Email phải có định dạng @gmail.com!";
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $error = "Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0!";
    } else {
        // Kiểm tra username đã tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Tên đăng nhập này đã được sử dụng!";
        } else {
            // Kiểm tra email đã tồn tại
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email này đã được sử dụng!";
            } else {
                // Demo data insertion (for real apps use password_hash)
                // Using a simple hash prefix to match db data style or just plain text
                $hashed_password = 'hashed_' . $password; 
                
                $stmtInsert = $conn->prepare("INSERT INTO users (username, full_name, gender, email, phone, address, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'customer')");
                if ($stmtInsert->execute([$username, $fullName, $gender, $email, $phone, $address, $hashed_password])) {
                    $success = "Đăng ký thành công! Đang chuyển hướng...";
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Có lỗi xảy ra. Vui lòng thử lại!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaStyle - Đăng Ký Tài Khoản</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            min-height: 100vh;
            background: #0f0f12;
            position: relative;
            overflow: hidden;
            padding: 40px 0;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 35px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            position: relative;
            z-index: 10;
            padding: 40px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h2 {
            font-family: var(--font-heading);
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #4facfe;
            font-size: 1.1rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: white;
            font-family: var(--font-body);
            transition: var(--transition-smooth);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .form-group input:focus, .form-group select:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #4facfe;
            box-shadow: 0 0 20px rgba(79, 172, 254, 0.3);
            outline: none;
        }

        .submit-btn {
            width: 100%;
            height: 55px;
            border-radius: 15px;
            border: none;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            cursor: pointer;
            transition: var(--transition-smooth);
            margin-top: 10px;
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%) !important;
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4) !important;
            text-transform: uppercase;
            letter-spacing: 1px;
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

    <div class="register-container">
        <!-- Animated Background Orbs -->
        <div class="orb" style="background: rgba(79, 172, 254, 0.2); top: -200px; left: -100px;"></div>
        <div class="orb" style="background: rgba(138, 43, 226, 0.2); bottom: -100px; right: -100px; animation-delay: -5s;"></div>
        <div class="orb" style="background: rgba(255, 65, 108, 0.15); top: 50%; left: 50%; transform: translate(-50%, -50%); width: 800px; height: 800px; filter: blur(120px); animation: none;"></div>

        <div class="register-card">
            <div class="register-header">
                <h2>Đăng Ký Tài Khoản</h2>
        
            </div>
            <form action="register.php" method="POST">
                <?php if($error): ?>
                    <p style="color: red; text-align: center; margin-bottom: 10px;"><?= $error ?></p>
                <?php endif; ?>
                <?php if($success): ?>
                    <p style="color: #00ff88; text-align: center; margin-bottom: 10px;"><?= $success ?></p>
                <?php endif; ?>
                <div class="form-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="fullname" placeholder="Họ và tên" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-id-badge"></i>
                    <input type="text" name="username" placeholder="Tên đăng nhập" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-venus-mars"></i>
                    <select name="gender" required>
                        <option value="" disabled selected style="background: #0f0f12; color: rgba(255,255,255,0.4);">Chọn giới tính</option>
                        <option value="nam" style="background: #0f0f12; color: white;">Nam</option>
                        <option value="nu" style="background: #0f0f12; color: white;">Nữ</option>
                        <option value="khac" style="background: #0f0f12; color: white;">Khác</option>
                    </select>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" title="Email phải có định dạng @gmail.com" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" name="phone" placeholder="Số điện thoại" pattern="0[0-9]{9}" title="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" name="address" placeholder="Địa chỉ" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Mật khẩu" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <input type="checkbox" name="terms" id="terms" required style="width: auto; padding: 0;">
                    <label for="terms" style="font-size: 0.9rem; color: var(--text-main); cursor: pointer;">Tôi đã đọc và đồng ý với <a href="#" style="color: var(--accent-purple); text-decoration: none;">Điều khoản dịch vụ</a></label>
                </div>
                
                <button type="submit" class="submit-btn">Đăng Ký <i class="fa-solid fa-user-plus"></i></button>
                <p style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);">
                    Đã có tài khoản? <a href="login.php" style="color: var(--accent-purple); text-decoration: none; font-weight: 600;">Đăng nhập ngay</a>
                </p>
            </form>
        </div>
    </div>

</body>
</html>
