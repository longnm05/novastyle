<?php
session_start();
header('Content-Type: application/json');

// Yêu cầu cấu hình chứa API Key
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: Tệp config.php không tồn tại!']);
    exit;
}

// Yêu cầu kết nối cơ sở dữ liệu
if (file_exists('db.php')) {
    require_once 'db.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: Tệp db.php không tồn tại!']);
    exit;
}

// Kiểm tra API Key đã được cấu hình chưa
if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
    echo json_encode([
        'success' => true, 
        'reply' => 'Hệ thống đang được cấu hình. (Hiện tại mã khóa API Gemini chưa được cấu hình trong config.php. Vui lòng thiết lập khóa của bạn để trải nghiệm tính năng trả lời thông minh từ Google AI!)'
    ]);
    exit;
}

// Lấy payload đầu vào từ JS
$input = json_decode(file_get_contents('php://input'), true);
$history = $input['history'] ?? [];

if (empty($history)) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch sử hội thoại!']);
    exit;
}

// Lấy danh sách sản phẩm thực tế từ cơ sở dữ liệu
$productText = "";
try {
    $stmt = $conn->query("SELECT p.name, p.price, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity > 0 ORDER BY p.created_at DESC LIMIT 50");
    $dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($dbProducts)) {
        $productText .= "- Danh sách các sản phẩm đang có sẵn tại cửa hàng và giá tiền tương ứng:\n";
        foreach ($dbProducts as $p) {
            $productText .= "  * {$p['name']} (Loại: {$p['cat_name']}): " . number_format($p['price'], 0, ',', '.') . " VNĐ\n";
        }
    }
} catch (PDOException $e) {
    // Bỏ qua lỗi và chạy tiếp nếu xảy ra lỗi truy vấn
}

// Hệ thống chỉ dẫn ngữ cảnh cho Gemini
$systemInstruction = "Bạn là trợ lý ảo hỗ trợ bán hàng thông minh của thương hiệu thời trang cao cấp NovaStyle. " .
                     "Hãy hỗ trợ giải đáp các thắc mắc của khách hàng dựa trên thông tin chính thức sau của cửa hàng:\n" .
                     "- Tên thương hiệu: NovaStyle (Thời trang nam nữ, giày dép, mũ nón, áo khoác & phụ kiện cao cấp).\n" .
                     "- Địa chỉ: 67 Đường Nguyễn Trãi, Hà Nội.\n" .
                     "- Hotline liên hệ: 1900 6868.\n" .
                     "- Email: support@novastyle.com.\n" .
                     "- Giờ mở cửa: Từ 8h00 đến 22h00 tất cả các ngày trong tuần.\n" .
                     "- Chính sách giao hàng: Miễn phí vận chuyển toàn quốc cho tất cả các đơn hàng. Thời gian giao hàng dự kiến từ 2 đến 4 ngày.\n" .
                     "- Chính sách đổi trả: Cho phép đổi trả miễn phí trong vòng 7 ngày kể từ khi nhận hàng nếu có lỗi từ nhà sản xuất (giữ nguyên tem mác, chưa qua sử dụng).\n" .
                     "- Các mã giảm giá/vouchers hiện tại đang hoạt động trên hệ thống:\n" .
                     "  1. WELCOME10: Giảm giá 10% cho tất cả sản phẩm. Điều kiện áp dụng cho đơn hàng tối thiểu từ 100.000 VNĐ, mức giảm tối đa là 50.000 VNĐ.\n" .
                     "  2. HELLO50: Giảm giá trực tiếp 50.000 VNĐ cho đơn hàng có giá trị tối thiểu từ 150.000 VNĐ.\n" .
                     (!empty($productText) ? $productText : "") .
                     "Yêu cầu cách trả lời:\n" .
                     "- Trả lời bằng tiếng Việt, xưng hô thân thiện là 'NovaStyle' và gọi khách hàng là 'bạn', 'quý khách' hoặc 'anh/chị'.\n" .
                     "- Trả lời ngắn gọn, cô đọng, đi thẳng vào vấn đề chính.\n" .
                     "- Không sử dụng các ký tự định dạng markdown quá phức tạp, giữ văn bản rõ ràng và dễ nhìn trên khung chat nhỏ di động.";

// Tạo payload gửi tới Gemini API
$payload = [
    'contents' => $history,
    'systemInstruction' => [
        'parts' => [
            ['text' => $systemInstruction]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 500,
        'temperature' => 0.7
    ]
];

// Cấu hình URL của API Gemini 2.5 Flash
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

// Gọi API qua cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
// Bỏ qua SSL verification nếu chạy localhost gặp lỗi chứng chỉ
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kết nối API: ' . $error
    ]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $reply = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode([
        'success' => true,
        'reply' => $reply
    ]);
} else {
    // Xử lý thông báo lỗi từ Gemini API
    $errMsg = $result['error']['message'] ?? 'Lỗi không xác định từ Gemini AI.';
    echo json_encode([
        'success' => false,
        'message' => $errMsg,
        'raw' => $result
    ]);
}
