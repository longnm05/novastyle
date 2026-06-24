<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để áp dụng voucher!']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$voucherCode = strtoupper(trim($data['voucher_code'] ?? ''));

if (empty($voucherCode)) {
    unset($_SESSION['applied_voucher']);
    echo json_encode(['success' => true, 'message' => 'Đã bỏ áp dụng voucher.', 'discount' => 0]);
    exit;
}

// Tính tổng giá trị giỏ hàng hiện tại
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
    exit;
}

$cartTotal = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

try {
    // Tìm voucher trong database
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE code = ? LIMIT 1");
    $stmt->execute([$voucherCode]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại!']);
        exit;
    }

    if ($voucher['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá này không tồn tại!']);
        exit;
    }

    // Kiểm tra hạn sử dụng
    if ($voucher['expiry_date'] && strtotime($voucher['expiry_date']) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá này đã hết hạn sử dụng!']);
        exit;
    }

    // Kiểm tra giá trị đơn hàng tối thiểu
    if ($cartTotal < $voucher['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher['min_order_value'], 0, ',', '.') . ' VNĐ để áp dụng mã này!'
        ]);
        exit;
    }

    // Tính toán số tiền được giảm
    $discountAmount = 0;
    if ($voucher['discount_type'] === 'percentage') {
        $discountAmount = $cartTotal * ($voucher['discount_value'] / 100);
        // Giới hạn giảm tối đa nếu có cấu hình
        if ($voucher['max_discount'] && $discountAmount > $voucher['max_discount']) {
            $discountAmount = $voucher['max_discount'];
        }
    } else {
        // Loại cố định
        $discountAmount = $voucher['discount_value'];
    }

    // Đảm bảo số tiền giảm không vượt quá tổng giá trị giỏ hàng
    if ($discountAmount > $cartTotal) {
        $discountAmount = $cartTotal;
    }

    // Lưu vào session
    $_SESSION['applied_voucher'] = [
        'code' => $voucher['code'],
        'discount_type' => $voucher['discount_type'],
        'discount_value' => $voucher['discount_value'],
        'discount_amount' => $discountAmount,
        'min_order_value' => $voucher['min_order_value'],
        'max_discount' => $voucher['max_discount']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã giảm giá thành công!',
        'code' => $voucher['code'],
        'discount' => $discountAmount,
        'new_total' => $cartTotal - $discountAmount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
