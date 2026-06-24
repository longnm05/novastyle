<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thanh toán!', 'redirect' => 'login.php']);
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$shippingAddress = $data['shipping_address'] ?? 'Chưa cập nhật địa chỉ';
$paymentMethod = $data['payment_method'] ?? 'cod';

$totalAmount = 0;
foreach ($cart as $id => $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

$discountAmount = 0;
$voucherCode = null;

if (isset($_SESSION['applied_voucher'])) {
    $voucher = $_SESSION['applied_voucher'];
    $voucherCode = $voucher['code'];
    $discountAmount = $voucher['discount_amount'];
}

// Tính tổng tiền sau khi trừ giảm giá
$finalTotalAmount = max(0, $totalAmount - $discountAmount);

try {
    $conn->beginTransaction();

    // 1. Chèn vào bảng orders (lưu vết voucher và số tiền giảm giá)
    $stmtOrder = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, voucher_code, discount_amount) VALUES (?, ?, 'pending', ?, ?, ?, ?)");
    $stmtOrder->execute([$userId, $finalTotalAmount, $shippingAddress, $paymentMethod, $voucherCode, $discountAmount]);
    $orderId = $conn->lastInsertId();

    // 2. Chèn vào bảng order_items
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
    foreach ($cart as $id => $item) {
        $stmtItem->execute([$orderId, $id, $item['quantity'], $item['price']]);
    }

    $conn->commit();

    // Xóa giỏ hàng và voucher sau khi đặt thành công
    unset($_SESSION['cart']);
    unset($_SESSION['applied_voucher']);

    $redirectUrl = 'invoice.php?id=' . $orderId;

    if ($paymentMethod === 'online') {
        require_once 'vnpay_config.php';
        
        $vnp_TxnRef = $orderId; 
        $vnp_OrderInfo = "Thanh toan don hang #" . $orderId;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $finalTotalAmount * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $vnp_Params = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        ksort($vnp_Params);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($vnp_Params as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $redirectUrl = $vnp_Url;
    }

    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $redirectUrl]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
