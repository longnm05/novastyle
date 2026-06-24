document.addEventListener('DOMContentLoaded', () => {

    // 1. Hover effect for Product Cards (Glow follows cursor)
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const glow = card.querySelector('.card-glow');
            if (glow) {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                glow.style.left = `${x - 75}px`;
                glow.style.top = `${y - 75}px`;
            }
        });
    });

    // 2. Navigation Scroll Effect
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.glass-header');
        if (window.scrollY > 50) {
            nav.style.boxShadow = 'var(--glass-shadow)';
            nav.style.background = 'rgba(10, 10, 12, 0.7)';
        } else {
            nav.style.boxShadow = 'none';
            nav.style.background = 'var(--glass-bg)';
        }
    });

    // 3. Quick View Logic
    const quickViewOverlay = document.getElementById('quickViewOverlay');
    if (quickViewOverlay) {
        const closeQuickViewBtn = document.getElementById('closeQuickView');
        const qvImage = document.getElementById('qvImage');
        const qvTitle = document.getElementById('qvTitle');
        const qvCategory = document.getElementById('qvCategory');
        const qvPrice = document.getElementById('qvPrice');
        const qvAddToCart = document.getElementById('qvAddToCart');
        const qvQty = document.getElementById('qvQty');

        document.querySelectorAll('.quick-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const card = e.target.closest('.product-card');
                const imgUrl = card.querySelector('.card-image img').src;
                const title = card.querySelector('h3').textContent;
                const category = card.querySelector('.category').textContent;
                const price = card.querySelector('.price').textContent;
                const addToCartBtn = card.querySelector('.add-to-cart');

                qvImage.src = imgUrl;
                qvTitle.textContent = title;
                qvCategory.textContent = category;
                qvPrice.textContent = price;
                qvQty.value = 1;

                qvAddToCart.setAttribute('data-id', addToCartBtn.getAttribute('data-id'));
                quickViewOverlay.classList.add('active');
            });
        });

        closeQuickViewBtn.addEventListener('click', () => quickViewOverlay.classList.remove('active'));
        quickViewOverlay.addEventListener('click', (e) => {
            if (e.target === quickViewOverlay) quickViewOverlay.classList.remove('active');
        });

        qvAddToCart.addEventListener('click', () => {
            const id = qvAddToCart.getAttribute('data-id');
            const qty = parseInt(qvQty.value) || 1;
            addToCart(id, qty, qvAddToCart);
        });
    }

    // 4. AJAX Add to Cart Function
    function addToCart(productId, quantity, buttonElement) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update badge
                    const cartBadge = document.getElementById('cartBadge');
                    if (cartBadge) cartBadge.textContent = data.total_items;

                    // Success effect
                    const originalHTML = buttonElement.innerHTML;
                    buttonElement.innerHTML = '<i class="fa-solid fa-check"></i> Đã thêm';
                    buttonElement.style.background = '#00ff88';
                    buttonElement.style.color = '#000';

                    setTimeout(() => {
                        buttonElement.innerHTML = originalHTML;
                        buttonElement.style.background = '';
                        buttonElement.style.color = '';
                        if (quickViewOverlay) quickViewOverlay.classList.remove('active');
                        // Tùy chọn: Chuyển hướng sang trang giỏ hàng
                        // window.location.href = 'cart.php';
                    }, 1000);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm vào giỏ hàng');
            });
    }

    // 5.Bind Add to Cart buttons on product cards
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            addToCart(id, 1, btn);
        });
    });

    // 6. AI Chatbot Widget Integration
    const initAIChatbot = () => {
        // Tránh tạo trùng lặp nếu tệp script.js được tải lại
        if (document.getElementById('aiBotWidget')) return;

        // Tạo bong bóng chat nổi
        const chatWidget = document.createElement('div');
        chatWidget.className = 'ai-bot-widget';
        chatWidget.id = 'aiBotWidget';
        chatWidget.innerHTML = '<i class="fa-solid fa-comments"></i>';
        document.body.appendChild(chatWidget);

        // Tạo khung cửa sổ chat
        const chatWindow = document.createElement('div');
        chatWindow.className = 'ai-chat-window';
        chatWindow.id = 'aiChatWindow';
        chatWindow.innerHTML = `
            <div class="ai-chat-header">
                <div class="ai-chat-title">
                    <i class="fa-solid fa-robot"></i>
                    <span>Trợ lý NovaStyle</span>
                    <span class="ai-chat-status"></span>
                </div>
                <button class="ai-chat-close" id="closeChatBtn"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="ai-chat-messages" id="chatMessages">
                <div class="chat-bubble bot">Xin chào! Mình là trợ lý ảo của NovaStyle. Mình có thể giúp gì cho bạn hôm nay?</div>
            </div>
            <div class="ai-chat-suggestions">
                <button class="suggestion-chip" data-msg="Voucher khuyến mãi hiện tại là gì?">Voucher khuyến mãi</button>
                <button class="suggestion-chip" data-msg="Địa chỉ cửa hàng mình ở đâu?">Địa chỉ cửa hàng</button>
                <button class="suggestion-chip" data-msg="Chính sách đổi trả sản phẩm thế nào?">Chính sách đổi trả</button>
                <button class="suggestion-chip" data-msg="Thời gian giao hàng và phí vận chuyển ra sao?">Giao hàng & Phí ship</button>
            </div>
            <div class="ai-chat-input-container">
                <input type="text" id="chatInput" placeholder="Nhập tin nhắn của bạn...">
                <button class="ai-chat-send" id="sendChatBtn"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        `;
        document.body.appendChild(chatWindow);

        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendChatBtn = document.getElementById('sendChatBtn');
        const closeChatBtn = document.getElementById('closeChatBtn');

        // Khởi tạo lịch sử hội thoại cho Gemini API
        let chatHistory = [
            {
                role: 'model',
                parts: [{ text: "Xin chào! Mình là trợ lý ảo của NovaStyle. Mình có thể giúp gì cho bạn hôm nay?" }]
            }
        ];

        // Mở/đóng khung chat
        chatWidget.addEventListener('click', () => {
            chatWindow.classList.toggle('active');
            if (chatWindow.classList.contains('active')) {
                chatInput.focus();
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });

        closeChatBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            chatWindow.classList.remove('active');
        });

        // Hàm cuộn tin nhắn xuống dưới cùng
        const scrollToBottom = () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        };

        // Hàm chèn bong bóng chat mới
        const appendMessage = (text, sender) => {
            const bubble = document.createElement('div');
            bubble.className = `chat-bubble ${sender}`;
            // Thay thế các dòng mới thành thẻ br để dễ hiển thị
            bubble.innerHTML = text.replace(/\n/g, '<br>');
            chatMessages.appendChild(bubble);
            scrollToBottom();
        };

        // Gửi tin nhắn tới PHP endpoint
        const processUserMessage = (text) => {
            if (!text.trim()) return;

            // Hiển thị tin nhắn người dùng
            appendMessage(text, 'user');
            chatHistory.push({
                role: 'user',
                parts: [{ text: text }]
            });

            // Hiển thị biểu tượng đang nhập (typing indicator)
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'chat-bubble bot';
            typingIndicator.id = 'typingIndicator';
            typingIndicator.innerHTML = `
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
            chatMessages.appendChild(typingIndicator);
            scrollToBottom();

            // Gọi API máy chủ chat_bot.php
            fetch('chat_bot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ history: chatHistory })
            })
            .then(res => res.json())
            .then(data => {
                // Xóa biểu tượng đang nhập
                const loader = document.getElementById('typingIndicator');
                if (loader) loader.remove();

                if (data.success) {
                    // Hiển thị phản hồi từ AI
                    appendMessage(data.reply, 'bot');
                    chatHistory.push({
                        role: 'model',
                        parts: [{ text: data.reply }]
                    });
                } else {
                    appendMessage('Rất tiếc, đã xảy ra lỗi kết nối với máy chủ AI. Bạn vui lòng liên hệ hotline 1900 6868 để được hỗ trợ nhé!', 'bot');
                    console.error('Gemini API Error:', data.message);
                }
            })
            .catch(err => {
                const loader = document.getElementById('typingIndicator');
                if (loader) loader.remove();

                appendMessage('Không thể kết nối Internet. Vui lòng kiểm tra lại đường truyền mạng của bạn.', 'bot');
                console.error('Fetch Error:', err);
            });
        };

        // Bắt sự kiện click nút gửi
        sendChatBtn.addEventListener('click', () => {
            const text = chatInput.value;
            chatInput.value = '';
            processUserMessage(text);
        });

        // Bắt sự kiện nhấn Enter trong ô nhập
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const text = chatInput.value;
                chatInput.value = '';
                processUserMessage(text);
            }
        });

        // Bắt sự kiện click các câu hỏi gợi ý nhanh
        document.querySelectorAll('.suggestion-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const msg = chip.getAttribute('data-msg');
                processUserMessage(msg);
            });
        });
    };

    // Khởi tạo Chatbot
    initAIChatbot();

});
