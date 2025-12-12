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
      let msg = 'No answer';
      if (res.ok) {
        const data = await res.json().catch(() => ({ answer: '...' }));
        // Prefer server answer when intent is not fallback/empty
        const intent = (data.intent || '').toLowerCase();
        const serverAnswer = data.answer || data.data?.answer || '';
        const needsLocal = !serverAnswer || intent === 'fallback' || intent === 'empty';
        msg = needsLocal ? localBot(text) : serverAnswer;
      } else {
        // Try to read error response
        try {
          const data = await res.json();
          msg = data.message || localBot(text) || 'Assistant error';
        } catch (_) {
          const text = await res.text();
          msg = text || localBot(text) || 'Assistant error';
        }
      }
      state.messages[state.messages.length - 1] = { role: 'bot', content: msg };
      render();
    } catch (e) {
      state.messages[state.messages.length - 1] = { role: 'bot', content: localBot(text) || 'Error contacting assistant.' };
      render();
    }
  }

  // Lightweight local responder extracted from prior working version
  function localBot(input) {
    const q = (input || '').toLowerCase().trim();
    const isTagalog = /\b(kamusta|kumusta|pano|paano|saan|kelan|magkano|presyo|salamat|ano|bakit|oo|opo|po|lang|naman)\b/i.test(q);

    // Greetings
    if (/\b(hi|hello|hey|kamusta|kumusta|musta|yo|sup|morning|afternoon|evening)\b/.test(q)) {
      return isTagalog
        ? 'Kamusta, Viu Fam! 👋 Tanong ka lang tungkol sa Viu o survey!'
        : "Hello, Viu Fam! 👋 Ask me anything about Viu or the survey!";
    }

    // Subscribe/Premium
    if (/(subscribe|subscription|premium|plan|mag\s?subscribe)/.test(q)) {
      return isTagalog
        ? 'Para mag-Premium: App → Premium → Piliin ang plan → Bayad. Walang ads, HD, at pwede download! ✨'
        : 'To get Premium: App → Premium → Pick a plan → Pay. Enjoy ad-free, HD, and downloads! ✨';
    }

    // Pricing
    if (/(price|pricing|cost|magkano|presyo|how much)/.test(q)) {
      return isTagalog
        ? 'Presyo depende sa bansa. Check mo sa Viu app ang latest. May monthly at yearly plans! 💰'
        : 'Pricing varies by region. Check the Viu app for current rates. Monthly and yearly plans available! 💰';
    }

    // Download
    if (/(download|offline|save)/.test(q)) {
      return isTagalog
        ? 'Para mag-download: Buksan ang episode → pindutin ang download icon. Premium ang best para dito. 📱'
        : 'To download: Open an episode → tap the download icon. Premium gives best quality. 📱';
    }

    // Cancel
    if (/(cancel|unsubscribe|stop)/.test(q)) {
      return isTagalog
        ? 'Cancel: Profile → Subscription → Cancel. Magagamit pa rin hanggang end ng billing period. 😊'
        : 'Cancel: Profile → Subscription → Cancel. You keep access until the end of the billing period. 😊';
    }

    // Devices
    if (/(device|devices|screens|how many)/.test(q)) {
      return isTagalog
        ? 'Pwede sa maraming devices. Log in lang sa pareho mong account. May limit sa sabay-sabay na streams. 📺'
        : 'Use multiple devices—just log in with the same account. Simultaneous streaming limits may apply. 📺';
    }

    // K-Drama
    if (/(kdrama|korean|k.?drama)/.test(q)) {
      return isTagalog
        ? 'Oo! Maraming K-dramas at variety shows! Tingnan ang K-Drama section sa app! 🇰🇷'
        : 'Absolutely! Tons of K-dramas and variety shows. Check the K-Drama section in the app! 🇰🇷';
    }

    // Genres
    if (/(genre|categories|type|content)/.test(q)) {
      return isTagalog
        ? 'May K-dramas, C-dramas, anime, movies, variety shows, at iba pa! 🎬'
        : 'We have K-dramas, C-dramas, anime, movies, variety shows, and more! 🎬';
    }

    // Account/password
    if (/(password|forgot|reset|login)/.test(q)) {
      return isTagalog
        ? 'Password: Settings → Security → Change. Nakalimutan? Gamitin ang “Forgot Password” sa login para sa email reset. 🔐'
        : "Password: Settings → Security → Change. Forgot it? Use 'Forgot Password' on login to reset via email. 🔐";
    }

    // Subtitles
    if (/(subtitle|subtitles|dub|language)/.test(q)) {
      return isTagalog
        ? 'Maraming subtitle languages! Habang nanonood: Settings icon → piliin ang language. 🗣️'
        : 'Multiple subtitle languages! While watching: Settings icon → choose language. 🗣️';
    }

    // Quality
    if (/(quality|hd|4k|resolution|buffer|blurry|pixel)/.test(q)) {
      return isTagalog
        ? 'Quality depende sa internet at plan. Premium may HD. Subukan baguhin ang quality sa settings. 📺'
        : 'Quality depends on internet and plan. Premium gets HD. Try adjusting quality in settings. 📺';
    }

    // Thanks
    if (/(thank|thanks|salamat)/.test(q)) {
      return isTagalog
        ? 'Walang anuman, Viu Fam! 😊'
        : "You're welcome, Viu Fam! 😊";
    }

    // Default
    return isTagalog
      ? 'Pwede kitang tulungan sa subscriptions, downloads, devices, content, at account settings. Anong gusto mong malaman? 🤔'
      : "I can help with subscriptions, downloads, devices, content, and account settings. What would you like to know? 🤔";
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
