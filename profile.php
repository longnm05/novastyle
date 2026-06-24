<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Xử lý Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $gender = $_POST['gender'];
        
        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            $error_msg = "Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, gender = ? WHERE id = ?");
            if ($stmt->execute([$fullname, $phone, $address, $gender, $user_id])) {
                $_SESSION['full_name'] = $fullname;
                $_SESSION['gender'] = $gender;
                $success_msg = "Cập nhật thông tin thành công!";
            } else {
                $error_msg = "Có lỗi xảy ra khi lưu thông tin. Vui lòng thử lại!";
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
        $orderIdToCancel = intval($_POST['order_id']);
        // Chỉ cho phép khách hàng tự hủy đơn hàng của mình khi trạng thái là 'pending' (Chờ xử lý/Chờ thanh toán)
        $stmtCancel = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
        if ($stmtCancel->execute([$orderIdToCancel, $user_id])) {
            if ($stmtCancel->rowCount() > 0) {
                $success_msg = "Hủy đơn hàng #" . $orderIdToCancel . " thành công!";
            } else {
                $error_msg = "Không thể hủy đơn hàng này hoặc đơn hàng đã bắt đầu được xử lý/giao hàng!";
            }
        } else {
            $error_msg = "Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại!";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $error_msg = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
        } elseif (strlen($newPassword) < 6) {
            $error_msg = "Mật khẩu mới phải từ 6 ký tự trở lên!";
        } else {
            // Lấy mật khẩu hiện tại trong database
            $stmtUserCheck = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmtUserCheck->execute([$user_id]);
            $dbUser = $stmtUserCheck->fetch();
            
            $validCurrent = false;
            if ($dbUser) {
                $dbHash = $dbUser['password_hash'];
                // Đối chiếu theo cách mã hóa của hệ thống hiện tại
                if ($dbHash === 'hashed_' . $currentPassword || $dbHash === $currentPassword) {
                    $validCurrent = true;
                }
            }
            
            if (!$validCurrent) {
                $error_msg = "Mật khẩu hiện tại không chính xác!";
            } else {
                // Cập nhật mật khẩu mới theo chuẩn định dạng của dự án
                $newHashedPassword = 'hashed_' . $newPassword;
                $stmtUpdatePass = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmtUpdatePass->execute([$newHashedPassword, $user_id])) {
                    $success_msg = "Đổi mật khẩu thành công!";
                } else {
                    $error_msg = "Có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại!";
                }
            }
        }
    }
}
// Lấy thông tin user
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// Lấy đơn hàng của user
$stmtOrders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmtOrders->execute([$user_id]);
$orders = $stmtOrders->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Người Dùng - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            padding-top: 100px;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
            display: flex;
            gap: 40px;
            min-height: 70vh;
        }

        /* Sidebar Profile */
        .profile-sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            align-self: flex-start;
        }

        .user-avatar-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-purple);
            margin-bottom: 15px;
        }

        .profile-menu {
            list-style: none;
            padding: 0;
        }

        .profile-menu li {
            margin-bottom: 10px;
        }

        .profile-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition-smooth);
            font-weight: 500;
        }

        .profile-menu a:hover,
        .profile-menu a.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.3);
        }

        /* Main Content */
        .profile-main {
            flex: 1;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .profile-tab-content {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .profile-tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            margin-bottom: 30px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Group */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .form-group input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            background: rgba(0,0,0,0.02);
            font-family: var(--font-body);
            color: var(--text-main);
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-purple);
            background: white;
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.1);
        }

        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .orders-table th {
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .status-processing { background: rgba(0, 123, 255, 0.1); color: #007bff; }
        .status-shipped { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .status-delivered { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .status-cancelled { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        /* Style Tags */
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .style-tag-label {
            padding: 10px 20px;
            background: rgba(0,0,0,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
            user-select: none;
        }

        .style-tag-input:checked + .style-tag-label {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.3);
        }
        
        .style-tag-input {
            display: none;
        }
    </style>
</head>

<body>
    <!-- Background Elements -->
    <div class="orb orb-1" style="background: rgba(138, 43, 226, 0.15);"></div>
    <div class="orb orb-2" style="background: rgba(255, 65, 108, 0.15);"></div>

    <!-- Navigation -->
    <nav class="glass-header" style="background: rgba(255,255,255,0.8);">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <i class="fa-solid fa-microchip" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i> NovaStyle
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Trang Chủ</a>
            <a href="profile.php" class="nav-icon active"><i class="fa-solid fa-user"></i></a>
        </div>
    </nav>

    <!-- Content -->
    <div class="profile-container">
        <!-- Sidebar -->
        <aside class="profile-sidebar">
            <div class="user-avatar-section">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200&h=200" alt="User Avatar" class="user-avatar">
                <h3 style="font-family: var(--font-heading); font-size: 1.2rem;"><?= htmlspecialchars($user['full_name']) ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Thành viên Bạc</p>
            </div>
            
            <ul class="profile-menu">
                <li><a href="#" class="profile-tab-link active" data-tab="info-tab"><i class="fa-solid fa-address-card"></i> Thông Tin</a></li>
                <li><a href="#" class="profile-tab-link" data-tab="orders-tab"><i class="fa-solid fa-box-open"></i> Đơn Hàng</a></li>
                <li><a href="#" class="profile-tab-link" data-tab="password-tab"><i class="fa-solid fa-key"></i> Đổi Mật Khẩu</a></li>
                <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                <li><a href="admin.php" style="color: #8a2be2; background: rgba(138, 43, 226, 0.1);"><i class="fa-solid fa-user-shield"></i> Trang Quản Trị</a></li>
                <?php endif; ?>
                <li style="margin-top: 30px;"><a href="logout.php" style="color: #ff4d4d; background: rgba(255, 77, 77, 0.1);"><i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất</a></li>
            </ul>
        </aside>

        <!-- Main Areas -->
        <main class="profile-main">
            <?php if($success_msg): ?>
                <div style="background: rgba(0, 255, 136, 0.1); border: 1px solid #00ff88; color: #00b35f; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fa-solid fa-check-circle"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div style="background: rgba(255, 65, 108, 0.1); border: 1px solid #ff416c; color: #ff416c; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <!-- Info Tab -->
            <div class="profile-tab-content active" id="info-tab">
                <h2 class="section-title"><i class="fa-solid fa-id-card" style="color: var(--accent-blue);"></i> Hồ Sơ Cá Nhân</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và Tên</label>
                            <input type="text" name="fullname" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tên đăng nhập (Không thể thay đổi)</label>
                            <input type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly style="background: rgba(0,0,0,0.05); cursor: not-allowed;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender" style="width: 100%; padding: 12px 20px; border: 1px solid var(--glass-border); border-radius: 10px; background: rgba(0,0,0,0.02); font-family: var(--font-body);">
                                <option value="nam" <?= $user['gender'] == 'nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="nu" <?= $user['gender'] == 'nu' ? 'selected' : '' ?>>Nữ</option>
                                <option value="khac" <?= $user['gender'] == 'khac' ? 'selected' : '' ?>>Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="0[0-9]{9}" title="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email (Không thể thay đổi)</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: rgba(0,0,0,0.05); cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ giao hàng</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-glow" style="padding: 12px 40px;"><i class="fa-solid fa-save"></i> Lưu Thay Đổi</button>
                </form>
            </div>

            <!-- Password Tab -->
            <div class="profile-tab-content" id="password-tab">
                <h2 class="section-title"><i class="fa-solid fa-key" style="color: var(--accent-purple);"></i> Đổi Mật Khẩu</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" required placeholder="Nhập mật khẩu hiện tại">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mật khẩu mới (Tối thiểu 6 ký tự)</label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Nhập mật khẩu mới">
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" required minlength="6" placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-glow" style="padding: 12px 40px; margin-top: 10px;"><i class="fa-solid fa-save"></i> Cập Nhật Mật Khẩu</button>
                </form>
            </div>

            <!-- Orders Tab -->
            <div class="profile-tab-content" id="orders-tab">
                <h2 class="section-title"><i class="fa-solid fa-box" style="color: var(--accent-purple);"></i> Lịch Sử Đơn Hàng</h2>
                <?php if(empty($orders)): ?>
                    <p style="color: var(--text-muted);">Bạn chưa có đơn hàng nào.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Mã Đơn</th>
                                <th>Ngày Đặt</th>
                                <th>Tổng Tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng Thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--accent-blue);">#ORD-<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                <td style="font-weight: 600;"><?= number_format($o['total_amount'], 0, ',', '.') ?> VNĐ</td>
                                <td>
                                    <?php if(isset($o['payment_method']) && $o['payment_method'] === 'online'): ?>
                                        <span style="font-size: 0.9rem; color: var(--text-main);"><i class="fa-solid fa-wallet" style="color: var(--accent-purple); margin-right: 5px;"></i> Online</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.9rem; color: var(--text-muted);"><i class="fa-solid fa-truck-ramp-box" style="margin-right: 5px;"></i> COD</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $status_labels = [
                                            'pending' => ['label' => 'Chờ thanh toán', 'class' => 'status-pending'],
                                            'processing' => ['label' => 'Đang chuẩn bị', 'class' => 'status-processing'],
                                            'shipped' => ['label' => 'Đang giao hàng', 'class' => 'status-shipped'],
                                            'delivered' => ['label' => 'Đã giao hàng', 'class' => 'status-delivered'],
                                            'cancelled' => ['label' => 'Đã hủy', 'class' => 'status-cancelled']
                                        ];
                                        $lbl = $status_labels[$o['status']] ?? ['label' => $o['status'], 'class' => 'status-pending'];
                                    ?>
                                    <span class="status-badge <?= $lbl['class'] ?>"><?= $lbl['label'] ?></span>
                                </td>
                                <td>
                                     <a href="invoice.php?id=<?= $o['id'] ?>" style="color: var(--accent-purple); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-file-invoice"></i> Xem</a>
                                     <?php if ($o['status'] === 'pending'): ?>
                                         <?php if (isset($o['payment_method']) && $o['payment_method'] === 'online'): ?>
                                             <br><a href="vnpay_pay_again.php?id=<?= $o['id'] ?>" style="color: var(--accent-blue); text-decoration: none; font-weight: 600; font-size: 0.85rem;"><i class="fa-solid fa-credit-card"></i> Thanh toán</a>
                                         <?php endif; ?>
                                         <br>
                                         <form method="POST" action="profile.php" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');" style="display: inline;">
                                             <input type="hidden" name="action" value="cancel_order">
                                             <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                             <button type="submit" style="background: none; border: none; color: #dc3545; font-weight: 600; font-size: 0.85rem; cursor: pointer; padding: 0; font-family: inherit; margin-top: 5px;"><i class="fa-solid fa-trash-can"></i> Hủy đơn</button>
                                         </form>
                                     <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Tab logic for Profile
        const tabLinks = document.querySelectorAll('.profile-tab-link');
        const tabContents = document.querySelectorAll('.profile-tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                link.classList.add('active');
                const targetTab = document.getElementById(link.getAttribute('data-tab'));
                targetTab.classList.add('active');
            });
        });
    </script>
</body>
</html>