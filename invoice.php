<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmtOrder = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id 
                             WHERE o.id = ? AND o.user_id = ?");
$stmtOrder->execute([$orderId, $userId]);
$order = $stmtOrder->fetch();

if (!$order) {
    die("Không tìm thấy đơn hàng!");
}

$status_names = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đã thanh toán / Đang xử lý',
    'shipped' => 'Đang giao hàng',
    'delivered' => 'Đã giao hàng',
    'cancelled' => 'Đã hủy'
];
$status_text = $status_names[$order['status']] ?? $order['status'];

// Lấy danh sách sản phẩm trong đơn hàng
$stmtItems = $conn->prepare("SELECT oi.*, p.name, p.image_url 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn #<?= $orderId ?> - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 150px auto 50px;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-info h2 {
            font-family: var(--font-heading);
            color: var(--accent-purple);
            margin-bottom: 5px;
        }
        .customer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }
        .customer-details h4 {
            margin-bottom: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th {
            text-align: left;
            padding: 12px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            text-align: right;
            font-size: 1.2rem;
            font-weight: 800;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        @media print {
            .nav-links, .glass-header, .btn-print-area { display: none; }
            .invoice-container { margin: 0; box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>
    <nav class="glass-header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <i class="fa-solid fa-microchip"></i> NovaStyle
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Trang Chủ</a>
            <a href="profile.php" class="nav-item">Hồ Sơ</a>
        </div>
    </nav>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-info">
                <h2>HÓA ĐƠN ĐIỆN TỬ</h2>
                <p>Mã đơn hàng: <strong>#<?= $orderId ?></strong></p>
                <p>Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
            </div>
            <div style="text-align: right;">
                <span class="status-badge status-<?= $order['status'] ?>"><?= $status_text ?></span>
            </div>
        </div>

        <div class="customer-details">
            <div>
                <h4>Thông tin khách hàng</h4>
                <p><strong><?= htmlspecialchars($order['full_name']) ?></strong></p>
                <p><?= htmlspecialchars($order['email']) ?></p>
                <p><?= htmlspecialchars($order['phone']) ?></p>
            </div>
            <div>
                <h4>Thông tin giao hàng & Thanh toán</h4>
                <p><strong>Địa chỉ:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                <p style="margin-top: 10px;"><strong>Thanh toán:</strong> <?= $order['payment_method'] === 'online' ? 'Thanh toán Online' : 'COD (Thanh toán khi nhận hàng)' ?></p>
                <p><strong>Trạng thái thanh toán:</strong> 
                    <?php
                    if ($order['payment_method'] === 'online') {
                        if ($order['status'] === 'pending') {
                            echo '<span style="color:#d39e00; font-weight:600;">Chưa thanh toán</span>';
                        } elseif ($order['status'] === 'cancelled') {
                            echo '<span style="color:#dc3545; font-weight:600;">Đã hủy</span>';
                        } else {
                            echo '<span style="color:#28a745; font-weight:600;">Đã thanh toán</span>';
                        }
                    } else {
                        // COD
                        if ($order['status'] === 'delivered') {
                            echo '<span style="color:#28a745; font-weight:600;">Đã thanh toán (COD)</span>';
                        } elseif ($order['status'] === 'cancelled') {
                            echo '<span style="color:#dc3545; font-weight:600;">Đã hủy</span>';
                        } else {
                            echo '<span style="color:#d39e00; font-weight:600;">Chưa thanh toán (Sẽ thanh toán khi nhận hàng)</span>';
                        }
                    }
                    ?>
                </p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price_at_purchase'], 0, ',', '.') ?> VNĐ</td>
                    <td style="text-align: right;"><?= number_format($item['quantity'] * $item['price_at_purchase'], 0, ',', '.') ?> VNĐ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php $subtotal = $order['total_amount'] + ($order['discount_amount'] ?? 0); ?>
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; margin-top: 30px; border-top: 2px solid #f0f0f0; padding-top: 20px;">
            <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <div style="font-size: 0.95rem; color: var(--text-muted);">
                    Tạm tính: <strong><?= number_format($subtotal, 0, ',', '.') ?> VNĐ</strong>
                </div>
                <div style="font-size: 0.95rem; color: #28a745; font-weight: 500;">
                    Giảm giá (Voucher: <?= htmlspecialchars($order['voucher_code']) ?>): <strong>-<?= number_format($order['discount_amount'], 0, ',', '.') ?> VNĐ</strong>
                </div>
            <?php endif; ?>
            <div class="total-row" style="margin-top: 5px; font-size: 1.3rem;">
                Tổng cộng: <span class="gradient-text"><?= number_format($order['total_amount'], 0, ',', '.') ?> VNĐ</span>
            </div>
        </div>

        <div class="btn-print-area" style="margin-top: 40px; display: flex; gap: 15px;">
            <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> In hóa đơn</button>
            <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    </div>
</body>
</html>
