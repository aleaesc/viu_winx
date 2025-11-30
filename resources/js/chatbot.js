(() => {
  const state = { open: false, messages: [] };
  const elToggle = document.createElement('div');
  elToggle.className = 'chatbot-toggle';
  elToggle.innerHTML = '💬';
  const elWidget = document.createElement('div');
  elWidget.className = 'chatbot-widget hidden';
  elWidget.innerHTML = `
    <div class="chatbot-header">
      <span>Virtual Assistant</span>
      <button id="cbClose" style="background:none;border:none;font-size:18px;cursor:pointer">✕</button>
    </div>
    <div class="chatbot-body" id="cbBody"></div>
    <div class="chatbot-input">
      <input id="cbInput" type="text" placeholder="Ask something..." />
      <button id="cbSend">➤</button>
    </div>
  `;
  document.body.appendChild(elToggle);
  document.body.appendChild(elWidget);

  function render() {
    const body = elWidget.querySelector('#cbBody');
    body.innerHTML = '';
    state.messages.forEach(m => {
      const row = document.createElement('div');
      row.className = `chatbot-message ${m.role}`;
      const bubble = document.createElement('div');
      bubble.className = 'bubble';
      bubble.textContent = m.content;
      row.appendChild(bubble);
      body.appendChild(row);
    });
    body.scrollTop = body.scrollHeight;
  }

  function addMessage(role, content) {
    state.messages.push({ role, content });
    render();
  }

  async function sendQuestion(text) {
    addMessage('user', text);
    addMessage('bot', '…');
    try {
      const token = localStorage.getItem('token');
      const res = await fetch('/api/chatbot/ask', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': 'Bearer ' + token } : {})
        },
        body: JSON.stringify({ question: text })
      });
      const data = await res.json();
      // replace last bot placeholder
      state.messages[state.messages.length - 1] = { role: 'bot', content: data.answer || 'No answer' };
      render();
    } catch (e) {
      state.messages[state.messages.length - 1] = { role: 'bot', content: 'Error contacting assistant.' };
      render();
    }
  }

  elToggle.addEventListener('click', () => {
    state.open = !state.open;
    elWidget.classList.toggle('hidden', !state.open);
    if (state.messages.length === 0) {
      addMessage('bot', 'Hello, Viu Fam! How can we help?');
    }
  });
  elWidget.querySelector('#cbClose').addEventListener('click', () => {
    state.open = false;
    elWidget.classList.add('hidden');
  });
  elWidget.querySelector('#cbSend').addEventListener('click', () => {
    const input = elWidget.querySelector('#cbInput');
    const text = (input.value || '').trim();
    if (!text) return;
    input.value = '';
    sendQuestion(text);
  });
  elWidget.querySelector('#cbInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      elWidget.querySelector('#cbSend').click();
    }
  });
})();
