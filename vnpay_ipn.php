<?php
require_once 'db.php';
require_once 'vnpay_config.php';

header('Content-Type: application/json');

$vnp_Params = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $vnp_Params[$key] = $value;
    }
}
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
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

try {
    $orderId = isset($_GET['vnp_TxnRef']) ? intval($_GET['vnp_TxnRef']) : 0;
    $vnp_Amount = isset($_GET['vnp_Amount']) ? intval($_GET['vnp_Amount']) : 0;
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';

    if ($secureHash === $vnp_SecureHash) {
        // 1. Kiểm tra đơn hàng trong CSDL
        $stmtOrder = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch();

        if ($order) {
            // 2. Kiểm tra số tiền gửi sang (VNPAY gửi số tiền nhân 100)
            $dbAmount = round($order['total_amount'] * 100);
            if ($dbAmount == $vnp_Amount) {
                // 3. Kiểm tra trạng thái đơn hàng hiện tại
                if ($order['status'] === 'pending') {
                    if ($vnp_ResponseCode === '00') {
                        // Thanh toán thành công
                        $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                        $stmtUpdate->execute([$orderId]);
                        
                        echo json_encode(["RspCode" => "00", "Message" => "Confirm Success"]);
                    } else {
                        // Thanh toán thất bại hoặc khách hủy
                        $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                        $stmtUpdate->execute([$orderId]);
                        
                        echo json_encode(["RspCode" => "00", "Message" => "Confirm Success (Cancelled)"]);
                    }
                } else {
                    echo json_encode(["RspCode" => "02", "Message" => "Order already confirmed"]);
                }
            } else {
                echo json_encode(["RspCode" => "04", "Message" => "Invalid amount"]);
            }
        } else {
            echo json_encode(["RspCode" => "01", "Message" => "Order not found"]);
        }
    } else {
        echo json_encode(["RspCode" => "97", "Message" => "Invalid signature"]);
    }
} catch (Exception $e) {
    echo json_encode(["RspCode" => "99", "Message" => "System error: " . $e->getMessage()]);
}
?>
