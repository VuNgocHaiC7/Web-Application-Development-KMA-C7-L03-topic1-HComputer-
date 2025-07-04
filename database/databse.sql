-- Tạo database
DROP DATABASE IF EXISTS H_Computer;
CREATE DATABASE H_Computer;
USE H_Computer;

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng quản trị viên
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Bảng danh mục sản phẩm
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Bảng sản phẩm
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    price DECIMAL(15,0),
    main_img VARCHAR(255),
    type VARCHAR(100),
    brand VARCHAR(100),
    rating INT,
    rating_count INT
);
CREATE TABLE product_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    image_path VARCHAR(255),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
CREATE TABLE product_description (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    description TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Bảng liên hệ
CREATE TABLE contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,  -- liên kết người dùng (có thể NULL nếu là khách)
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- Bảng giỏ hàng
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng vouchers
CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount DECIMAL(5, 2) NOT NULL, -- Tỷ lệ giảm giá (ví dụ: 0.10 cho 10%)
    is_active BOOLEAN DEFAULT TRUE
);

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    ward VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- bank_transfer hoặc cash_on_delivery
    total DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Processing, Shipped, Delivered, Cancelled
    voucher_code VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (voucher_code) REFERENCES vouchers(code) ON DELETE SET NULL
);

-- Bảng chi tiết đơn hàng
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

INSERT INTO vouchers (code, discount, is_active) VALUES
('GIAM10', 0.10, TRUE),
('GIAM20', 0.20, TRUE);

INSERT INTO admins (username, password)
VALUES ('admin', 'admin');

-- Thêm dữ liệu danh mục
INSERT INTO categories (name) VALUES
('Camera'),
('Điện thoại'),
('Máy tính');

-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP 360 Độ 3MP IMOU TA32CP-L', 470000, 'image/product/f1.jpg', 'Camera', 'IMOU', 4, 123);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(1, 'image/product/fd1/fd1.jpg'),
(1, 'image/product/fd1/fd2.jpg'),
(1, 'image/product/fd1/fd3.jpg'),
(1, 'image/product/fd1/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(1, 'Độ phân giải:3 MP (2304 x 1296);'),
(1, 'Góc nhìn: 360 độ;'),
(1, 'Góc xoay: Xoay ngang 355 độ. Xoay dọc -5 - 80 độ;'),
(1, 'Tầm nhìn xa hồng ngoại: 10 m trong tối;'),
(1, 'Tiện ích: Phát hiện chuyển động. Báo động âm thanh bất thường. Tích hợp còi thông báo. Phát hiện con người. Theo dõi chuyển động. Gửi cảnh báo đến điện thoại. Đàm thoại 2 chiều;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP Ngoài Trời 360 Độ 2MP TP-Link Tapo C500', 650000, 'image/product/f2.jpg', 'Camera', 'TP-LINK', 3, 87);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(2, 'image/product/fd2/fd1.jpg'),
(2, 'image/product/fd2/fd2.jpg'),
(2, 'image/product/fd2/fd3.jpg'),
(2, 'image/product/fd2/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(2, 'Độ phân giải:2 MP (1080p);'),
(2, 'Góc nhìn: 360 độ;'),
(2, 'Góc xoay: Xoay ngang 360 độ. Xoay dọc 130 độ;'),
(2, 'Tầm nhìn xa hồng ngoại: 30 m trong tối;'),
(2, 'Tiện ích:Chống nước, bụi IP65. Phát hiện chuyển động. Báo động âm thanh bất thường. Phát hiện hình dáng con người bằng AI. Phát hiện giả mạo camera. Chế độ tuần tra. Chọn khu vực riêng tư. Theo dõi chuyển động. Gửi cảnh báo đến điện thoại. Chế độ riêng tư. Đàm thoại 2 chiều;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop Asus Vivobook 15 OLED A1505ZA i5 12500H/16GB/512GB/120Hz/Win11', 16190000, 'image/product/f3.jpg', 'Máy tính', 'ASUS', 5, 210);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(3, 'image/product/fd3/fd1.jpg'),
(3, 'image/product/fd3/fd2.jpg'),
(3, 'image/product/fd3/fd3.jpg'),
(3, 'image/product/fd3/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(3, 'Công nghệ CPU: Intel Core i5 Alder Lake - 12500H;'),
(3, 'Số nhân:12;'),
(3, 'Số luồng: 16;'),
(3, 'Tốc độ CPU: 2.5GHz;'),
(3, 'Hệ điều hành: Windows 11;'),
(3, 'RAM: 16 GB;'),
(3, 'Loại RAM: DDR4 2 khe (8 GB onboard + 1 khe 8 GB);'),
(3, 'Độ phân giải: 2.8K (2880 x 1620) - OLED;'),
(3, 'Thông tin Pin: 3-cell, 50Wh;');



-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop Acer Aspire Go AG15 31P 30M4 i3 N305/8GB/256GB/Win11', 10090000, 'image/product/f4.jpg', 'Máy tính', 'ACER', 4, 164);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(4, 'image/product/fd4/fd1.jpg'),
(4, 'image/product/fd4/fd2.jpg'),
(4, 'image/product/fd4/fd3.jpg'),
(4, 'image/product/fd4/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(4, 'Công nghệ CPU:Intel Core i3 Alder Lake chuỗi N - N305;'),
(4, 'Số nhân: 8;'),
(4, 'Số luồng: 8;'),
(4, 'Tốc độ CPU: 1.8GHz;'),
(4, 'RAM:8 GB;'),
(4, 'Loại RAM:LPDDR5 (Onboard);'),
(4, 'Độ phân giải: Full HD (1920 x 1080);'),
(4, 'Thông tin Pin: 3-cell, 50Wh;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP Ngoài Trời 2MP Ezviz H3C', 700000, 'image/product/f5.jpg', 'Camera', 'EZVIZ', 5, 99);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(5, 'image/product/fd5/fd1.jpg'),
(5, 'image/product/fd5/fd2.jpg'),
(5, 'image/product/fd5/fd3.jpg'),
(5, 'image/product/fd5/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(5, 'Độ phân giải:2 MP (1080p);'),
(5, 'Góc xoay: Nhìn ngang 82 độ. Nhìn chéo 98 độ;'),
(5, 'Tầm nhìn xa hồng ngoại: 30 m trong tối (đen trắng), 15 m ban đêm (có màu);'),
(5, 'Tiện ích: Tích hợp micro. Chống nước, chống bụi IP67. Chế độ quan sát ban đêm có màu. Nhận dạng con người AI. Tích hợp Google Assistant và Amazon Alexa. Hình ảnh HD. Phòng vệ chủ động bằng đèn chớp. Công nghệ nén H265;');



-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại Samsung Galaxy Z Fold6 5G 12GB/256GB', 37990000, 'image/product/f6.jpg', 'Điện thoại', 'SAMSUNG', 5, 305);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(6, 'image/product/fd6/fd1.jpg'),
(6, 'image/product/fd6/fd2.jpg'),
(6, 'image/product/fd6/fd3.jpg'),
(6, 'image/product/fd6/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(6, 'Hệ điều hành: Android 14;'),
(6, 'Chip xử lý (CPU): Snapdragon 8 Gen 3 for Galaxy;'),
(6, 'Tốc độ CPU: 3.39 GHz;'),
(6, 'Chip đồ họa (GPU): Adreno 750;'),
(6, 'RAM: 12 GB;'),
(6, 'Dung lượng lưu trữ: 256 GB;'),
(6, 'Dung lượng pin: 4400 mAh;'),
(6, 'Độ phân giải camera sau: Chính 50 MP & Phụ 12 MP, 10 MP;'),
(6, 'Mạng di động: Hỗ trợ 5G;');



-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại HONOR X5b 4GB/64GB', 2140000, 'image/product/f7.jpg', 'Điện thoại', 'HONOR', 3, 42);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(7, 'image/product/fd7/fd1.jpg'),
(7, 'image/product/fd7/fd2.jpg'),
(7, 'image/product/fd7/fd3.jpg'),
(7, 'image/product/fd7/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(7, 'Hệ điều hành: Android 14;'),
(7, 'Chip xử lý (CPU): MediaTek Helio G36 8 nhân;'),
(7, 'Tốc độ CPU: 4 nhân 2.2 GHz & 4 nhân 1.6 GHz;'),
(7, 'Chip đồ họa (GPU): IMG PowerVR GE8320;'),
(7, 'RAM: 4 GB;'),
(7, 'Dung lượng lưu trữ: 64 GB;'),
(7, 'Dung lượng pin: 5200 mAh;'),
(7, 'Độ phân giải camera sau: Chính 13 MP & Ống kính phụ;'),
(7, 'Mạng di động: Hỗ trợ 4G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop MacBook Air 13 inch M2 16GB/256GB', 21190000, 'image/product/f8.jpg', 'Máy tính', 'MACBOOK', 5, 177);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(8, 'image/product/fd8/fd1.jpg'),
(8, 'image/product/fd8/fd2.jpg'),
(8, 'image/product/fd8/fd3.jpg'),
(8, 'image/product/fd8/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(8, 'Công nghệ CPU: Apple M2;'),
(8, 'Số nhân: 8;'),
(8, 'Tốc độ CPU: 100GB/s;'),
(8, 'RAM: 16 GB;'),
(8, 'Hỗ trợ RAM tối đa: Không hỗ trợ nâng cấp;'),
(8, 'Ổ cứng: 256 GB SSD;'),
(8, 'Độ phân giải: Liquid Retina (2560 x 1664);'),
(8, 'Card màn hình: Card tích hợp - 8 nhân GPU;'),
(8, 'Kích thước: Dài 304.1 mm - Rộng 215 mm - Dày 11.3 mm - 1.24 kg;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại iPhone 16 Pro Max 256GB', 30590000, 'image/new_product/n1.jpg', 'Điện thoại', 'IPHONE', 4, 150);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(9, 'image/new_product/nd1/nd1.jpg'),
(9, 'image/new_product/nd1/nd2.jpg'),
(9, 'image/new_product/nd1/nd3.jpg'),
(9, 'image/new_product/nd1/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(9, 'Hệ điều hành: iOS 18;'),
(9, 'Chip xử lý (CPU): Apple A18 Pro 6 nhân;'),
(9, 'Chip đồ họa (GPU): Apple GPU 6 nhân;'),
(9, 'RAM: 8 GB;'),
(9, 'Dung lượng lưu trữ: 256 GB;'),
(9, 'Độ phân giải camera sau: Chính 48 MP & Phụ 48 MP, 12 MP;'),
(9, 'Độ phân giải màn hình: Super Retina XDR (1320 x 2868 Pixels);'),
(9, 'Dung lượng pin: 33 giờ;'),
(9, 'Mạng di động: Hỗ trợ 5G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại Samsung Galaxy A36 5G 8GB/128GB', 8090000, 'image/new_product/n2.jpg', 'Điện thoại', 'SAMSUNG', 5, 86);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(10, 'image/new_product/nd2/nd1.jpg'),
(10, 'image/new_product/nd2/nd2.jpg'),
(10, 'image/new_product/nd2/nd3.jpg'),
(10, 'image/new_product/nd2/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(10, 'Hệ điều hành: Android 15;'),
(10, 'Chip xử lý (CPU): Snapdragon 6 Gen 3 8 nhân;'),
(10, 'Tốc độ CPU: 2.4 GHz;'),
(10, 'Chip đồ họa (GPU): Adreno 710;'),
(10, 'RAM: 8 GB;'),
(10, 'Độ phân giải camera sau: Chính 50 MP & Phụ 8 MP, 5 MP;'),
(10, 'Dung lượng pin: 5000 mAh;'),
(10, 'Dung lượng lưu trữ: 128 GB;'),
(10, 'Mạng di động: Hỗ trợ 5G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại OPPO Reno13 F 8GB/256GB', 8990000, 'image/new_product/n3.jpg', 'Điện thoại', 'OPPO', 3, 73);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(11, 'image/new_product/nd3/nd1.jpg'),
(11, 'image/new_product/nd3/nd2.jpg'),
(11, 'image/new_product/nd3/nd3.jpg'),
(11, 'image/new_product/nd3/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(11, 'Hệ điều hành: Android 15;'),
(11, 'Chip xử lý (CPU): MediaTek Helio G100 8 nhân;'),
(11, 'Tốc độ CPU: 2 nhân 2.2 GHz & 6 nhân 2.0 GHz;'),
(11, 'Chip đồ họa (GPU): Mali-G57 MC2;'),
(11, 'RAM: 8 GB;'),
(11, 'Dung lượng lưu trữ: 256 GB;'),
(11, 'Độ phân giải camera sau: Chính 50 MP & Phụ 8 MP, 2 MP;'),
(11, 'Dung lượng pin: 5800 mAh;'),
(11, 'Mạng di động: Hỗ trợ 4G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop MSI Gaming GF63 Thin 12VE i5 12450H/16GB/512GB/6GB RTX4050/144Hz/Win11', 19690000, 'image/new_product/n4.jpg', 'Máy tính', 'MSI', 5, 112);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(12, 'image/new_product/nd4/nd1.jpg'),
(12, 'image/new_product/nd4/nd2.jpg'),
(12, 'image/new_product/nd4/nd3.jpg'),
(12, 'image/new_product/nd4/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(12, 'Công nghệ CPU: Intel Core i5 Alder Lake - 12450H;'),
(12, 'Số nhân: 8;'),
(12, 'Số luồng: 12;'),
(12, 'Tốc độ CPU: 2GHz;'),
(12, 'Tốc độ tối đa: Turbo Boost 4.4 GHz;'),
(12, 'RAM: 16 GB;'),
(12, 'Ổ cứng: 512 GB SSD NVMe PCIe Gen 4.0. Hỗ trợ khe cắm HDD SATA 2.5 inch mở rộng;'),
(12, 'Độ phân giải: Full HD (1920 x 1080);'),
(12, 'Thông tin Pin: 3-cell Li-ion, 52.4 Wh;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP 360 Độ 8MP Ezviz C6C', 1290000, 'image/new_product/n5.jpg', 'Camera', 'EZVIZ', 3, 69);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(13, 'image/new_product/nd5/nd1.jpg'),
(13, 'image/new_product/nd5/nd2.jpg'),
(13, 'image/new_product/nd5/nd3.jpg'),
(13, 'image/new_product/nd5/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(13, 'Độ phân giải: 8 MP (2160p);'),
(13, 'Góc nhìn: 360 độ;'),
(13, 'Góc xoay: Góc xoay 340 độ. Nhìn ngang 106 độ. Nhìn dọc 90 độ. Nhìn chéo 48 độ;'),
(13, 'Tầm nhìn xa hồng ngoại: 10 m trong tối (đen trắng), 6 m ban đêm (có màu);'),
(13, 'Tiện ích: Phát hiện con người. Phát hiện vật nuôi. Chế độ riêng tư. Đàm thoại 2 chiều. Cuộc gọi 1 chạm. Cài đặt tối đa 4 khung hình theo dõi. Tích hợp Google Assistant và Amazon Alexa. Phát hiện tiếng ồn lớn. Theo dõi tự động thu phóng 2 màn hình. Hình ảnh 4K. Màu ban đêm thông minh. WiFi 6;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Máy tính bảng Xiaomi Redmi Pad Pro WiFi 8GB/128GB', 6990000, 'image/new_product/n6.jpg', 'Máy tính', 'XIAOMI', 5, 92);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(14, 'image/new_product/nd6/nd1.jpg'),
(14, 'image/new_product/nd6/nd2.jpg'),
(14, 'image/new_product/nd6/nd3.jpg'),
(14, 'image/new_product/nd6/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(14, 'Công nghệ màn hình: IPS LCD;'),
(14, 'Độ phân giải: 1600 x 2560 Pixels;'),
(14, 'Màn hình rộng: 12.1" - Tần số quét 120 Hz;'),
(14, 'Tốc độ CPU: 4 nhân 2.3 GHz & 4 nhân 1.95 GHz;'),
(14, 'Hệ điều hành: Xiaomi HyperOS (Android 14);'),
(14, 'Chip xử lý (CPU): Snapdragon 7s Gen 2 8 nhân;'),
(14, 'RAM: 8 GB;'),
(14, 'Dung lượng lưu trữ: 128 GB;'),
(14, 'Dung lượng pin: 10000 mAh;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP Ngoài Trời 360 Độ 3MP TIANDY TC-H333N', 600000, 'image/new_product/n7.jpg', 'Camera', 'TIANDY', 4, 55);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(15, 'image/new_product/nd7/nd1.jpg'),
(15, 'image/new_product/nd7/nd2.jpg'),
(15, 'image/new_product/nd7/nd3.jpg'),
(15, 'image/new_product/nd7/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(15, 'Độ phân giải: 3 MP (1296p);'),
(15, 'Góc nhìn: 360 độ;'),
(15, 'Góc xoay: Xoay dọc 90 độ. Nhìn ngang 360 độ. Nhìn dọc 43.1 độ. Nhìn chéo 94.2 độ. Xoay ngang 294 độ;'),
(15, 'Tầm nhìn xa hồng ngoại: 50 m trong tối (đen trắng), 15 m ban đêm (có màu);'),
(15, 'Tiện ích: Chống nước, bụi IP66. Phát hiện chuyển động. Báo động âm thanh bất thường. Đèn LED trợ sáng. Gửi thông báo đến điện thoại khi có động. Chọn vùng quan sát. Theo dõi chuyển động. Chế độ quan sát ban đêm có màu. Smart IR. Chế độ riêng tư. Hỗ trợ chống sét trực tiếp 6000V và chống sét lan truyền 2000V. Hỗ trợ chức năng giảm nhiễu số 3D-DNR. Hỗ trợ chức năng bù sáng BLC, HLC. Hỗ trợ chức năng chống ngược sáng Digital WDR. Đàm thoại 2 chiều;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop Acer Gaming Nitro 5 Tiger AN515 58 52SP i5 12500H/8GB/512GB/4GB RTX3050/144Hz/Win11', 18690000, 'image/new_product/n8.jpg', 'Máy tính', 'ACER', 5, 103);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(16, 'image/new_product/nd8/nd1.jpg'),
(16, 'image/new_product/nd8/nd2.jpg'),
(16, 'image/new_product/nd8/nd3.jpg'),
(16, 'image/new_product/nd8/nd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(16, 'Công nghệ CPU: Intel Core i5 Alder Lake - 12500H;'),
(16, 'Số nhân: 12;'),
(16, 'Số luồng: 16;'),
(16, 'Tốc độ CPU: 2.5GHz;'),
(16, 'RAM: 8 GB;'),
(16, 'Loại RAM: DDR4 2 khe (1 khe 8 GB + 1 khe rời);'),
(16, 'Ổ cứng: 512 GB SSD NVMe PCIe (Có thể tháo ra, lắp thanh khác tối đa 1 TB);'),
(16, 'Độ phân giải: Full HD (1920 x 1080);'),
(16, '<Thông tin Pin: 4-cell, 57.5Wh;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại Xiaomi 15 Ultra 5G 16GB/512GB', 32990000, 'image/product/f9.jpg', 'Điện thoại', 'XIAOMI', 5, 179);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(17, 'image/product/fd9/fd1.jpg'),
(17, 'image/product/fd9/fd2.jpg'),
(17, 'image/product/fd9/fd3.jpg'),
(17, 'image/product/fd9/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(17, 'Hệ điều hành: Xiaomi HyperOS 2;'),
(17, 'Chip xử lý (CPU): Qualcomm Snapdragon 8 Elite 8 nhân;'),
(17, 'Tốc độ CPU: 2 nhân 4.32 GHz & 6 nhân 3.53 GHz;'),
(17, 'Chip đồ họa (GPU): Adreno 830;'),
(17, 'RAM: 16 GB;'),
(17, 'Dung lượng lưu trữ: 512 GB;'),
(17, 'Độ phân giải camera sau: Chính 50 MP & Phụ 200 MP, 50 MP, 50 MP;'),
(17, 'Dung lượng pin: 5410 mAh;'),
(17, 'Mạng di động: Hỗ trợ 5G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Điện thoại Samsung Galaxy S24 FE 5G 8GB/128GB', 12890000, 'image/product/f10.jpg', 'Điện thoại', 'SAMSUNG', 3, 503);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(18, 'image/product/fd10/fd1.jpg'),
(18, 'image/product/fd10/fd2.jpg'),
(18, 'image/product/fd10/fd3.jpg'),
(18, 'image/product/fd10/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(18, 'Hệ điều hành: Android 14;'),
(18, 'Chip xử lý (CPU): Exynos 2400e 8 nhân;'),
(18, 'Tốc độ CPU: 3.1 GHz;'),
(18, 'Chip đồ họa (GPU): Xclipse 940;'),
(18, 'RAM: 8 GB;'),
(18, 'Dung lượng lưu trữ: 128 GB'),
(18, 'Độ phân giải camera sau: Chính 50 MP & Phụ 12 MP, 8 MP;'),
(18, 'Dung lượng pin: 4700 mAh;'),
(18, 'Mạng di động: Hỗ trợ 5G;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP 360 Độ 5MP IMOU Ranger Dual IPC-S2XP-10M0WED', 600000, 'image/product/f11.jpg', 'Camera', 'IMOU', 4, 426);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(19, 'image/product/fd11/fd1.jpg'),
(19, 'image/product/fd11/fd2.jpg'),
(19, 'image/product/fd11/fd3.jpg'),
(19, 'image/product/fd11/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(19, 'Độ phân giải: 5 MP (162jpg;'),
(19, 'Góc nhìn: 360 độ;'),
(19, 'Góc xoay: Xoay ngang 355 độ. Góc nhìn 87 độ (H), 47 độ (V), 105 độ (D). Xoay nghiêng 90 độ;'),
(19, 'Tầm nhìn xa hồng ngoại: 15 m trong tối;'),
(19, 'Tiện ích: Phát hiện chuyển động. Báo động âm thanh bất thường. Tích hợp còi thông báo. Phát hiện con người. Gửi thông báo đến điện thoại khi có động. Phát hiện vật nuôi. Theo dõi chuyển động. Chế độ quan sát ban đêm có màu. Đàm thoại 2 chiều. Hỗ trợ chuẩn ONVIF;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop MSI Modern 15 F13MG i5 1335U/16GB/512GB/Win11', 14190000, 'image/product/f12.jpg', 'Máy tính', 'MSI', 3, 234);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(20, 'image/product/fd12/fd1.jpg'),
(20, 'image/product/fd12/fd2.jpg'),
(20, 'image/product/fd12/fd3.jpg'),
(20, 'image/product/fd12/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(20, 'Công nghệ CPU: Intel Core i5 Raptor Lake - 1335U;'),
(20, 'Số nhân: 10;'),
(20, 'Số luồng: 12;'),
(20, 'Tốc độ tối đa: Turbo Boost 4.6 GHz;'),
(20, 'Tốc độ CPU: 1.3GHz;'),
(20, 'RAM: 16 GB;'),
(20, 'Độ phân giải: Full HD (1920 x 1080);'),
(20, 'Thông tin Pin: 3-cell, 45Wh;'),
(20, 'Hệ điều hành: Windows 11 Home SL;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera IP 360 Độ 5MP IMOU Rex 2D GK2DP', 950000, 'image/product/f13.jpg', 'Camera', 'IMOU', 3, 198);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(21, 'image/product/fd13/fd1.jpg'),
(21, 'image/product/fd13/fd2.jpg'),
(21, 'image/product/fd13/fd3.jpg'),
(21, 'image/product/fd13/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(21, 'Độ phân giải: 5 MP (1620p);'),
(21, 'Góc nhìn: 360 độ;'),
(21, 'Góc xoay: Xoay 0 - 355 độ. Nghiêng 0 - 90 độ. Góc nhìn 83 độ (H), 46 độ (V), 102 độ (D);'),
(21, 'Tầm nhìn xa hồng ngoại: 30 m trong tối;'),
(21, 'Tiện ích: Phát hiện chuyển động. Báo động âm thanh bất thường. Phát hiện con người. Gửi thông báo đến điện thoại khi có động. Khu vực có thể định cấu hình. Đàm thoại 2 chiều;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Camera Trông Trẻ 2MP EZVIZ BM1', 1490000, 'image/product/f14.jpg', 'Camera', 'EZVIZ', 4, 465);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(22, 'image/product/fd14/fd1.jpg'),
(22, 'image/product/fd14/fd2.jpg'),
(22, 'image/product/fd14/fd3.jpg'),
(22, 'image/product/fd14/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(22, 'Độ phân giải: 2 MP (1080p);'),
(22, 'Góc xoay: Xoay dọc 46 độ. Nhìn chéo 100 độ. Xoay ngang 85 độ;'),
(22, 'Tầm nhìn xa hồng ngoại: 5 m trong tối;'),
(22, 'Tiện ích: Phát hiện chuyển động. Điều khiển giọng nói qua trợ lý ảo. Phát hiện tiếng khóc. Phát hiện em bé trèo khỏi nôi (Em bé bỏ đi). Phát hiện hoạt động của em bé (Phát hiện người thông minh). Báo động thông minh. Tự động phát nhạc ru bé. Chọn vùng quan sát. Đàm thoại 2 chiều;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop Asus Gaming Vivobook K3605ZC i5 12500H/16GB/512GB/4GB RTX3050/Win11', 18690000, 'image/product/f15.jpg', 'Máy tính', 'ASUS', 5, 629);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(23, 'image/product/fd15/fd1.jpg'),
(23, 'image/product/fd15/fd2.jpg'),
(23, 'image/product/fd15/fd3.jpg'),
(23, 'image/product/fd15/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(23, 'Công nghệ CPU: Intel Core i5 Alder Lake - 12500H;'),
(23, 'Số nhân: 12;'),
(23, 'Số luồng: 16;'),
(23, 'Tốc độ CPU: 2.5GHz;'),
(23, 'RAM: 16 GB;'),
(23, 'Ổ cứng: 512 GB SSD NVMe PCIe;'),
(23, 'Độ phân giải: WUXGA;'),
(23, 'Thông tin Pin: 3-cell, 50Wh;'),
(23, 'Hệ điều hành: Windows 11 Home SL;');


-- Chèn vào bảng products
INSERT INTO products (name, price, main_img, type, brand, rating, rating_count)
VALUES ('Laptop Acer Gaming Nitro V ANV15 51 53NE i5 13420H/16GB/512GB/4GB RTX2050/144Hz/Win11', 18490000, 'image/product/f16.jpg', 'Máy tính', 'ACER', 4, 388);
-- ID của sản phẩm này sau khi thêm vào là 1
-- Chèn gallery
INSERT INTO product_gallery (product_id, image_path) VALUES
(24, 'image/product/fd16/fd1.jpg'),
(24, 'image/product/fd16/fd2.jpg'),
(24, 'image/product/fd16/fd3.jpg'),
(24, 'image/product/fd16/fd4.jpg');
-- Chèn description
INSERT INTO product_description (product_id, description) VALUES
(24, 'Công nghệ CPU: Intel Core i5 Raptor Lake - 13420H;'),
(24, 'Số nhân: 8;'),
(24, 'Số luồng: 12;'),
(24, 'Tốc độ CPU: 2.1GHz;'),
(24, 'RAM: 16 GB;'),
(24, 'Ổ cứng: 512 GB SSD NVMe PCIe (Có thể tháo ra, lắp thanh khác tối đa 2 TB);'),
(24, 'Độ phân giải: Full HD (1920 x 1080);'),
(24, 'Thông tin Pin: 4-cell, 57Wh;'),
(24, 'Hệ điều hành: Windows 11 Home SL;');