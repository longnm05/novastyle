<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Reset voucher cũ khi vào trang thanh toán mới để tránh nhầm lẫn
unset($_SESSION['applied_voucher']);

$user_id = $_SESSION['user_id'];
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$cart = $_SESSION['cart'] ?? [];
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Lấy danh sách voucher đang hoạt động và còn hạn sử dụng
$stmtVouchers = $conn->prepare("SELECT * FROM vouchers WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE()) ORDER BY created_at DESC");
$stmtVouchers->execute();
$availableVouchers = $stmtVouchers->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - NovaStyle</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: var(--bg-light); padding-top: 100px; }
        .checkout-container { max-width: 1200px; margin: 0 auto; padding: 40px 5%; display: flex; gap: 40px; min-height: 70vh; }
        .checkout-form-section { flex: 1.5; background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: fit-content; }
        .section-title { font-family: var(--font-heading); font-size: 1.8rem; margin-bottom: 30px; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 20px; border: 1px solid var(--glass-border); border-radius: 10px; background: rgba(0,0,0,0.02); font-family: var(--font-body); color: var(--text-main); transition: 0.3s; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--accent-purple); background: white; box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.1); }
        .checkout-summary-section { flex: 1; background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: sticky; top: 100px; height: fit-content; }
        .summary-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .summary-item img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; }
        .summary-info { flex: 1; }
        .summary-info h4 { font-size: 0.95rem; margin-bottom: 5px; color: var(--text-main); }
        .summary-price { font-weight: 600; color: var(--accent-blue); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-muted); font-size: 0.95rem; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--glass-border); font-size: 1.2rem; font-weight: 800; color: var(--text-main); }
        .btn-confirm { width: 100%; padding: 15px; margin-top: 30px; font-size: 1.1rem; justify-content: center; }
        @media (max-width: 768px) { .checkout-container { flex-direction: column; } }

        /* Giao diện chọn phương thức thanh toán */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        .payment-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border: 2px solid var(--glass-border);
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .payment-card:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
            border-color: rgba(138, 43, 226, 0.3);
        }
        .payment-card.active {
            border-color: var(--accent-purple);
            background: rgba(138, 43, 226, 0.05);
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.1);
        }
        .payment-card-icon {
            font-size: 1.8rem;
            color: var(--text-muted);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.03);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .payment-card.active .payment-card-icon {
            color: var(--accent-purple);
            background: rgba(138, 43, 226, 0.1);
        }
        .payment-card-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .payment-card-title {
            font-weight: 600;
            color: var(--text-main);
            font-size: 1.05rem;
            margin-bottom: 3px;
        }
        .payment-card-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .payment-card-check {
            font-size: 1.3rem;
            color: var(--text-muted);
            opacity: 0.2;
            transition: all 0.3s ease;
        }
        .payment-card.active .payment-card-check {
            color: var(--accent-purple);
            opacity: 1;
        }
    </style>
</head>
<body>
    <nav class="glass-header" style="background: rgba(255,255,255,0.8);">
        <div class="logo"><a href="index.php" style="text-decoration: none; color: inherit;"><i class="fa-solid fa-microchip"></i> NovaStyle</a></div>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Trang Chủ</a>
            <a href="products.php" class="nav-item">Sản Phẩm</a>
            <a href="cart.php" class="nav-item">Giỏ Hàng</a>
        </div>
    </nav>

    <div class="checkout-container">
        <div class="checkout-form-section">
            <h2 class="section-title"><i class="fa-solid fa-map-location-dot" style="color: var(--accent-blue);"></i> Thông tin giao hàng</h2>
            <form id="checkoutForm">
                <div class="form-row">
                    <div class="form-group"><label>Họ và Tên</label><input type="text" id="shippingName" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                    <div class="form-group"><label>Số điện thoại</label><input type="tel" id="shippingPhone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="0[0-9]{9}" title="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0" required></div>
                </div>
                <div class="form-group" style="margin-bottom: 20px;"><label>Email liên hệ</label><input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: rgba(0,0,0,0.05);"></div>
                <div class="form-group"><label>Địa chỉ nhận hàng</label><textarea id="shippingAddress" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea></div>
                
                <h2 class="section-title" style="margin-top: 40px; margin-bottom: 20px;"><i class="fa-solid fa-credit-card" style="color: var(--accent-purple);"></i> Phương thức thanh toán</h2>
                <div class="payment-methods">
                    <label class="payment-card active">
                        <input type="radio" name="payment_method" value="cod" checked style="display:none;">
                        <div class="payment-card-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                        <div class="payment-card-info">
                            <span class="payment-card-title">Thanh toán khi nhận hàng (COD)</span>
                            <span class="payment-card-desc">Thanh toán bằng tiền mặt khi nhận hàng.</span>
                        </div>
                        <div class="payment-card-check"><i class="fa-solid fa-circle-check"></i></div>
                    </label>
                    <label class="payment-card">
                        <input type="radio" name="payment_method" value="online" style="display:none;">
                        <div class="payment-card-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="payment-card-info">
                            <span class="payment-card-title">Thanh toán Online</span>
                            <span class="payment-card-desc">Chuyển khoản nhanh VietQR hoặc Thẻ/Ví điện tử.</span>
                        </div>
                        <div class="payment-card-check"><i class="fa-solid fa-circle-check"></i></div>
                    </label>
                </div>
            </form>
        </div>

        <div class="checkout-summary-section">
            <h2 class="section-title" style="font-size: 1.5rem;"><i class="fa-solid fa-receipt" style="color: var(--accent-purple);"></i> Tóm tắt đơn hàng</h2>
            <div id="checkoutItems">
                <?php if (empty($cart)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">Giỏ hàng trống!</p>
                <?php else: ?>
                    <?php foreach ($cart as $id => $item): ?>
                        <div class="summary-item">
                            <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                            <div class="summary-info">
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Số lượng: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="summary-price"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> VNĐ</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Voucher Input Box -->
            <div style="margin-top: 20px; margin-bottom: 20px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); padding-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); font-size: 0.9rem;">Mã giảm giá (Voucher)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="voucherCodeInput" placeholder="Nhập mã" style="flex: 1; padding: 10px 15px; border: 1px solid var(--glass-border); border-radius: 10px; background: rgba(0,0,0,0.02); font-family: var(--font-body); color: var(--text-main); text-transform: uppercase;">
                    <button type="button" id="btnApplyVoucher" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.9rem;">Áp dụng</button>
                </div>
                <div id="voucherMessage" style="margin-top: 8px; font-size: 0.85rem; font-weight: 500;"></div>

                <!-- Quick Select List -->
                <?php if (!empty($availableVouchers)): ?>
                    <div style="margin-top: 15px;">
                        <a href="javascript:void(0);" id="toggleVoucherList" style="font-size: 0.85rem; color: var(--accent-purple); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-tags"></i> Chọn từ danh sách mã giảm giá <i class="fa-solid fa-chevron-down" id="voucherChevron"></i>
                        </a>
                        <div id="voucherListContainer" style="display: none; margin-top: 10px; flex-direction: column; gap: 10px; max-height: 200px; overflow-y: auto; padding-right: 5px;">
                            <?php foreach ($availableVouchers as $v): ?>
                                <?php 
                                $isEligible = $total_price >= $v['min_order_value'];
                                $desc = $v['discount_type'] === 'percentage' 
                                    ? 'Giảm ' . number_format($v['discount_value'], 0) . '% ' . ($v['max_discount'] ? '(Tối đa ' . number_format($v['max_discount'], 0, ',', '.') . 'đ)' : '')
                                    : 'Giảm ' . number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                $cond = 'Đơn tối thiểu ' . number_format($v['min_order_value'], 0, ',', '.') . 'đ';
                                ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--glass-border); border-radius: 10px; background: <?= $isEligible ? 'rgba(138, 43, 226, 0.03)' : 'rgba(0,0,0,0.02)' ?>; opacity: <?= $isEligible ? '1' : '0.7' ?>;">
                                    <div style="flex: 1; padding-right: 10px;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <code style="font-weight: 700; color: var(--accent-purple); font-size: 0.9rem; background: rgba(138,43,226,0.1); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($v['code']) ?></code>
                                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);"><?= $desc ?></span>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                            <?= $cond ?>
                                            <?php if ($v['expiry_date']): ?>
                                                • HSD: <?= date('d/m/Y', strtotime($v['expiry_date'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($isEligible): ?>
                                            <button type="button" class="btn select-voucher-btn" data-code="<?= htmlspecialchars($v['code']) ?>" style="padding: 6px 12px; font-size: 0.8rem; background: var(--accent-purple); color: white; border: none; border-radius: 6px; cursor: pointer;">Chọn</button>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: #dc3545; font-weight: 500; white-space: nowrap;">Thiếu <?= number_format($v['min_order_value'] - $total_price, 0, ',', '.') ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="summary-row"><span>Tạm tính</span><span id="summarySubtotal"><?= number_format($total_price, 0, ',', '.') ?> VNĐ</span></div>
            <div class="summary-row" id="discountRow" style="display: none; color: #28a745;"><span>Giảm giá (Voucher)</span><span id="summaryDiscount">-0 VNĐ</span></div>
            <div class="summary-row"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
            <div class="summary-total"><span>Tổng thanh toán</span><span class="gradient-text" id="summaryTotal"><?= number_format($total_price, 0, ',', '.') ?> VNĐ</span></div>
            <button id="confirmOrderBtn" class="btn btn-primary btn-confirm btn-glow" <?= empty($cart) ? 'disabled' : '' ?>>Xác Nhận Đặt Hàng</button>
        </div>
    </div>

    <script>
        // Xử lý chọn phương thức thanh toán
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Xử lý áp dụng Voucher
        let subtotal = <?= $total_price ?>;
        
        document.getElementById('btnApplyVoucher').addEventListener('click', function() {
            const voucherCode = document.getElementById('voucherCodeInput').value.trim();
            const msgDiv = document.getElementById('voucherMessage');
            
            msgDiv.innerText = '';
            msgDiv.style.color = '';
            
            fetch('apply_voucher.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ voucher_code: voucherCode })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.discount > 0) {
                        msgDiv.innerText = data.message;
                        msgDiv.style.color = '#2e7d32'; // Màu xanh thành công
                        
                        document.getElementById('discountRow').style.display = 'flex';
                        document.getElementById('summaryDiscount').innerText = '-' + formatVND(data.discount) + ' VNĐ';
                        document.getElementById('summaryTotal').innerText = formatVND(data.new_total) + ' VNĐ';
                    } else {
                        msgDiv.innerText = data.message;
                        msgDiv.style.color = 'var(--text-muted)';
                        
                        document.getElementById('discountRow').style.display = 'none';
                        document.getElementById('summaryTotal').innerText = formatVND(subtotal) + ' VNĐ';
                    }
                } else {
                    msgDiv.innerText = data.message;
                    msgDiv.style.color = '#c62828'; // Màu đỏ lỗi
                    
                    document.getElementById('discountRow').style.display = 'none';
                    document.getElementById('summaryTotal').innerText = formatVND(subtotal) + ' VNĐ';
                }
            })
            .catch(err => {
                console.error(err);
                msgDiv.innerText = 'Lỗi hệ thống khi áp dụng mã giảm giá!';
                msgDiv.style.color = '#c62828';
            });
        });
        
        function formatVND(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }

        // Tải danh sách voucher và chọn nhanh
        const toggleLink = document.getElementById('toggleVoucherList');
        if (toggleLink) {
            toggleLink.addEventListener('click', function() {
                const container = document.getElementById('voucherListContainer');
                const chevron = document.getElementById('voucherChevron');
                if (container.style.display === 'none') {
                    container.style.display = 'flex';
                    chevron.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    container.style.display = 'none';
                    chevron.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            });
        }

        document.querySelectorAll('.select-voucher-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                document.getElementById('voucherCodeInput').value = code;
                document.getElementById('btnApplyVoucher').click(); // Tự động kích hoạt nút áp dụng
            });
        });

        document.getElementById('confirmOrderBtn').addEventListener('click', () => {
            const name = document.getElementById('shippingName').value.trim();
            const phone = document.getElementById('shippingPhone').value.trim();
            const address = document.getElementById('shippingAddress').value.trim();

            if (!name || !phone || !address) {
                alert('Vui lòng điền đầy đủ thông tin giao hàng!');
                return;
            }

            const phoneRegex = /^0[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                alert('Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0!');
                return;
            }

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

            const btn = document.getElementById('confirmOrderBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
            btn.disabled = true;

            fetch('process_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    shipping_address: `Người nhận: ${name} | SĐT: ${phone} | Địa chỉ: ${address}`,
                    payment_method: paymentMethod
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Lỗi: ' + data.message);
                    btn.innerHTML = 'Xác Nhận Đặt Hàng';
                    btn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>
