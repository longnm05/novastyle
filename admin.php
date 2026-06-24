<?php
session_start();
require_once 'db.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// 2. Xử lý các hành động (Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // QUẢN LÝ SẢN PHẨM
    if ($action === 'save_product') {
        $id = $_POST['id'] ?: 'p' . time();
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $desc = $_POST['description'];
        $image_url = $_POST['image_url'];

        // Xử lý upload ảnh nếu có
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_name = time() . '_' . basename($_FILES['image_file']['name']);
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $file_name)) {
                $image_url = $upload_dir . $file_name;
            }
        }

        if ($_POST['is_edit'] === 'true') {
            $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, stock_quantity=?, description=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $category_id, $price, $stock, $desc, $image_url, $id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (id, name, category_id, price, stock_quantity, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $name, $category_id, $price, $stock, $desc, $image_url]);
        }
        $message = "Đã lưu sản phẩm thành công!";
    }

    // QUẢN LÝ DANH MỤC
    if ($action === 'save_category') {
        $name = $_POST['name'];
        $slug = $_POST['slug'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $desc = $_POST['description'];

        if (!empty($_POST['id'])) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?");
            $stmt->execute([$name, $slug, $desc, $_POST['id']]);
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $desc]);
        }
        $message = "Đã lưu danh mục thành công!";
    }

    // QUẢN LÝ ĐƠN HÀNG - CẬP NHẬT TRẠNG THÁI
    if ($action === 'update_order_status') {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status, $order_id]);
        $message = "Đã cập nhật trạng thái đơn hàng!";
    }

    // QUẢN LÝ KHÁCH HÀNG - KHÓA/MỞ TÀI KHOẢN
    if ($action === 'toggle_user_status') {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $stmt->execute([$new_status, $user_id]);
        $message = "Đã cập nhật trạng thái khách hàng!";
    }

    // QUẢN LÝ VOUCHERS - LƯU
    if ($action === 'save_voucher') {
        $active_tab = 'vouchers';
        $id = $_POST['id'] ?? '';
        $code = strtoupper(trim($_POST['code']));
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $min_order_value = floatval($_POST['min_order_value']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $status = $_POST['status'] ?? 'active';

        try {
            if ($_POST['is_edit'] === 'true') {
                $stmt = $conn->prepare("UPDATE vouchers SET code=?, discount_type=?, discount_value=?, min_order_value=?, max_discount=?, expiry_date=?, status=? WHERE id=?");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_value, $max_discount, $expiry_date, $status, $id]);
                $message = "Đã cập nhật voucher thành công!";
            } else {
                $stmt = $conn->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_discount, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_value, $max_discount, $expiry_date, $status]);
                $message = "Đã thêm voucher thành công!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                $error = "Mã voucher đã tồn tại trên hệ thống!";
            } else {
                $error = "Lỗi hệ thống: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Xử lý Xóa (GET)
if (isset($_GET['delete_product'])) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$_GET['delete_product']]);
    header("Location: admin.php?tab=products"); exit();
}
if (isset($_GET['delete_category'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->execute([$_GET['delete_category']]);
    header("Location: admin.php?tab=categories"); exit();
}
if (isset($_GET['delete_voucher'])) {
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE id=?");
    $stmt->execute([$_GET['delete_voucher']]);
    header("Location: admin.php?tab=vouchers"); exit();
}

// 3. Lấy dữ liệu cho các Tab
// Dashboard Stats
$stats = [
    'products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'customers' => $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status='delivered'")->fetchColumn() ?: 0
];

// Data for tables
$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$customers = $conn->query("SELECT * FROM users WHERE role='customer' ORDER BY created_at DESC")->fetchAll();
$orders = $conn->query("SELECT o.*, u.full_name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
$vouchers = $conn->query("SELECT * FROM vouchers ORDER BY created_at DESC")->fetchAll();

$active_tab = $_GET['tab'] ?? $active_tab ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaStyle Admin - Quản Trị Hệ Thống</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #ff9f1c;
            --bg-body: #f8f9fa;
            --bg-sidebar: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --border: #dee2e6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); line-height: 1.5; }

        .admin-container { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }
        .logo { padding: 0 25px 30px; font-size: 1.5rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .menu { list-style: none; flex: 1; }
        .menu-item { margin: 5px 15px; }
        .menu-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 15px;
            text-decoration: none; color: var(--text-muted); border-radius: 8px;
            transition: all 0.3s; font-weight: 500;
        }
        .menu-link:hover, .menu-link.active { background: rgba(67, 97, 238, 0.08); color: var(--primary); }
        .menu-link i { width: 20px; text-align: center; }

        /* Main Content */
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 1.5rem; font-weight: 700; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .stat-card .label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }

        /* Tables & UI Elements */
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 30px; }
        .card-header { padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; }
        .btn {
            padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;
            font-weight: 500; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger { background: rgba(247, 37, 133, 0.1); color: var(--danger); }
        .btn-success { background: rgba(76, 201, 240, 0.1); color: #008eb7; }
        .btn-warning { background: rgba(255, 159, 28, 0.1); color: #cc7a00; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fcfcfd; padding: 12px 20px; text-align: left; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); font-size: 0.9rem; vertical-align: middle; }
        .product-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fff3e0; color: #ef6c00; }
        .badge-processing { background: #e3f2fd; color: #1565c0; }
        .badge-shipped { background: #e0f7fa; color: #006064; }
        .badge-delivered { background: #e8f5e9; color: #2e7d32; }
        .badge-cancelled { background: #ffebee; color: #c62828; }
        .badge-active { background: #e3f2fd; color: #1565c0; }
        .badge-locked { background: #f5f5f5; color: #616161; }

        /* Modal */
        .modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content { background: #fff; width: 90%; max-width: 500px; border-radius: 12px; padding: 30px; position: relative; }
        .modal-header { margin-bottom: 20px; font-size: 1.2rem; font-weight: 700; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-microchip"></i> NovaStyle
            </a>
        </div>
        <ul class="menu">
            <li class="menu-item"><a href="?tab=dashboard" class="menu-link <?= $active_tab == 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Thống kê</a></li>
            <li class="menu-item"><a href="?tab=products" class="menu-link <?= $active_tab == 'products' ? 'active' : '' ?>"><i class="fa-solid fa-box"></i> Sản phẩm</a></li>
            <li class="menu-item"><a href="?tab=categories" class="menu-link <?= $active_tab == 'categories' ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i> Danh mục</a></li>
            <li class="menu-item"><a href="?tab=customers" class="menu-link <?= $active_tab == 'customers' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Khách hàng</a></li>
            <li class="menu-item"><a href="?tab=orders" class="menu-link <?= $active_tab == 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Đơn hàng</a></li>
            <li class="menu-item"><a href="?tab=vouchers" class="menu-link <?= $active_tab == 'vouchers' ? 'active' : '' ?>"><i class="fa-solid fa-ticket"></i> Khuyến mãi</a></li>
        </ul>
        <div style="padding: 20px 30px;"><a href="logout.php" style="color: var(--danger); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($message): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c8e6c9;">
                <i class="fa-solid fa-circle-check"></i> <?= $message ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <i class="fa-solid fa-circle-xmark"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- TAB: DASHBOARD -->
        <?php if ($active_tab == 'dashboard'): ?>
            <div class="header"><h1>Thống kê tổng quan</h1></div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Sản phẩm</div>
                    <div class="value"><?= $stats['products'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Khách hàng</div>
                    <div class="value"><?= $stats['customers'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Đơn hàng</div>
                    <div class="value"><?= $stats['orders'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Doanh thu</div>
                    <div class="value"><?= number_format($stats['revenue'], 0, ',', '.') ?> VNĐ</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: PRODUCTS -->
        <?php if ($active_tab == 'products'): ?>
            <div class="header">
                <h1>Quản lý mặt hàng</h1>
                <button class="btn btn-primary" onclick="openProductModal()"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</button>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>ID</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Kho</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): ?>
                            <tr>
                                <td><img src="<?= $p['image_url'] ?>" class="product-img" onerror="this.src='https://placehold.co/40'"></td>
                                <td><code><?= $p['id'] ?></code></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['cat_name'] ?? 'N/A') ?></td>
                                <td><?= number_format($p['price'], 0, ',', '.') ?> VNĐ</td>
                                <td><?= $p['stock_quantity'] ?></td>
                                <td>
                                    <button class="btn btn-success" onclick="openProductModal(true, '<?= $p['id'] ?>', '<?= addslashes($p['name']) ?>', '<?= $p['category_id'] ?>', '<?= $p['price'] ?>', '<?= $p['stock_quantity'] ?>', '<?= addslashes($p['description']) ?>', '<?= $p['image_url'] ?>')"><i class="fa-solid fa-pen"></i></button>
                                    <a href="?delete_product=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: CATEGORIES -->
        <?php if ($active_tab == 'categories'): ?>
            <div class="header">
                <h1>Quản lý loại mặt hàng</h1>
                <button class="btn btn-primary" onclick="openCategoryModal()"><i class="fa-solid fa-plus"></i> Thêm danh mục</button>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Slug</th>
                                <th>Mô tả</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                <td><code><?= $c['slug'] ?></code></td>
                                <td><?= htmlspecialchars($c['description']) ?></td>
                                <td>
                                    <button class="btn btn-success" onclick="openCategoryModal(true, '<?= $c['id'] ?>', '<?= addslashes($c['name']) ?>', '<?= $c['slug'] ?>', '<?= addslashes($c['description']) ?>')"><i class="fa-solid fa-pen"></i></button>
                                    <a href="?delete_category=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Xóa danh mục sẽ xóa toàn bộ sản phẩm thuộc danh mục này. Bạn chắc chứ?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: CUSTOMERS -->
        <?php if ($active_tab == 'customers'): ?>
            <div class="header"><h1>Quản lý khách hàng</h1></div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Họ tên</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($customers as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($u['username'] ?? '') ?></code></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                                <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_user_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($u['status'] === 'active'): ?>
                                            <input type="hidden" name="status" value="locked">
                                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-lock"></i> Khóa</button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-unlock"></i> Mở</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: ORDERS -->
        <?php if ($active_tab == 'orders'): ?>
            <div class="header"><h1>Quản lý đơn đặt hàng</h1></div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o): ?>
                            <tr>
                                <td><code>#<?= $o['id'] ?></code></td>
                                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                <td><strong><?= number_format($o['total_amount'], 0, ',', '.') ?> VNĐ</strong></td>
                                <td>
                                    <?php if(isset($o['payment_method']) && $o['payment_method'] === 'online'): ?>
                                        <span style="color: var(--secondary); font-weight: 600;"><i class="fa-solid fa-credit-card"></i> Online</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-truck"></i> COD</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $admin_status_labels = [
                                            'pending' => 'Chờ thanh toán',
                                            'processing' => 'Đã thanh toán / Đang chuẩn bị',
                                            'shipped' => 'Đang giao',
                                            'delivered' => 'Đã giao',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        $lbl_status = $admin_status_labels[$o['status']] ?? $o['status'];
                                    ?>
                                    <span class="badge badge-<?= $o['status'] ?>"><?= $lbl_status ?></span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" style="padding: 5px; border-radius: 4px; border: 1px solid var(--border);">
                                            <option value="pending" <?= $o['status'] == 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                            <option value="processing" <?= $o['status'] == 'processing' ? 'selected' : '' ?>>Đã thanh toán / Chuẩn bị</option>
                                            <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>>Đang giao</option>
                                            <option value="delivered" <?= $o['status'] == 'delivered' ? 'selected' : '' ?>>Đã giao</option>
                                            <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding: 5px 10px;"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: VOUCHERS -->
        <?php if ($active_tab == 'vouchers'): ?>
            <div class="header">
                <h1>Quản lý mã khuyến mãi (Vouchers)</h1>
                <button class="btn btn-primary" onclick="openVoucherModal()"><i class="fa-solid fa-plus"></i> Thêm Voucher</button>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã Voucher</th>
                                <th>Loại giảm giá</th>
                                <th>Giá trị</th>
                                <th>Đơn tối thiểu</th>
                                <th>Giảm tối đa</th>
                                <th>Hạn sử dụng</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($vouchers as $v): ?>
                            <tr>
                                <td><code><?= $v['id'] ?></code></td>
                                <td><strong style="color: var(--primary);"><?= htmlspecialchars($v['code']) ?></strong></td>
                                <td>
                                    <?= $v['discount_type'] === 'percentage' ? 'Phần trăm (%)' : 'Số tiền cố định (VNĐ)' ?>
                                </td>
                                <td>
                                    <?= $v['discount_type'] === 'percentage' ? number_format($v['discount_value'], 0) . '%' : number_format($v['discount_value'], 0, ',', '.') . ' VNĐ' ?>
                                </td>
                                <td><?= number_format($v['min_order_value'], 0, ',', '.') ?> VNĐ</td>
                                <td><?= $v['max_discount'] ? number_format($v['max_discount'], 0, ',', '.') . ' VNĐ' : 'Không giới hạn' ?></td>
                                <td><?= $v['expiry_date'] ? date('d/m/Y', strtotime($v['expiry_date'])) : 'Không giới hạn' ?></td>
                                <td>
                                    <span class="badge" style="background: <?= $v['status'] === 'active' ? '#e8f5e9; color: #2e7d32;' : '#ffebee; color: #c62828;' ?>;">
                                        <?= $v['status'] === 'active' ? 'Đang hoạt động' : 'Tạm khóa' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn" style="background: rgba(67, 97, 238, 0.1); color: var(--primary); padding: 5px 10px;" onclick="openVoucherModal(true, '<?= $v['id'] ?>', '<?= addslashes($v['code']) ?>', '<?= $v['discount_type'] ?>', '<?= $v['discount_value'] ?>', '<?= $v['min_order_value'] ?>', '<?= $v['max_discount'] ?>', '<?= $v['expiry_date'] ?>', '<?= $v['status'] ?>')"><i class="fa-solid fa-pen"></i></button>
                                    <a href="?delete_voucher=<?= $v['id'] ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Bạn có chắc chắn muốn xóa voucher này?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal Sản Phẩm -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-header" id="pModalTitle">Thêm sản phẩm</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="is_edit" id="p_is_edit" value="false">
            <input type="hidden" name="id" id="p_id">
            <div class="form-group">
                <label>Tên sản phẩm</label>
                <input type="text" name="name" id="p_name" required>
            </div>
            <div class="form-group">
                <label>Danh mục</label>
                <select name="category_id" id="p_category" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Giá (VNĐ)</label>
                    <input type="number" step="1" name="price" id="p_price" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Số lượng kho</label>
                    <input type="number" name="stock" id="p_stock" required>
                </div>
            </div>
            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description" id="p_desc" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Ảnh sản phẩm</label>
                <input type="file" name="image_file" style="margin-bottom: 5px;">
                <input type="text" name="image_url" id="p_image_url" placeholder="Hoặc nhập URL ảnh">
            </div>
            <div style="display:flex; gap:10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal('productModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Danh Mục -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-header" id="cModalTitle">Thêm danh mục</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" id="c_id">
            <div class="form-group">
                <label>Tên danh mục</label>
                <input type="text" name="name" id="c_name" required>
            </div>
            <div class="form-group">
                <label>Slug (Không bắt buộc)</label>
                <input type="text" name="slug" id="c_slug" placeholder="tên-danh-mục">
            </div>
            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description" id="c_desc" rows="3"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal('categoryModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voucher -->
<div id="voucherModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-header" id="vModalTitle">Thêm voucher</h3>
        <form method="POST" onsubmit="return validateVoucherForm();">
            <input type="hidden" name="action" value="save_voucher">
            <input type="hidden" name="is_edit" id="v_is_edit" value="false">
            <input type="hidden" name="id" id="v_id">
            <div class="form-group">
                <label>Mã Voucher</label>
                <input type="text" name="code" id="v_code" required placeholder="" style="text-transform: uppercase;">
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Loại giảm giá</label>
                    <select name="discount_type" id="v_discount_type" onchange="toggleVoucherFields()" required>
                        <option value="percentage">Phần trăm (%)</option>
                        <option value="fixed">Số tiền cố định (VNĐ)</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label id="lblDiscountValue">Giá trị giảm (%)</label>
                    <input type="text" name="discount_value" id="v_discount_value" required placeholder="" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');">
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Đơn tối thiểu (VNĐ)</label>
                    <input type="text" name="min_order_value" id="v_min_order_value" value="0" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
                <div class="form-group" style="flex: 1;" id="maxDiscountGroup">
                    <label>Giảm tối đa (VNĐ)</label>
                    <input type="text" name="max_discount" id="v_max_discount" placeholder="" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Ngày hết hạn</label>
                    <input type="date" name="expiry_date" id="v_expiry_date">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Trạng thái</label>
                    <select name="status" id="v_status">
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Tạm khóa</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" class="btn" onclick="closeModal('voucherModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function openProductModal(edit = false, id='', name='', cat='', price='', stock='', desc='', img='') {
        document.getElementById('productModal').style.display = 'flex';
        document.getElementById('pModalTitle').innerText = edit ? "Chỉnh sửa sản phẩm" : "Thêm sản phẩm";
        document.getElementById('p_is_edit').value = edit;
        document.getElementById('p_id').value = id;
        document.getElementById('p_name').value = name;
        document.getElementById('p_category').value = cat;
        document.getElementById('p_price').value = price;
        document.getElementById('p_stock').value = stock;
        document.getElementById('p_desc').value = desc;
        document.getElementById('p_image_url').value = img;
    }

    function openCategoryModal(edit = false, id='', name='', slug='', desc='') {
        document.getElementById('categoryModal').style.display = 'flex';
        document.getElementById('cModalTitle').innerText = edit ? "Chỉnh sửa danh mục" : "Thêm danh mục";
        document.getElementById('c_id').value = id;
        document.getElementById('c_name').value = name;
        document.getElementById('c_slug').value = slug;
        document.getElementById('c_desc').value = desc;
    }

    function openVoucherModal(edit = false, id='', code='', type='percentage', val='', min='0', max='', expiry='', status='active') {
        document.getElementById('voucherModal').style.display = 'flex';
        document.getElementById('vModalTitle').innerText = edit ? "Chỉnh sửa voucher" : "Thêm voucher";
        document.getElementById('v_is_edit').value = edit;
        document.getElementById('v_id').value = id;
        document.getElementById('v_code').value = code;
        document.getElementById('v_discount_type').value = type;
        document.getElementById('v_discount_value').value = val;
        document.getElementById('v_min_order_value').value = min;
        document.getElementById('v_max_discount').value = max;
        document.getElementById('v_expiry_date').value = expiry;
        document.getElementById('v_status').value = status;
        toggleVoucherFields();
    }

    function toggleVoucherFields() {
        const type = document.getElementById('v_discount_type').value;
        const lblValue = document.getElementById('lblDiscountValue');
        const maxGroup = document.getElementById('maxDiscountGroup');
        if (type === 'percentage') {
            lblValue.innerText = "Giá trị giảm (%)";
            maxGroup.style.display = 'block';
        } else {
            lblValue.innerText = "Giá trị giảm (VNĐ)";
            maxGroup.style.display = 'none';
            document.getElementById('v_max_discount').value = '';
        }
    }

    function validateVoucherForm() {
        const type = document.getElementById('v_discount_type').value;
        const valInput = document.getElementById('v_discount_value');
        const val = parseFloat(valInput.value);
        
        if (isNaN(val) || val <= 0) {
            alert("Giá trị giảm phải là số lớn hơn 0!");
            valInput.focus();
            return false;
        }
        
        if (type === 'percentage' && val > 100) {
            alert("Giá trị giảm theo phần trăm không được vượt quá 100%!");
            valInput.focus();
            return false;
        }
        
        return true;
    }
</script>

</body>
</html>
