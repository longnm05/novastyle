<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);

    if (empty($product_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Mã sản phẩm không hợp lệ']);
        exit();
    }

    // Lấy thông tin sản phẩm từ DB để đảm bảo giá và thông tin chính xác
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại']);
        exit();
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image_url'],
            'quantity' => $quantity
        ];
    }

    $total_items = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_items += $item['quantity'];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Đã thêm vào giỏ hàng',
        'total_items' => $total_items
    ]);
    exit();
}
?>
