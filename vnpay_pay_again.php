<?php
session_start();
require_once 'db.php';
require_once 'vnpay_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

// Lấy thông tin đơn hàng kiểm tra tính hợp lệ
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending' AND payment_method = 'online'");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    die("Đơn hàng không hợp lệ, không thuộc về bạn hoặc đã được thanh toán!");
}

$vnp_TxnRef = $order['id']; 
$vnp_OrderInfo = "Thanh toan lai don hang #" . $order['id'];
$vnp_OrderType = "billpayment";
$vnp_Amount = $order['total_amount'] * 100;
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

header("Location: " . $vnp_Url);
exit();
?>
