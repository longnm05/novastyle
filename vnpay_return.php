<?php
session_start();
require_once 'db.php';
require_once 'vnpay_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$orderId = isset($_GET['vnp_TxnRef']) ? intval($_GET['vnp_TxnRef']) : 0;
$amount = isset($_GET['vnp_Amount']) ? intval($_GET['vnp_Amount']) / 100 : 0;
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';

$vnp_Params = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $vnp_Params[$key] = $value;
    }
}
unset($vnp_Params['vnp_SecureHash']);
ksort($vnp_Params);
$i = 0;
$hashData = "";
foreach ($vnp_Params as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$payment_success = false;
$error_msg = "";

if ($secureHash === $vnp_SecureHash) {
    if ($vnp_ResponseCode === '00') {
        $payment_success = true;
        
        // Cập nhật trạng thái đơn hàng thành 'processing' (Đã thanh toán) trong CSDL
        try {
            $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending'");
            $stmtUpdate->execute([$orderId]);
        } catch (Exception $e) {
            $error_msg = "Lỗi CSDL: " . $e->getMessage();
        }
    } else {
        // Một số mã lỗi phổ biến của VNPAY
        $vnp_errors = [
            '09' => 'Thẻ/Tài khoản của quý khách chưa đăng ký dịch vụ InternetBanking.',
            '10' => 'Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần.',
            '11' => 'Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.',
            '24' => 'Khách hàng hủy giao dịch.',
            '51' => 'Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Nhập sai mã OTP quá số lần quy định.'
        ];
        $error_msg = $vnp_errors[$vnp_ResponseCode] ?? 'Giao dịch không thành công. Mã lỗi VNPAY: ' . $vnp_ResponseCode;
    }
} else {
    $error_msg = "Chữ ký bảo mật không hợp lệ. Giao dịch có thể đã bị can thiệp.";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-light);
            padding-top: 120px;
            font-family: var(--font-body);
        }
        .result-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .result-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }
        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .icon-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 2px solid #28a745;
            animation: pulseSuccess 1.5s infinite;
        }
        .icon-failure {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        .result-title {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: var(--text-main);
        }
        .result-desc {
            color: var(--text-muted);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            overflow: hidden;
        }
        .info-table td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            font-size: 0.95rem;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-table td:first-child {
            color: var(--text-muted);
            width: 150px;
        }
        .info-table td:last-child {
            font-weight: 600;
            color: var(--text-main);
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        @keyframes pulseSuccess {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
    </style>
</head>
<body>

    <!-- Background -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="result-container">
        <div class="result-card">
            <?php if ($payment_success): ?>
                <div class="icon-wrapper icon-success">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h1 class="result-title">Thanh Toán Thành Công!</h1>
                <p class="result-desc">Cảm ơn bạn đã mua sắm tại NovaStyle. Đơn hàng của bạn đã được ghi nhận thanh toán trực tuyến.</p>
                
                <table class="info-table">
                    <tr>
                        <td>Mã đơn hàng:</td>
                        <td>#ORD-<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                    <tr>
                        <td>Mã giao dịch VNPAY:</td>
                        <td><?= htmlspecialchars($vnp_TransactionNo) ?></td>
                    </tr>
                    <tr>
                        <td>Số tiền thanh toán:</td>
                        <td style="color: var(--accent-blue); font-weight:700;"><?= number_format($amount, 0, ',', '.') ?> VNĐ</td>
                    </tr>
                    <tr>
                        <td>Ngân hàng:</td>
                        <td><?= htmlspecialchars($vnp_BankCode) ?></td>
                    </tr>
                </table>
                
                <div class="btn-group">
                    <a href="invoice.php?id=<?= $orderId ?>" class="btn btn-primary btn-glow" style="padding: 12px 30px;"><i class="fa-solid fa-file-invoice"></i> Xem Hóa Đơn</a>
                    <a href="index.php" class="btn btn-secondary" style="padding: 12px 30px;">Tiếp tục mua sắm</a>
                </div>
            <?php else: ?>
                <div class="icon-wrapper icon-failure">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <h1 class="result-title" style="color: #dc3545;">Thanh Toán Thất Bại</h1>
                <p class="result-desc"><?= htmlspecialchars($error_msg) ?></p>
                
                <table class="info-table">
                    <tr>
                        <td>Mã đơn hàng:</td>
                        <td>#ORD-<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                    <tr>
                        <td>Số tiền dự kiến:</td>
                        <td style="color: #dc3545; font-weight:700;"><?= number_format($amount, 0, ',', '.') ?> VNĐ</td>
                    </tr>
                </table>
                
                <div class="btn-group">
                    <a href="profile.php" class="btn btn-primary btn-glow" style="padding: 12px 30px; background: var(--primary);"><i class="fa-solid fa-rotate-left"></i> Thanh toán lại</a>
                    <a href="index.php" class="btn btn-secondary" style="padding: 12px 30px;">Về Trang Chủ</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
