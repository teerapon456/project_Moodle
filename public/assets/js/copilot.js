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
    }

    addMessage(sender, text) {
        this.messages.push({ sender, text, time: new Date().toISOString() });
        this.saveState();

        const div = document.createElement('div');
        div.className = `message ${sender}`;
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
        // Simulate "Thinking"
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot typing-indicator';
        typingDiv.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
        this.messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();

        setTimeout(() => {
            typingDiv.remove();
            this.executeLogic(text.toLowerCase());
        }, 600 + Math.random() * 500);
    }

    async executeLogic(cmd) {
        try {
            // Get last 10 messages for context
            const history = this.messages.slice(-10).map(m => ({
                role: m.sender === 'user' ? 'user' : 'assistant',
                content: m.text
            }));

            // Get user info for AI context
            const userInfo = typeof USER !== 'undefined' ? {
                name: USER.name,
                department: USER.department,
                permissions: USER.permissions
            } : null;

            const assetBase = window.ASSET_BASE || (window.APP_BASE_PATH + '/public/');
            const response = await fetch(assetBase + 'api/copilot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: cmd,
                    history: history,
                    user: userInfo
                })
            });

            const data = await response.json();
            let reply = data.reply || "ขออภัย ระบบมีปัญหา กรุณาลองใหม่";

            // แสดงข้อความก่อน
            this.addMessage('bot', reply);

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
            this.addMessage('bot', "เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่");
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.copilot = new Copilot();
});
