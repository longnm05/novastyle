<?php
session_start();
require_once 'db.php';

// Lấy danh sách danh mục để hiển thị ở sidebar và kiểm tra điều kiện lọc
$catStmt = $conn->prepare("SELECT * FROM categories");
$catStmt->execute();
$categories = $catStmt->fetchAll();

// Khởi tạo các mảng điều kiện lọc
$whereClauses = [];
$params = [];

// Xử lý lọc theo category slug đơn lẻ (từ URL trang chủ/hồ sơ)
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $catParam = $_GET['category'];
    if ($catParam === 'nam') {
        $whereClauses[] = "c.slug = ?";
        $params[] = 'thoi-trang-nam';
    } elseif ($catParam === 'nu') {
        $whereClauses[] = "c.slug = ?";
        $params[] = 'thoi-trang-nu';
    } elseif ($catParam === 'giay') {
        $whereClauses[] = "c.slug = ?";
        $params[] = 'giay-the-thao';
    } elseif ($catParam === 'phukien') {
        $whereClauses[] = "c.slug = ?";
        $params[] = 'phu-kien';
    } else {
        $whereClauses[] = "c.slug = ?";
        $params[] = $catParam;
    }
}

// Xử lý lọc theo danh sách checkbox categories (Mảng các ID)
$selectedCategories = [];
if (isset($_GET['categories']) && is_array($_GET['categories'])) {
    $selectedCategories = array_map('intval', $_GET['categories']);
    if (!empty($selectedCategories)) {
        $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
        $whereClauses[] = "p.category_id IN ($placeholders)";
        $params = array_merge($params, $selectedCategories);
    }
}

// Xử lý lọc theo khoảng giá
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $whereClauses[] = "p.price >= ?";
    $params[] = floatval($_GET['price_min']);
}
if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $whereClauses[] = "p.price <= ?";
    $params[] = floatval($_GET['price_max']);
}
// Xây dựng câu SQL truy vấn sản phẩm kèm điều kiện lọc
$sql = "SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id = c.id";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

// 1. Phân trang (6 sản phẩm / trang)
$limit = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// 2. Tính tổng số sản phẩm sau khi lọc
$countSql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id";
if (!empty($whereClauses)) {
    $countSql .= " WHERE " . implode(" AND ", $whereClauses);
}
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// 3. Lấy sản phẩm cho trang hiện tại
$sql = "SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id = c.id";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Lấy danh hiệu hiển thị (title) và đếm sản phẩm hiển thị
$headerTitle = "Tất cả sản phẩm";
if (!empty($selectedCategories) || isset($_GET['category'])) {
    $chosenNames = [];
    foreach ($categories as $cat) {
        $isChecked = in_array($cat['id'], $selectedCategories);
        if (isset($_GET['category'])) {
            $catParam = $_GET['category'];
            if (($catParam === 'nam' && $cat['slug'] === 'thoi-trang-nam') ||
                ($catParam === 'nu' && $cat['slug'] === 'thoi-trang-nu') ||
                ($catParam === 'giay' && $cat['slug'] === 'giay-the-thao') ||
                ($catParam === 'phukien' && $cat['slug'] === 'phu-kien') ||
                ($catParam === $cat['slug'])) {
                $isChecked = true;
            }
        }
        if ($isChecked) {
            $chosenNames[] = $cat['name'];
        }
    }
    if (!empty($chosenNames)) {
        $headerTitle = implode(", ", $chosenNames);
    }
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
    <title>Sản Phẩm - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-light);
            padding-top: 100px;
        }

        .products-layout {
            display: flex;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 5%;
        }

        /* Sidebar Filter */
        .filter-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .filter-group {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .filter-title {
            font-family: var(--font-heading);
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--text-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-list {
            list-style: none;
            padding: 0;
        }

        .filter-list li {
            margin-bottom: 10px;
        }

        .filter-list label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .filter-list label:hover {
            color: var(--accent-blue);
        }

        .filter-list input[type="checkbox"] {
            accent-color: var(--accent-purple);
            width: 16px;
            height: 16px;
        }

        /* Price Range */
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .price-inputs input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            background: rgba(0,0,0,0.02);
            color: var(--text-main);
            font-family: var(--font-body);
        }

        /* Main Content */
        .products-main {
            flex: 1;
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

        .sort-select {
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: rgba(0,0,0,0.02);
            font-family: var(--font-body);
            color: var(--text-main);
            cursor: pointer;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 50px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-smooth);
            font-weight: 600;
        }

        .page-btn.active, .page-btn:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
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
        <form action="search.php" method="GET" class="smart-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" name="q" placeholder="Tìm kiếm trang phục, phụ kiện (VD: áo khoác, túi xách)..." required>
            <button type="submit" class="ai-btn"><i class="fa-solid fa-arrow-right"></i></button>
        </form>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Trang Chủ</a>
            <a href="cart.php" class="nav-icon" id="openCartBtn"><i class="fa-solid fa-cart-shopping"></i><span class="badge" id="cartBadge"><?= $cart_count ?></span></a>
            <a href="profile.php" class="nav-icon" title="Hồ Sơ Của Tôi"><i class="fa-solid fa-user"></i></a>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="products-layout">
        
        <!-- Sidebar Filter -->
        <aside class="filter-sidebar">
            <form method="GET" action="products.php" id="filterForm">
                <div class="filter-group">
                    <h3 class="filter-title">Danh Mục <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem;"></i></h3>
                    <ul class="filter-list">
                        <li>
                            <label>
                                <input type="checkbox" id="cat-all" name="all" value="1" 
                                       <?= (empty($selectedCategories) && !isset($_GET['category'])) ? 'checked' : '' ?>
                                       onchange="if(this.checked) { document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = false); this.form.submit(); }"> 
                                Tất cả sản phẩm
                            </label>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <?php 
                            $isChecked = in_array($cat['id'], $selectedCategories);
                            if (isset($_GET['category'])) {
                                $catParam = $_GET['category'];
                                if (($catParam === 'nam' && $cat['slug'] === 'thoi-trang-nam') ||
                                    ($catParam === 'nu' && $cat['slug'] === 'thoi-trang-nu') ||
                                    ($catParam === 'giay' && $cat['slug'] === 'giay-the-thao') ||
                                    ($catParam === 'phukien' && $cat['slug'] === 'phu-kien') ||
                                    ($catParam === $cat['slug'])) {
                                    $isChecked = true;
                                }
                            }
                            ?>
                            <li>
                                <label>
                                    <input type="checkbox" class="category-checkbox" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>" 
                                           <?= $isChecked ? 'checked' : '' ?>
                                           onchange="document.getElementById('cat-all').checked = false; this.form.submit();"> 
                                    <?= htmlspecialchars($cat['name']) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">Khoảng Giá <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem;"></i></h3>
                    <div class="price-inputs">
                        <input type="number" name="price_min" placeholder="Từ (đ)" value="<?= isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : '' ?>">
                        <span>-</span>
                        <input type="number" name="price_max" placeholder="Đến (đ)" value="<?= isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; display: block; margin-top: 15px; padding: 8px 20px; font-size: 0.9rem;">Lọc Giá</button>
                </div>
            </form>
        </aside>

        <!-- Product Grid Area -->
        <main class="products-main">
            <div class="products-header">
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem;"><?= htmlspecialchars($headerTitle) ?></h2>
                </div>
            </div>

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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <?php
            // Xây dựng tiền tố link phân trang (giữ lại các bộ lọc khác)
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            $linkPrefix = 'products.php?' . ($queryString ? $queryString . '&' : '');
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= $linkPrefix ?>page=<?= $page - 1 ?>" class="page-btn"><i class="fa-solid fa-arrow-left"></i></a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= $linkPrefix ?>page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $linkPrefix ?>page=<?= $page + 1 ?>" class="page-btn"><i class="fa-solid fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Quick View Modal (Reused from index.html) -->
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
