<?php
session_start();
require_once 'db.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

$products = [];
if ($searchQuery !== '') {
    // Tìm kiếm phân biệt dấu bằng cách chuyển về nhị phân BINARY kết hợp LOWER
    $stmt = $conn->prepare("SELECT p.*, c.name as cat_name 
                             FROM products p 
                             JOIN categories c ON p.category_id = c.id 
                             WHERE BINARY LOWER(p.name) LIKE BINARY LOWER(?) 
                                OR BINARY LOWER(p.description) LIKE BINARY LOWER(?) 
                                OR BINARY LOWER(p.ai_tags) LIKE BINARY LOWER(?) 
                                OR BINARY LOWER(c.name) LIKE BINARY LOWER(?)");
    $likeQuery = "%" . $searchQuery . "%";
    $stmt->execute([$likeQuery, $likeQuery, $likeQuery, $likeQuery]);
    $products = $stmt->fetchAll();
}

$visibleCount = count($products);

// Tính tổng số lượng sản phẩm trong giỏ hàng (Session)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm Kiếm: "<?= htmlspecialchars($searchQuery) ?>" - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-light);
            padding-top: 100px;
        }

        .products-layout {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 5%;
        }

        .products-main {
            width: 100%;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
        }

        .search-page-bar {
            display: flex;
            align-items: center;
            max-width: 600px;
            margin: 0 auto 40px;
            position: relative;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 5px 5px 5px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .search-page-bar input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-main);
            font-family: var(--font-body);
            font-size: 1.1rem;
            padding: 10px 10px 10px 0;
        }

        .search-page-bar button {
            background: var(--primary-gradient);
            border: none;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            font-family: var(--font-heading);
            padding: 12px 30px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .search-page-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(138,43,226,0.3);
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
            <a href="products.php" class="nav-item">Sản Phẩm</a>
            <a href="cart.php" class="nav-icon" id="openCartBtn"><i class="fa-solid fa-cart-shopping"></i><span class="badge" id="cartBadge"><?= $cart_count ?></span></a>
            <a href="profile.php" class="nav-icon" title="Hồ Sơ Của Tôi"><i class="fa-solid fa-user"></i></a>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="products-layout">
        <!-- Re-search bar for convenience -->
        <form action="search.php" method="GET" class="search-page-bar">
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($searchQuery) ?>" required>
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm</button>
        </form>

        <main class="products-main">
            <div class="products-header">
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem;">
                        Kết quả tìm kiếm cho: "<span class="gradient-text"><?= htmlspecialchars($searchQuery) ?></span>"
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Tìm thấy <?= $visibleCount ?> sản phẩm phù hợp</p>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 80px 20px; background: rgba(255,255,255,0.5); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border);">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                    <h3 style="font-size: 1.5rem; margin-bottom: 10px;">Không tìm thấy sản phẩm nào</h3>
                    <p style="color: var(--text-muted);">Hãy thử tìm kiếm với các từ khóa khác như "áo", "giày", "túi"...</p>
                </div>
            <?php else: ?>
                <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                    <?php foreach ($products as $row): ?>
                    <div class="product-card">
                        <div class="card-glow"></div>
                        <div class="card-image">
                            <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                            <button class="quick-view"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        <div class="card-info">
                            <span class="category"><?= htmlspecialchars($row['cat_name']) ?></span>
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <div class="price-row">
                                <span class="price"><?= number_format($row['price'], 0, ',', '.') ?> VNĐ</span>
                                <button class="add-to-cart" data-id="<?= htmlspecialchars($row['id']) ?>" data-name="<?= htmlspecialchars($row['name']) ?>" data-price="<?= $row['price'] ?>" data-image="<?= htmlspecialchars($row['image_url']) ?>">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Quick View Modal -->
    <div class="modal-overlay" id="quickViewOverlay">
        <div class="modal-content" id="quickViewModal" style="width: 90%; max-width: 800px; display: flex; flex-wrap: wrap; gap: 30px; position: relative;">
            <button id="closeQuickView" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-main); z-index: 10;"><i class="fa-solid fa-times"></i></button>
            <div style="flex: 1; min-width: 300px;">
                <img id="qvImage" src="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            </div>
            <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; justify-content: center;">
                <span id="qvCategory" class="category" style="margin-bottom: 10px; display: inline-block;"></span>
                <h2 id="qvTitle" style="font-family: var(--font-heading); font-size: 2rem; margin-bottom: 15px; color: var(--text-main);"></h2>
                <div style="font-size: 2rem; font-weight: 800; color: var(--accent-blue); margin-bottom: 20px;" id="qvPrice"></div>
                <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.8;">Sản phẩm thiết kế độc quyền, được AI phân tích có độ tương thích 95% với phong cách hiện tại của bạn. Chất liệu cao cấp, đường may tỉ mỉ mang lại trải nghiệm tuyệt vời.</p>
                <div style="display: flex; gap: 15px; margin-top: auto;">
                    <input type="number" value="1" min="1" id="qvQty" style="width: 80px; padding: 10px; border: 1px solid var(--glass-border); border-radius: 10px; text-align: center; background: rgba(0,0,0,0.02); color: var(--text-main);">
                    <button class="btn btn-primary" id="qvAddToCart" style="flex: 1; justify-content: center;"><i class="fa-solid fa-cart-plus"></i> Thêm Vào Giỏ Hàng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?= time() ?>"></script>
</body>
</html>
