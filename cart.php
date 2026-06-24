<?php
session_start();
require_once 'db.php';

// Xử lý cập nhật số lượng hoặc xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $id = $_POST['product_id'];
        if ($_POST['action'] === 'update') {
            $qty = intval($_POST['quantity']);
            if ($qty > 0) {
                $_SESSION['cart'][$id]['quantity'] = $qty;
            } else {
                unset($_SESSION['cart'][$id]);
            }
        } elseif ($_POST['action'] === 'remove') {
            unset($_SESSION['cart'][$id]);
        }
        header("Location: cart.php");
        exit();
    }
}

$cart = $_SESSION['cart'] ?? [];
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-page {
            max-width: 1000px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        @media (max-width: 900px) {
            .cart-container { grid-template-columns: 1fr; }
        }
        .cart-list {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
        }
        .cart-item-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .cart-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .item-img {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
        }
        .item-details { flex: 1; }
        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-main);
        }
        .item-price {
            color: var(--accent-blue);
            font-weight: 700;
        }
        .qty-box {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.05);
            padding: 5px 15px;
            border-radius: 10px;
        }
        .qty-input {
            width: 40px;
            text-align: center;
            background: transparent;
            border: none;
            color: white;
            font-weight: 600;
        }
        .remove-btn {
            background: transparent;
            border: none;
            color: #ff4d4d;
            cursor: pointer;
            font-size: 1.2rem;
            transition: 0.3s;
        }
        .remove-btn:hover { transform: scale(1.1); }
        
        .cart-summary {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            height: fit-content;
        }
        .summary-title {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: var(--text-muted);
        }
        .total-row {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--accent-blue);
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: var(--primary-gradient);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            margin-top: 30px;
            transition: 0.3s;
        }
        .checkout-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3); }
        .empty-cart {
            text-align: center;
            padding: 50px;
        }
    </style>
</head>
<body>
    <!-- Background -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Navigation -->
    <nav class="glass-header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <i class="fa-solid fa-microchip"></i> NovaStyle
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Trang Chủ</a>
            <a href="products.php" class="nav-item">Sản Phẩm</a>
            <a href="profile.php" class="nav-icon"><i class="fa-solid fa-user"></i></a>
        </div>
    </nav>

    <div class="cart-page">
        <h1 style="font-family: var(--font-heading); font-size: 2.5rem; font-weight: 800; margin-bottom: 40px;">Giỏ Hàng Của Bạn</h1>
        
        <?php if (empty($cart)): ?>
            <div class="cart-list empty-cart">
                <i class="fa-solid fa-cart-shopping" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                <h2>Giỏ hàng của bạn đang trống</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;">Hãy tiếp tục mua sắm để tìm thấy những sản phẩm ưng ý nhất.</p>
                <a href="products.php" class="checkout-btn" style="display:inline-block; width:auto; padding: 12px 30px;">Quay lại cửa hàng</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-list">
                    <?php foreach ($cart as $id => $item): ?>
                        <div class="cart-item-row">
                            <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="item-img">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-price"><?= number_format($item['price'], 0, ',', '.') ?> VNĐ</div>
                            </div>
                            <form method="POST" style="display:flex; align-items:center; gap:10px;">
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="update">
                                <div class="qty-box">
                                    <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" class="remove-btn" style="color:white; font-size:1rem;">-</button>
                                    <input type="text" class="qty-input" value="<?= $item['quantity'] ?>" readonly>
                                    <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" class="remove-btn" style="color:white; font-size:1rem;">+</button>
                                </div>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="remove-btn" title="Xóa sản phẩm"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2 class="summary-title">Tổng đơn hàng</h2>
                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <span><?= number_format($total_price, 0, ',', '.') ?> VNĐ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <span>Miễn phí</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Tổng cộng</span>
                        <span><?= number_format($total_price, 0, ',', '.') ?> VNĐ</span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">TIẾN HÀNH THANH TOÁN</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer style="margin-top: 100px; padding: 40px; text-align: center; border-top: 1px solid var(--glass-border);">
        <p>&copy; 2026 NovaStyle. Trải nghiệm mua sắm đẳng cấp.</p>
    </footer>

</body>
</html>
