# ViuBot Chat Integration & Run Guide

This document mirrors the original ValBot guide structure but substitutes domain data for Viu (regional streaming service + user experience survey). Architecture, flow, and formats remain unchanged; only factual/content sections and examples are updated to Viu context.

---

## Table of Contents

1. Overview
2. Architecture Summary
3. System Prompt (Canonical Version)
4. Language Detection Heuristic
5. Environment Configuration (.env)
6. Local Setup (Windows PowerShell)
7. Chat Endpoint Specification
8. Frontend Integration Flow
9. Example Requests (curl / Node / Tinker)
10. Prompt Variants & Samples
11. Optional Streaming Upgrade Design
12. Security & Rate Limiting Considerations
13. Improvement Ideas & Enhancements
14. Reusable Prompt Templates (Samples)
15. Minimal Integration Snippet
16. Automated Test Example (Feature Test)
17. Operational Checklist for Production
18. Appendix: Token/History Management Improvement

---

## 1. Overview

ViuBot is a lightweight assistant for the Viu User Experience & Satisfaction Survey. It helps users understand the 1–5 rating scale, locate features (downloads, subtitles, categories), differentiate Free vs Premium, and discover content genres (K-drama, anime, variety, Viu Originals). The backend selects the first available AI provider in priority order: OpenAI → Google Gemini → xAI Grok → stub fallback.

---

## 2. Architecture Summary

**Backend**

-   Endpoint: `POST /api/chat` defined in `routes/api.php` with throttle `20,1` (20 requests/minute/IP).
-   Controller: `App/Http/Controllers/Api/ChatController.php` orchestrates provider cascade.
-   Providers use environment keys in `config/services.php` for OpenAI, Gemini, xAI.
-   Sanitization removes Markdown and normalizes whitespace.

**Frontend**

-   `public/endUser.html` (or equivalent) includes a Floating Action Button (FAB) opening a chat modal.
-   Maintains `chatHistory` (last ≤10 messages) and posts JSON to `/api/chat`.
-   Displays typing indicator, then AI reply.

---

## 3. System Prompt (Canonical Version)

Plain text only. You are ViuBot — a warm, upbeat, and helpful assistant for the Viu User Experience & Satisfaction Survey. Sound like a friendly streaming platform support staff member who explains things simply.
Audience: Mixed ages including casual viewers. Use simple words, short sentences, and very clear steps.
Adaptive language:

-   Default to English. If the user writes mainly in Tagalog (Filipino), switch to Tagalog.
-   Keep responses concise (under 120 words). Use short lists when helpful.
    Primary language for this reply: {{PRIMARY_LANGUAGE}}.
    Formatting rules:
-   Plain text only (no Markdown, no **bold**, no code fences).
-   Preserve line breaks. Separate logical sections with one blank line.
-   Use bullets starting with "- " for lists.
    Structure: Brief answer (1–2 lines) + optional bullet steps + closing tip if needed.
    Goals:
-   Help users finish the survey (rate aspects 1 = Very Dissatisfied … 5 = Very Satisfied).
-   Clarify Free vs Premium benefits.
-   Guide on finding shows, using subtitles, downloads, and search.
    Unknown info (e.g. region-specific promo timing): say you are not sure; suggest checking official Viu app or website.
    Safety:
-   Avoid medical, legal, financial advice.
-   Do not request personal data beyond optional survey fields.
    Trusted Viu Facts (quote exactly):
    Free Tier: Ads, SD/limited HD, limited early access.
    Premium Tier: No ads, HD up to 1080p (select 4K where available), offline downloads, faster episode availability.
    Subtitle Languages (vary by region): English, Tagalog (PH), Indonesian, Thai, Arabic (MENA), Chinese (Traditional/Simplified) for select content.
    Popular Content Examples: True Beauty, Vincenzo, Hometown Cha-Cha-Cha, Running Man, Anime selections, Viu Originals (e.g. KILLER CAMPUS, Still 2gether).
    Core Genres: K-drama, C-drama, Thai series, Anime, Variety, Viu Originals.
    Availability (Representative Markets): Hong Kong, Singapore, Malaysia, Indonesia, Thailand, Philippines, Myanmar, UAE, Saudi Arabia, Kuwait, Qatar, Bahrain, Oman, Jordan, Egypt.
    Sample PH Pricing (subject to change): Premium ≈ PHP149/month.
    If asked about benefits: list them clearly and suggest trying a free episode first.
    Keep tone helpful and practical.

---

## 4. Language Detection Heuristic

Current heuristic: regex match for common Tagalog tokens in latest user message; if match → set primary language Tagalog.

Improved heuristic (optional):

```php
$tagalogTokens = '(ang|ng|sa|ako|ikaw|kayo|po|opo|hindi|oo|salamat|paano|saan|kailan|magkano|gusto|nasaan|bakit|kailangan|meron|wala|ito|iyan|doon|naman|ayos|pasensya|tulong|kung|habang|dahil|para|lahat|mga|niya|nila|natin|namin)';
$tagalogHits = preg_match_all("/\\b$tagalogTokens\\b/iu", $userMessage, $m);
$wordCount = str_word_count($userMessage);
$preferTagalog = $wordCount > 0 && ($tagalogHits / max($wordCount,1)) >= 0.05;
```

Optionally examine last 2 user turns and compute weighted ratio.

---

## 5. Environment Configuration (.env)

Set at least one provider key. The first non-empty key used in order: OpenAI → Gemini → xAI.

```
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1   # optional override
OPENAI_MODEL=gpt-4o-mini

GEMINI_API_KEY=...
GEMINI_MODEL=gemini-1.5-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta

XAI_API_KEY=...
XAI_MODEL=grok-beta
XAI_BASE_URL=https://api.x.ai/v1
```

SQLite quick start:

```
DB_CONNECTION=sqlite
```

Run `php artisan config:clear` after .env changes if needed.

---

## 6. Local Setup (Windows PowerShell)

Prerequisites: PHP ≥ 8.2, Composer, Node ≥ 18.

```powershell
cd "c:\Users\Asus\Downloads\val_survey-main"
composer install
Copy-Item .env.example .env -Force
php artisan key:generate
New-Item -ItemType File -Path .\database\database.sqlite -Force | Out-Null
# Edit .env: set DB_CONNECTION=sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

Survey page: `http://127.0.0.1:8000/endUser.html`  
Admin: `http://127.0.0.1:8000/adminfrontend.html`

Seed a user (optional):

```powershell
php artisan tinker
# In tinker:
# App\Models\User::create(['name'=>'admin','email'=>'admin@example.com','password'=>bcrypt('secret123')]);
exit
```

---

## 7. Chat Endpoint Specification

Route: `POST /api/chat` (public + throttle).  
Validation:

-   `message`: required string (1–2000)
-   `history`: optional array
-   Each history element: `{ role: system|user|assistant, content: string ≤ 4000 }`
    Response success example:

```json
{
    "provider": "openai",
    "reply": "Concise plain text guidance here..."
}
```

Errors trigger bilingual fallback message.

---

## 8. Frontend Integration Flow

1. User opens ViuBot modal via FAB.
2. Input submitted: pushes `{role:'user', content:msg}` into `chatHistory`.
3. Sends last ≤10 history items + new `message`.
4. Displays typing indicator.
5. Receives JSON reply, sanitizes, appends.
6. On error: shows bilingual fallback.

Potential improvement: token-aware truncation vs fixed length.

---

## 9. Example Requests

**curl (PowerShell escaping)**

```powershell
curl -X POST "http://127.0.0.1:8000/api/chat" `
  -H "Content-Type: application/json" `
  -d '{"message":"How do I download episodes?"}'
```

**Node**

```powershell
node -e "fetch('http://127.0.0.1:8000/api/chat',{
  method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
  body:JSON.stringify({message:'Explain Premium benefits'})
}).then(r=>r.json()).then(console.log)"
```

**Tinker**

```powershell
php artisan tinker
Http::post('http://127.0.0.1:8000/api/chat', ['message'=>'Subtitle languages?'])->json();
exit
```

---

## 10. Prompt Variants & Samples

**Bilingual Variant:** If mixed language, return English paragraph then Tagalog translation.
**Accessibility Variant:** Each sentence ≤14 words; ensure high readability.

Sample (Rating Explanation, English):

```
Very Dissatisfied means experience far below expectations.
- Slow streaming or frequent buffering
- Hard to find shows
- You would not recommend it
Tip: Use only for very poor experience.
```

Tagalog version:

```
Lubos na Hindi Nasiyahan: malayo sa inaasahan ang karanasan.
- Mabagal o madalas mag-buffer
- Mahirap hanapin ang palabas
- Hindi mo irerekomenda
Tip: Piliin lang kung talagang napakababa ng karanasan.
```

Sample (Premium Benefits, English):

```
Premium gives higher quality and no ads.
- HD up to 1080p
- Offline downloads
- Faster episode access
Tip: Try free tier then upgrade if you binge often.
```

Tagalog:

```
Premium: mas mataas na quality at walang ads.
- HD hanggang 1080p
- Offline downloads
- Mas mabilis na episode access
Tip: Subukan muna ang free bago mag-upgrade.
```

---

## 11. Optional Streaming Upgrade Design

Add route:

```php
Route::post('/chat/stream', [ChatController::class,'stream'])->middleware('throttle:20,1');
```

Controller snippet:

```php
return response()->stream(function() use($messages,$openKey,$url){
  $resp = Http::withHeaders([...])->post($url,[
    'model'=>'gpt-4o-mini','messages'=>$messages,'stream'=>true,'temperature'=>0.4,'max_tokens'=>512,
  ]);
  foreach ($resp->stream() as $chunk) {
    if (isset($chunk['choices'][0]['delta']['content'])) {
      echo 'data: '.json_encode($chunk['choices'][0]['delta']['content'])."\n\n"; flush();
    }
  }
  echo "data: [DONE]\n\n"; flush();
},200,['Content-Type'=>'text/event-stream']);
```

Frontend SSE example:

```javascript
const es = new EventSource("/api/chat/stream");
es.onmessage = (e) => {
    if (e.data === "[DONE]") es.close();
    else appendToken(e.data);
};
```

---

## 12. Security & Rate Limiting Considerations

-   Current throttle mitigates abuse; tune to traffic.
-   Avoid logging full user messages if PII risk; only upstream errors.
-   Add profanity or injection filter before sending to provider if necessary.
-   Enforce plain text to reduce XSS surface (already sanitized on frontend).

---

## 13. Improvement Ideas & Enhancements

-   Token-based history trimming.
-   Cache common answers (Premium benefits, subtitle languages, pricing example).
-   Sentiment detection to add empathy lines for negative feedback.
-   Admin toggle to enable/disable chat.
-   Streaming partial responses for faster perceived latency.

---

## 14. Reusable Prompt Templates (Samples)

**Survey Guidance (English):**

```
Rate video quality from 1 to 5.
- 5: Very clear and smooth
- 4: Mostly clear with minor issues
- 3: Acceptable but noticeable drops
- 2: Frequent blur or buffering
- 1: Poor and frustrating
Tip: Choose what matches your overall viewing.
```

**Subtitle Languages (Tagalog):**

```
Available subtitles depende sa region.
- English at Tagalog sa PH
- Indonesian at Thai sa kani-kaniyang bansa
- Arabic sa Middle East
Tip: I-check ang subtitle menu kung kulang ang wika.
```

---

## 15. Minimal Integration Snippet

```html
<script>
    async function askViuBot(q) {
        const res = await fetch("/api/chat", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ message: q, history: [] }),
        });
        const json = await res.json();
        console.log(json.reply);
    }
    askViuBot("How do I start the survey?");
</script>
```

---

## 16. Automated Test Example

Create `tests/Feature/ChatTest.php`:

```php
<?php
it('returns stub reply if no provider keys', function () {
    config(['services.openai.api_key'=>null,'services.gemini.api_key'=>null,'services.xai.api_key'=>null]);
    $resp = $this->postJson('/api/chat', ['message'=>'Hello']);
    $resp->assertOk()->assertJsonStructure(['provider','reply']);
    expect($resp->json('provider'))->toBe('stub');
});
```

Run:

```powershell
php artisan test --filter=ChatTest
```

---

## 17. Operational Checklist for Production

-   [ ] Configure at least one provider key.
-   [ ] Confirm `APP_URL` and HTTPS certificate.
-   [ ] Review logs for error volume; add retry/backoff if needed.
-   [ ] Add monitoring (uptime + latency).
-   [ ] Consider auth if restricting usage.
-   [ ] Validate environment for privacy compliance.

---

## 18. Appendix: Token/History Management Improvement

Basic estimation: 1 token ≈ 4 chars. To cap at ~1,500 tokens total:

```php
function trimHistoryByTokens(array $history, int $maxTokens = 1500): array {
  $est = 0; $out = [];
  foreach (array_reverse($history) as $item) {
    $tokens = ceil(strlen($item['content'])/4);
    if ($est + $tokens > $maxTokens) break;
    $est += $tokens; $out[] = $item;
  }
  return array_reverse($out);
}
```

Use in place of fixed `take(10)`.

---

## Quick Reference Commands

```powershell
# Initial setup
composer install; Copy-Item .env.example .env -Force; php artisan key:generate
php artisan migrate
npm install; npm run build
php artisan serve

# Test chatbot
curl -X POST "http://127.0.0.1:8000/api/chat" -H "Content-Type: application/json" -d '{"message":"Explain Premium benefits"}'
```

---

## Next Suggested Enhancements

1. Implement streaming route for faster UX.
2. Add automated tests for each provider branch & fallback.
3. Introduce simple sentiment-based closing tip.
4. Persist minimal anonymous interaction metrics (count only) for monitoring.
