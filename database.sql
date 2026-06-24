-- ==========================================================
-- NovaStyle E-Commerce Database Schema
-- Nền tảng MySQL / MariaDB
-- ==========================================================

-- 1. Xóa Database cũ nếu tồn tại và tạo mới
DROP DATABASE IF EXISTS `novastyle_db`;
CREATE DATABASE `novastyle_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `novastyle_db`;

-- --------------------------------------------------------
-- 2. Cấu trúc bảng `users` (Quản lý khách hàng và AI Profiles)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `status` enum('active','locked') DEFAULT 'active',
  `ai_style_preference` json DEFAULT NULL COMMENT 'JSON lưu thói quen mua sắm cho AI phân tích',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Cấu trúc bảng `categories` (Danh mục sản phẩm)
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Cấu trúc bảng `products` (Kho hàng hóa)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` varchar(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `ai_tags` text COMMENT 'Các từ khóa để tính năng Smart Search quét',
  `stock_quantity` int(11) DEFAULT '100',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Cấu trúc bảng `orders` (Hóa đơn mua hàng)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `voucher_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Cấu trúc bảng `order_items` (Chi tiết giỏ hàng/Đơn hàng)
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Cấu trúc bảng `vouchers` (Mã khuyến mãi)
-- --------------------------------------------------------
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `vouchers` (`code`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `expiry_date`, `status`) VALUES
('WELCOME10', 'percentage', 10.00, 100000.00, 50000.00, '2026-12-31', 'active'),
('HELLO50', 'fixed', 50000.00, 150000.00, 50000.00, '2026-12-31', 'active');
-- ==========================================================
-- DỮ LIỆU MẪU (DUMP DATA) ĐỂ WEB CÓ CHỖ HIỂN THỊ
-- ==========================================================

INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Thời trang Nam', 'thoi-trang-nam', 'Quần áo nam phong cách hiện đại'),
(2, 'Thời trang Nữ', 'thoi-trang-nu', 'Quần áo nữ sành điệu'),
(3, 'Phụ kiện', 'phu-kien', 'Túi xách, kính mắt, trang sức'),
(4, 'Giày thể thao', 'giay-the-thao', 'Sneaker và giày chạy bộ'),
(5, 'Outwear', 'outwear', 'Áo khoác, Bomber, Jacket');

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `ai_tags`, `stock_quantity`) VALUES
('p1', 1, 'Áo Thun Basic Premium', 'Áo thun cotton cao cấp, form chuẩn', 250000.00, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab', 'áo thun, basic, nam, rẻ, mua', 150),
('p2', 3, 'Túi Xách Da Minimalist', 'Túi xách da thiết kế tối giản', 1200000.00, 'https://images.unsplash.com/photo-1584916201218-f4242ceb4809', 'túi xách, da, phụ kiện, minimalist', 50),
('p3', 4, 'Sneaker Phantom X', 'Giày thể thao công nghệ mới nhẹ bóng', 2500000.00, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d', 'giày, sneaker, thể thao, phantom', 120),
('p4', 5, 'Áo Khoác Bomber Cyber', 'Áo khoác phong cách đường phố tương lai', 850000.00, 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea', 'áo khoác, bomber, cyber, outwear, rẻ', 75);

INSERT INTO `users` (`username`, `full_name`, `email`, `password_hash`, `role`, `status`) VALUES
('nguyenvana', 'Nguyễn Văn A', 'nguyenvana@gmail.com', 'hashed_123456', 'customer', 'active'),
('admin', 'Quản Trị Viên', 'admin@gmail.com', 'hashed_123456', 'admin', 'active'),
('admin1', 'Quản Trị Viên 1', 'admin1@gmail.com', 'hashed_123456', 'admin', 'active');

-- Dữ liệu mẫu báo biểu (Đơn hàng)
INSERT INTO `orders` (`user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`) VALUES
(1, 2750000.00, 'delivered', 'Thủ đô Hà Nội, Vietnam', 'cod');

INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 'p1', 1, 250000.00),
(1, 'p3', 1, 2500000.00);

-- --------------------------------------------------------


-- Hoàn tất Script PostgreSQL/MySQL
