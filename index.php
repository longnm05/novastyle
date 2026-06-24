<?php
require_once 'db.php';

// Lấy 8 sản phẩm ngẫu nhiên hoặc mới nhất cho phần Sản phẩm bán chạy
$stmt = $conn->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY RAND() LIMIT 8");
$stmt->execute();
$products = $stmt->fetchAll();
?>
<?php
session_start();
require_once 'db.php';

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
    <title>NovaStyle - Tương lai của Mua sắm</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <!-- FontAwesome for standard icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function solveSimpleChallenge() {
            return true;
        }
    </script>
</head>

<body>

    <!-- Trang trí nền 3D/Gradient -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Navigation -->
    <nav class="glass-header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <i class="fa-solid fa-microchip"></i> NovaStyle
            </a>
        </div>
        <form action="search.php" method="GET" class="smart-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" name="q" placeholder="Tìm kiếm trang phục, phụ kiện (VD: áo khoác, túi xách)..." required>
            <button type="submit" class="ai-btn"><i class="fa-solid fa-arrow-right"></i></button>
        </form>
        <div class="nav-links">
            <a href="products.php" class="nav-item" style="text-decoration:none; color:var(--text-main); font-weight:600; margin-right:15px;">Sản Phẩm</a>
           <!--  <a href="#" class="nav-icon"><i class="fa-solid fa-heart"></i><span class="badge">2</span></a>-->
            <a href="cart.php" class="nav-icon" id="openCartBtn"><i class="fa-solid fa-cart-shopping"></i><span class="badge"
                    id="cartBadge"><?= $cart_count ?></span></a>
            <a href="profile.php" class="nav-icon" title="Hồ Sơ Của Tôi">
                <i class="fa-solid fa-circle-user" style="font-size: 1.5rem; color: var(--accent-purple);"></i>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <div class="badge-ai">Xu Hướng Thời Trang Mới Nhất</div>
            <h1>Nâng tầm phong cách, <br><span class="gradient-text">Khẳng định cá tính</span></h1>
            <p>Trải nghiệm mua sắm đỉnh cao. Bộ sưu tập thời trang hiện đại, đa dạng và bắt kịp xu hướng luôn sẵn sàng dành cho bạn.</p>
            <div class="hero-actions">
                <button class="btn btn-primary" onclick="window.location.href='products.php'">Khám phá Ngay <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hologram-effect">
                <div class="product-ring">
                    <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=400&h=400"
                        alt="Nike Shoe 3D">
                </div>
            </div>
        </div>
    </header>

    <!-- Categories Section -->
    <section class="categories" style="padding: 50px 5%; background: rgba(255,255,255,0.01); border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);">
        <div class="section-header" style="margin-bottom: 40px;">
            <h2 style="font-size: 2rem;"><i class="fa-solid fa-tags"></i> Danh Mục</h2>
        </div>
        <div style="display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; scrollbar-width: thin;" id="categoryFilters">
            <a href="products.php?category=nam" style="text-decoration: none; color: inherit; min-width: 150px; background: rgba(0,0,0,0.03); border: 1px solid var(--glass-border); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(138,43,226,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                <i class="fa-solid fa-shirt" style="font-size: 2rem; color: var(--accent-blue); margin-bottom: 10px;"></i>
                <h4 style="margin:0;">Áo Nam</h4>
            </a>
            <a href="products.php?category=giay" style="text-decoration: none; color: inherit; min-width: 150px; background: rgba(0,0,0,0.03); border: 1px solid var(--glass-border); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(138,43,226,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                <i class="fa-solid fa-shoe-prints" style="font-size: 2rem; color: var(--accent-purple); margin-bottom: 10px;"></i>
                <h4 style="margin:0;">Giày Dép</h4>
            </a>
            <a href="products.php?category=phukien" style="text-decoration: none; color: inherit; min-width: 150px; background: rgba(0,0,0,0.03); border: 1px solid var(--glass-border); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(138,43,226,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                <i class="fa-solid fa-glasses" style="font-size: 2rem; color: #ff416c; margin-bottom: 10px;"></i>
                <h4 style="margin:0;">Phụ Kiện</h4>
            </a>
            <a href="products.php?category=outwear" style="text-decoration: none; color: inherit; min-width: 150px; background: rgba(0,0,0,0.03); border: 1px solid var(--glass-border); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(138,43,226,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                <i class="fa-solid fa-hat-cowboy" style="font-size: 2rem; color: #00ff88; margin-bottom: 10px;"></i>
                <h4 style="margin:0;">Mũ Nón/Áo Khoác</h4>
            </a>
            <a href="products.php" style="text-decoration: none; color: inherit; min-width: 150px; background: rgba(0,0,0,0.03); border: 1px solid var(--glass-border); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(138,43,226,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                <i class="fa-solid fa-border-all" style="font-size: 2rem; color: #ffaa00; margin-bottom: 10px;"></i>
                <h4 style="margin:0;">Tất Cả</h4>
            </a>
        </div>
    </section>

    <!-- Gợi Ý Cho Bạn Section -->
    <section class="ai-curated">
        <div class="section-header">
            <h2><i class="fa-solid fa-star"></i> Sản phẩm bán chạy</h2>
            <p>Bộ sưu tập được tuyển chọn từ các xu hướng mới nhất</p>
        </div>

        <div id="noProductsFound" style="display: none; text-align: center; padding: 50px; color: var(--text-muted);">
            <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 20px;"></i>
            <h3>Không tìm thấy sản phẩm nào!</h3>
            <p>Vui lòng thử lại với từ khóa khác hoặc danh mục khác.</p>
        </div>

        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $row): ?>
            <div class="product-card">
                <div class="card-glow"></div>
                <div class="card-image">
                    <div class="ai-match">Hot Trend</div>
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
    </section>


    <!-- Login Modal Removed (Using separate login.php) -->

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
                <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.8;">Sản phẩm thiết kế độc quyền với phong cách hiện đại và thời thượng. Chất liệu cao cấp, đường may tỉ mỉ mang lại trải nghiệm tuyệt vời cho người mặc.</p>
                <div style="display: flex; gap: 15px; margin-top: auto;">
                    <input type="number" value="1" min="1" id="qvQty" style="width: 80px; padding: 10px; border: 1px solid var(--glass-border); border-radius: 10px; text-align: center; background: rgba(0,0,0,0.02); color: var(--text-main);">
                    <button class="btn btn-primary" id="qvAddToCart" style="flex: 1; justify-content: center;"><i class="fa-solid fa-cart-plus"></i> Thêm Vào Giỏ Hàng</button>
                </div>
            </div>
        </div>
    </div>

    <footer style="background: rgba(10,10,12,0.9); border-top: 1px solid var(--glass-border); padding: 50px 5% 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px;">
            <div>
                <h3 style="font-family: var(--font-heading); margin-bottom: 15px;"><i class="fa-solid fa-microchip" style="color: var(--accent-blue);"></i> NovaStyle</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Trải nghiệm mua sắm e-commerce đỉnh cao với các sản phẩm thời trang chất lượng và hiện đại.</p>
            </div>
            <div>
                <h4 style="margin-bottom: 15px;">Chính sách</h4>
                <ul style="list-style: none; padding: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><a href="#" style="color: inherit; text-decoration: none;">Giao hàng & Nhận hàng</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Chính sách đổi trả</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Bảo mật thông tin</a></li>
                </ul>
            </div>
            <div>
                <h4 style="margin-bottom: 15px;">Liên hệ</h4>
                <ul style="list-style: none; padding: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><i class="fa-solid fa-phone"></i> 1900 6868</li>
                    <li><i class="fa-solid fa-envelope"></i> support@novastyle.com</li>
                    <li><i class="fa-solid fa-location-dot"></i> 67 Đường Nguyễn Trãi, Hà Nội</li>
                </ul>
            </div>
        </div>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">&copy; 2026 NovaStyle. Bản quyền được bảo lưu.</p>
    </footer>

    <script src="script.js?v=<?= time() ?>"></script>
</body>

</html>
