class Copilot {
    constructor() {
        this.isOpen = false;
        this.messages = [];

        // Use unique key per user content
        const userId = (typeof USER !== 'undefined' && USER.id) ? USER.id : 'guest';
        this.STORAGE_KEY_MESSAGES = `copilot_messages_${userId}`;
        this.STORAGE_KEY_STATE = `copilot_state_${userId}`;

        this.init();
    }

    init() {
        this.loadState();
        this.renderUI();
        this.bindEvents();

        // Restore state
        if (this.isOpen) {
            document.querySelector('.copilot-window').classList.add('open');
        }
        this.scrollToBottom();

        // Welcome message if empty
        if (this.messages.length === 0) {
            this.addMessage('bot', 'สวัสดีครับ! ผมคือผู้ช่วย AI ของคุณ (Version Real AI) \nคุณสามารถบอกผมให้ "จองรถ" หรือ "ไปที่หน้าข่าวสาร" ได้เลยครับ');
        }
    }

    loadState() {
        const savedMsgs = localStorage.getItem(this.STORAGE_KEY_MESSAGES);
        this.messages = savedMsgs ? JSON.parse(savedMsgs) : [];

        const savedState = localStorage.getItem(this.STORAGE_KEY_STATE);
        this.isOpen = savedState === 'open';
    }

    saveState() {
        localStorage.setItem(this.STORAGE_KEY_MESSAGES, JSON.stringify(this.messages));
        localStorage.setItem(this.STORAGE_KEY_STATE, this.isOpen ? 'open' : 'closed');
    }

    renderUI() {
        const ui = `
            <style>
                .copilot-quick-actions {
                    display: flex;
                    gap: 8px;
                    padding: 10px 15px;
                    overflow-x: auto;
                    background: #f8fafc;
                    border-top: 1px solid #e2e8f0;
                    scrollbar-width: none;
                }
                .copilot-quick-actions::-webkit-scrollbar { display: none; }
                .copilot-quick-btn {
                    white-space: nowrap;
                    padding: 6px 12px;
                    background: #fff;
                    border: 1px solid #cbd5e1;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    color: #475569;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }
                .copilot-quick-btn:hover {
                    background: #eff6ff;
                    border-color: #3b82f6;
                    color: #1d4ed8;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(59,130,246,0.1);
                }
                .copilot-typing {
                    display: flex;
                    gap: 4px;
                    padding: 12px 16px;
                    background: #f1f5f9;
                    border-radius: 12px;
                    width: fit-content;
                    margin-bottom: 10px;
                    border-bottom-left-radius: 4px;
                }
                .copilot-dot {
                    width: 6px;
                    height: 6px;
                    background: #94a3b8;
                    border-radius: 50%;
                    animation: copilot-bounce 1.4s infinite ease-in-out both;
                }
                .copilot-dot:nth-child(1) { animation-delay: -0.32s; }
                .copilot-dot:nth-child(2) { animation-delay: -0.16s; }
                @keyframes copilot-bounce {
                    0%, 80%, 100% { transform: scale(0); }
                    40% { transform: scale(1); }
                }
                .message-anim { animation: msg-fade-up 0.3s ease-out forwards; }
                @keyframes msg-fade-up {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
            <div class="copilot-launcher" title="Open Copilot">
                <i class="ri-robot-2-line"></i>
            </div>
            <div class="copilot-window">
                <div class="copilot-header">
                    <div class="copilot-title">
                        <i class="ri-robot-2-line"></i>
                        AI Copilot
                    </div>
                    <button class="copilot-close"><i class="ri-close-line"></i></button>
                </div>
                <div class="copilot-messages" id="copilot-messages">
                    ${this.messages.map(m => `
                        <div class="message ${m.sender}">
                            ${this.formatMessage(m.text)}
                        </div>
                    `).join('')}
                </div>
                <div class="copilot-quick-actions">
                    <button class="copilot-quick-btn" data-query="มีประกาศหรือข่าวสารอะไรใหม่บ้าง?"><i class="ri-newspaper-line"></i> ข่าวสาร</button>
                    <button class="copilot-quick-btn" data-query="ขอข้อมูลส่วนตัวของฉันหน่อย"><i class="ri-user-line"></i> ข้อมูล</button>
                    <button class="copilot-quick-btn" data-query="ระเบียบการลาเป็นอย่างไร?"><i class="ri-file-list-3-line"></i> ระเบียบการลา</button>
                </div>
                <div class="copilot-input-area">
                    <input type="text" class="copilot-input" placeholder="พิมพ์คำสั่ง... (เช่น 'จองรถ')">
                    <button class="copilot-send"><i class="ri-send-plane-fill"></i></button>
                </div>
            </div>
        `;

        const container = document.createElement('div');
        container.id = 'copilot-root';
        container.innerHTML = ui;
        document.body.appendChild(container);

        this.messagesContainer = document.getElementById('copilot-messages');
    }

    bindEvents() {
        const launcher = document.querySelector('.copilot-launcher');
        const closeBtn = document.querySelector('.copilot-close');
        const sendBtn = document.querySelector('.copilot-send');
        const input = document.querySelector('.copilot-input');
        const windowEl = document.querySelector('.copilot-window');

        const toggle = () => {
            this.isOpen = !this.isOpen;
            windowEl.classList.toggle('open', this.isOpen);
            this.saveState();
            if (this.isOpen) {
                setTimeout(() => input.focus(), 100);
                this.scrollToBottom();
            }
        };

        launcher.addEventListener('click', toggle);
        closeBtn.addEventListener('click', toggle);

        const sendMessage = () => {
            const text = input.value.trim();
            if (!text) return;

            // User message
            this.addMessage('user', text);
            input.value = '';

            // Process command
            this.processCommand(text);
        };

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        const quickBtns = document.querySelectorAll('.copilot-quick-btn');
        quickBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const query = btn.getAttribute('data-query');
                this.addMessage('user', query);
                this.processCommand(query);
            });
        });
    }

    addMessage(sender, text) {
        this.messages.push({ sender, text, time: new Date().toISOString() });
        this.saveState();

        const div = document.createElement('div');
        div.className = `message ${sender} message-anim`;
        div.innerHTML = this.formatMessage(text);
        this.messagesContainer.appendChild(div);
        this.scrollToBottom();
    }

    scrollToBottom() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }

    formatMessage(text) {
        // Basic formatting - enable links and newlines
        return text.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="underline">$1</a>');
    }

    // --- LOGIC CORE (Real AI via API) ---
    processCommand(text) {
        // Hide quick actions on send
        const quickActions = document.querySelector('.copilot-quick-actions');
        if (quickActions) quickActions.style.display = 'none';

        // Simulate "Thinking" with new animation
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message-anim copilot-typing';
        typingDiv.innerHTML = '<div class="copilot-dot"></div><div class="copilot-dot"></div><div class="copilot-dot"></div>';
        this.messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();

        setTimeout(() => {
            this.executeLogic(text.toLowerCase(), typingDiv);
        }, 600 + Math.random() * 500);
    }

    async executeLogic(cmd, typingDiv) {
        try {
            // Get last 10 messages for context
            const history = this.messages.slice(-10).map(m => ({
                role: m.sender === 'user' ? 'user' : 'assistant',
                content: m.text
            }));

            // Get user info for AI context
            const userInfo = typeof USER !== 'undefined' ? {
                id: USER.id,
                name: USER.name,
                department: USER.department,
                permissions: USER.permissions
            } : null;

            const apiBase = (window.APP_BASE_PATH || '').replace(/\/+$/, '') + '/api/copilot.php';
            const response = await fetch(apiBase, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: cmd,
                    history: history,
                    user: userInfo
                })
            });

            if (!response.ok) {
                const text = await response.text();
                throw new Error(`Server Error: ${response.status} - ${text.substring(0, 100)}`);
            }

            const data = await response.json();
            let reply = data.reply || "ขออภัย ระบบมีปัญหา กรุณาลองใหม่";

            if (typingDiv) typingDiv.remove();

            // แสดงข้อความก่อน
            this.addMessage('bot', reply);

            // Show quick actions again
            const quickActions = document.querySelector('.copilot-quick-actions');
            if (quickActions) quickActions.style.display = 'flex';

            // ถ้ามี action ให้ navigate (action ถูกแยกมาจาก PHP แล้ว)
            if (data.action) {
                setTimeout(() => {
                    let fullUrl;
                    if (data.action.startsWith('http')) {
                        fullUrl = data.action;
                        window.open(fullUrl, '_blank');
                    } else {
                        // Safe Join: Trim trailing slash from base, leading slash from action
                        const base = (window.APP_BASE_PATH || '').replace(/\/+$/, '');
                        const action = data.action.replace(/^\/+/, '');
                        fullUrl = base + '/' + action;
                        window.open(fullUrl, '_blank');
                    }
                }, 1500);
            }

        } catch (error) {
            console.error(error);
            if (typingDiv) typingDiv.remove();
            this.addMessage('bot', "เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่");
            
            const quickActions = document.querySelector('.copilot-quick-actions');
            if (quickActions) quickActions.style.display = 'flex';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.copilot = new Copilot();
});
