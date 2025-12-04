<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIU Client Satisfaction Survey</title>
    
    <!-- Tailwind CSS & DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Removed Vite in dev to avoid manifest error; using public assets -->

    <!-- Shared Toast Assets -->
    <link rel="stylesheet" href="/toast.css">
    <!-- Flag Icons for country flags -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/css/flag-icons.min.css">

    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background-color: #FAFAFA;
        }

        /* VIU Brand Colors */
        .bg-viu-yellow { background-color: #F6BE00; }
        .text-viu-yellow { color: #F6BE00; }
        .border-viu-yellow { border-color: #F6BE00; }
        .fill-viu-yellow { fill: #F6BE00; }
        .stroke-viu-yellow { stroke: #F6BE00; }
        .hover-bg-viu-dark:hover { background-color: #dca000; }
         .bg-viu-dark-gray { background-color: #4B4B4B; }
        .hover-bg-viu-gray-hover:hover { background-color: #333333; }

        /* Utility */
        .hidden-page { display: none !important; }
        .fade-out { opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.5s ease; }
        .fade-in { animation: fadeIn 0.8s ease-in forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .glow-bg { background: radial-gradient(circle, rgba(246,190,0,0.15) 0%, rgba(255,255,255,0) 70%); }

        /* Survey Styles */
        #splash-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;
            background-color: #F6BE00; transition: opacity 0.5s ease-in-out, visibility 0.5s;
        }

        .input-minimal {
            background: transparent; border: none; border-bottom: 2px solid #D1D5DB;
            border-radius: 0; padding: 0; font-size: 1.125rem; color: #374151; transition: border-color 0.3s ease;
        }
        .input-minimal:focus { outline: none; border-bottom-color: #F6BE00; box-shadow: none; }
        .label-minimal { text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; color: #9CA3AF; font-weight: 600; margin-bottom: 0.25rem; }

        .progress-container { width: 150px; height: 6px; background-color: #E5E7EB; border-radius: 999px; overflow: hidden; }
        .progress-fill { height: 100%; background-color: #F6BE00; width: 0%; transition: width 0.5s ease-in-out; }

        .star-icon { width: 3.5rem; height: 3.5rem; color: #E5E7EB; fill: #E5E7EB; transition: all 0.2s ease; cursor: pointer; }
        .star-icon.active { color: #F6BE00; fill: #F6BE00; }
        
        .big-number { font-size: 8rem; line-height: 1; font-weight: 900; letter-spacing: -0.05em; }
        @media (max-width: 768px) { .big-number { font-size: 5rem; margin-bottom: 1rem; } }

        .checkbox-viu { width: 1.5rem; height: 1.5rem; border-radius: 0.3rem; border: 2px solid #9CA3AF; }
        .checkbox-viu:checked { background-color: #F6BE00; border-color: #F6BE00; }
        
        .genre-pill { border: 1px solid #E5E7EB; background-color: white; color: #4B5563; padding: 0.75rem 1.5rem; border-radius: 9999px; cursor: pointer; transition: all 0.2s ease; font-weight: 500; user-select: none; }
        .genre-pill:hover { border-color: #F6BE00; color: black; transform: translateY(-2px); }
        .genre-pill.selected { background-color: #FFF8DC; border-color: #F6BE00; color: black; font-weight: 600; }

        .btn-viu-continue { background-color: #D1D5DB; color: black; border: none; transition: all 0.3s ease; }
        .btn-viu-continue:hover { background-color: #F6BE00; transform: scale(1.02); }
        /* Question flow: locked state until a star is selected */
        .btn-viu-continue.is-disabled { background-color: #E5E7EB !important; color: #9CA3AF !important; cursor: not-allowed; }
        .btn-viu-continue.is-disabled:hover { background-color: #E5E7EB !important; transform: none !important; }

        /* Chatbot Styles (Centered Modal) */
        .chat-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000;
            display: flex; align-items: center; justify-content: center;
            background-color: rgba(17, 24, 39, 0.45);
            opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
        }
        .chat-overlay.visible { opacity: 1; pointer-events: auto; }

        #chat-window {
            width: 92%; max-width: 560px; height: 560px;
            background: #ffffff; border-radius: 18px; overflow: hidden;
            box-shadow: 0 40px 80px rgba(0,0,0,0.25);
            display: flex; flex-direction: column;
            transform: translateY(8px) scale(0.98); transition: transform 0.2s ease;
        }
        .chat-overlay.visible #chat-window { transform: translateY(0) scale(1); }
        
        .chat-header { 
            background-color: #F6BE00; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; color: black;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        
        .chat-body { flex: 1; padding: 18px; background-color: #FAFAFA; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; }
        
        .chat-input-area { background: white; padding: 14px 16px; border-top: 1px solid #E5E7EB; display: flex; align-items: center; gap: 12px; }
        .chat-input { flex-grow: 1; background: #F3F4F6; border-radius: 14px; padding: 14px 16px; font-size: 1rem; outline: none; color: #374151; box-shadow: inset 0 1px 0 rgba(255,255,255,0.7); }
        
        .chat-row { display: flex; align-items: flex-start; gap: 12px; }
        .chat-row.bot { justify-content: flex-start; }
        .chat-row.user { justify-content: flex-end; }
        .chat-bubble-bot { 
            display: inline-flex; align-items: center; gap: 10px; max-width: 85%;
            background: #F3F4F6; color: #111827; padding: 12px 16px; border-radius: 18px;
            font-size: 1rem; font-weight: 600; box-shadow: 0 8px 18px rgba(0,0,0,0.10);
        }
        .chat-bubble-user {
            position: relative; max-width: 85%; background: #FFFDF2; color: #111827; padding: 12px 16px; border-radius: 18px; font-weight: 600; border: 1px solid #FCE9A3; box-shadow: 0 6px 14px rgba(246,190,0,0.12), 0 3px 8px rgba(0,0,0,0.05);
        }
        .chat-avatar { width: 36px; height: 36px; border-radius: 50%; background: #fff; box-shadow: 0 6px 14px rgba(0,0,0,0.12); }
        /* Typing indicator */
        .typing {
            display: inline-flex; align-items: center; gap: 6px;
        }
        .typing .dot {
            width: 6px; height: 6px; background: #9CA3AF; border-radius: 999px;
            animation: bounce 1s infinite ease-in-out;
        }
        .typing .dot:nth-child(2){ animation-delay: .15s; }
        .typing .dot:nth-child(3){ animation-delay: .3s; }
        @keyframes bounce { 0%,80%,100%{ transform: translateY(0); opacity:.6 } 40%{ transform: translateY(-4px); opacity:1 } }
        
        /* Quick action buttons */
        .quick-action-buttons {
            display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;
        }
        .quick-action-btn {
            background: #fff; border: 1px solid #F6BE00; color: #000; padding: 6px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
        }
        .quick-action-btn:hover {
            background: #F6BE00; color: #000; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(246,190,0,0.3);
        }
        
        .bot-message-text {
            line-height: 1.6;
            font-weight: normal;
        }
        
        .bot-message-text strong,
        .bot-message-text b {
            font-weight: normal;
        }
        
        /* Suggested question chips */
        .suggested-chip {
            background: white; 
            border: 1.5px solid #F6BE00; 
            color: #111827; 
            padding: 8px 14px; 
            border-radius: 16px; 
            font-size: 0.8rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .suggested-chip:hover {
            background: #F6BE00; 
            color: black; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(246,190,0,0.3);
        }
        
        /* Flag icon alignment */
        .flag-ico { width: 20px; height: 15px; border-radius: 3px; box-shadow: 0 0 0 1px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen relative">
        <!-- Toast Stack (Bottom-right) -->
        <div id="toast-stack" class="fixed right-6 bottom-6 z-[1000] flex flex-col gap-3"></div>

    <!-- SPLASH -->
    <div id="splash-screen"></div>

    <!-- PAGE: WELCOME -->
    <div id="welcome-page" class="w-full min-h-screen flex flex-col relative opacity-0 transition-opacity duration-1000 bg-white">
        <div class="absolute top-6 left-6 md:top-10 md:left-10">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center px-4">
            <div class="w-full max-w-3xl flex flex-col items-center text-center glow-bg py-20 rounded-full">
                <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight leading-tight"><span class="text-black">Shape the</span><br><span class="text-viu-yellow">Viu Experience</span></h1>
                <p class="mt-6 text-gray-500 text-lg md:text-xl max-w-2xl font-medium">Help us curate the next generation of Asian entertainment.</p>
                <div class="mt-10 flex flex-col gap-4 w-full max-w-xs">
                    <button onclick="goToPage('user-details-page', 0)" class="btn border-none bg-viu-yellow hover-bg-viu-dark text-black text-lg font-bold px-8 h-14 rounded-xl shadow-md transform transition hover:scale-105 flex items-center justify-center gap-2 group">Start Survey <i data-lucide="arrow-right" class="w-5 h-5"></i></button>
                    <a href="/admin" class="btn bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-500 font-semibold h-12 rounded-xl shadow-sm flex items-center justify-center gap-2 no-underline"><i data-lucide="lock" class="w-4 h-4"></i>Admin Access</a>
                </div>
            </div>
        </div>
    </div>

    <!-- PAGE: USER DETAILS -->
    <div id="user-details-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="goBack('welcome-page')"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span class="progress-text text-viu-yellow">0%</span></div><div class="progress-container"><div class="progress-fill"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center w-full max-w-2xl px-8 fade-in -mt-20">
            <h2 class="text-4xl font-bold text-black mb-16 text-center">Welcome to <span class="text-viu-yellow">viu</span></h2>
            <form onsubmit="event.preventDefault(); goToPage('privacy-page', 8);" class="w-full flex flex-col gap-10">
                <div class="form-control w-full">
                    <label class="label-minimal">Country</label>
                    <div class="relative" id="country-select-wrapper">
                        <button type="button" id="country-select-trigger" class="w-full flex items-center justify-between bg-white border-b-2 border-gray-300 px-0 py-3 text-left rounded-none focus:outline-none focus:border-viu-yellow text-lg font-medium">
                            <span id="country-selected-text" class="text-gray-400">Select country</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400"></i>
                        </button>
                        <div id="country-dropdown" class="absolute left-0 top-full mt-2 w-full max-h-72 overflow-y-auto bg-white rounded-xl shadow-xl border border-gray-200 hidden z-20">
                            <ul id="country-list" class="divide-y divide-gray-100"></ul>
                        </div>
                        <input type="hidden" id="user-country" />
                    </div>
                </div>
                <div class="form-control w-full"><label class="label-minimal">Name (Optional)</label><input type="text" id="user-name" class="input input-minimal w-full" placeholder="Enter name"/></div>
                <div class="form-control w-full"><label class="label-minimal">Email (Optional)</label><input type="email" id="user-email" class="input input-minimal w-full" placeholder="Enter email" pattern="^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$" title="Please enter a valid email address (e.g., name@example.com)"/><p id="user-email-error" class="text-xs text-red-500 mt-1 hidden">Please enter a valid email (e.g., name@gmail.com).</p></div>
                <div class="flex items-center justify-center gap-4 mt-8">
                    <button type="button" onclick="goBack('welcome-page')" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
                    <button type="submit" onclick="return validateUserDetails();" class="btn btn-viu-continue border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2">CONTINUE <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- PAGE: PRIVACY -->
    <div id="privacy-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="goBack('user-details-page', 0)"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span class="progress-text text-viu-yellow">8%</span></div><div class="progress-container"><div class="progress-fill"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center w-full max-w-3xl px-4 fade-in -mt-20">
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 w-full border-l-[12px] border-viu-yellow">
                <h2 class="text-3xl md:text-4xl font-bold text-black mb-6">Privacy Matters</h2>
                <p class="text-gray-500 font-medium text-lg leading-relaxed mb-6">Your responses will remain anonymous unless you choose to share your details.</p>
                <div class="bg-gray-100 rounded-lg p-4 flex items-center gap-4 cursor-pointer" onclick="document.getElementById('privacy-check').click()">
                    <input type="checkbox" id="privacy-check" class="checkbox checkbox-viu bg-white"/>
                    <label for="privacy-check" class="text-gray-600 font-semibold cursor-pointer">I accept the privacy policy</label>
                </div>
            </div>
            <div class="flex items-center justify-center gap-4 mt-12">
                <button type="button" onclick="goBack('user-details-page', 0)" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
                <button type="button" onclick="handlePrivacyContinue()" class="btn btn-viu-continue border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2">CONTINUE <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>

    <!-- PAGE: GENRE -->
    <div id="genre-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="goBack('privacy-page', 8)"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span class="progress-text text-viu-yellow">17%</span></div><div class="progress-container"><div class="progress-fill"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center w-full max-w-4xl px-4 fade-in -mt-20 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-black mb-3">What do you watch?</h2>
            <p class="text-gray-500 text-lg font-medium mb-12">Select your favorite genres.</p>
            <div class="flex flex-wrap justify-center gap-4 max-w-3xl">
                <div class="genre-pill" onclick="toggleGenre(this)">K-Dramas</div>
                <div class="genre-pill" onclick="toggleGenre(this)">Movies</div>
                <div class="genre-pill" onclick="toggleGenre(this)">Variety Shows</div>
                <div class="genre-pill" onclick="toggleGenre(this)">Anime</div>
                <div class="genre-pill" onclick="toggleGenre(this)">Thai Drama</div>
                <div class="genre-pill" onclick="toggleGenre(this)">Others</div>
            </div>
            <div id="others-input-row" class="mt-4 hidden">
                <input id="others-input" type="text" class="input input-minimal w-64" placeholder="Please specify..." oninput="syncOthersValue()" />
            </div>
            <div class="flex items-center justify-center gap-4 mt-16">
                <button type="button" onclick="goBack('privacy-page', 8)" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
                <button type="button" onclick="startQuestions()" class="btn btn-viu-continue border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2">CONTINUE <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>

    <!-- PAGE: QUESTIONS -->
    <div id="question-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="handleQuestionBack()"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span id="q-progress-text" class="progress-text text-viu-yellow">25%</span></div><div class="progress-container"><div id="q-progress-fill" class="progress-fill"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col md:flex-row items-center justify-center w-full max-w-5xl px-4 fade-in -mt-20">
            <div class="flex-shrink-0 mr-0 md:mr-12 mb-6 md:mb-0"><h1 id="q-number" class="big-number text-black">01</h1></div>
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <h2 id="q-title" class="text-4xl md:text-5xl font-bold text-black mb-2">Content Variety</h2>
                <p id="q-subtitle" class="text-gray-500 text-lg md:text-xl mb-10">How fresh are the latest movies?</p>
                <div class="flex flex-col w-fit mx-auto md:mx-0">
                    <div class="flex gap-2 md:gap-4 mb-3" id="star-container"></div>
                    <div class="flex justify-between w-full px-1 text-[10px] md:text-xs font-bold text-gray-400 tracking-widest uppercase"><span>Poor</span><span>Perfect</span></div>
                </div>
            </div>
        </div>
        <div class="w-full flex items-center justify-center gap-4 mb-10 md:mb-16">
            <button type="button" onclick="handleQuestionBack()" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
            <button id="continue-btn" type="button" onclick="nextQuestion()" class="btn btn-viu-continue is-disabled border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2" title="Please rate this question before continuing.">CONTINUE <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
        </div>
    </div>

    <!-- PAGE: FINAL -->
    <div id="final-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="handleFinalBack()"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span class="progress-text text-viu-yellow">95%</span></div><div class="progress-container"><div class="progress-fill" style="width: 95%"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center w-full max-w-4xl px-4 fade-in -mt-20">
            <div class="w-full max-w-3xl">
                <h2 class="text-4xl font-bold text-black mb-8 text-left w-full">Final Thoughts?</h2>
                <textarea id="final-comment" class="w-full h-64 p-8 rounded-[2rem] border border-gray-100 shadow-sm focus:shadow-md focus:outline-none focus:border-gray-200 resize-none text-xl text-gray-700 placeholder-gray-300 bg-white" placeholder="Tell us what we can do better..."></textarea>
            </div>
            <div class="flex items-center justify-center gap-4 mt-12">
                <button type="button" onclick="handleFinalBack()" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
                <button type="button" onclick="goToSummary()" class="btn btn-viu-continue border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2">CONTINUE <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>

    <!-- PAGE: SUMMARY -->
    <div id="summary-page" class="w-full min-h-screen flex flex-col items-center bg-gray-50 relative hidden-page">
        <div class="w-full flex justify-between items-center p-6 md:p-10">
            <div class="cursor-pointer" onclick="goBack('final-page')"><svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="flex flex-col items-end gap-1"><div class="flex justify-between w-[150px] text-[10px] font-bold text-gray-400 uppercase"><span>Progress</span><span class="progress-text text-viu-yellow">100%</span></div><div class="progress-container"><div class="progress-fill" style="width: 100%"></div></div></div>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center w-full max-w-3xl px-4 fade-in -mt-10">
            <h2 class="text-4xl font-bold text-black mb-8 text-center">Ready to Submit?</h2>
            <div class="w-full bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="max-h-[60vh] overflow-y-auto p-6 md:p-8 space-y-0" id="summary-list"></div>
            </div>
            <div class="flex items-center justify-center gap-4 mt-8">
                <button type="button" onclick="goBack('final-page')" class="btn bg-viu-dark-gray hover-bg-viu-gray-hover border-none text-white rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i>BACK</button>
                <button type="button" onclick="submitSurvey()" class="btn btn-viu-continue border-none rounded-full px-8 h-12 font-bold text-xs tracking-widest flex items-center gap-2">SUBMIT <i data-lucide="check" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>

    <!-- PAGE: THANK YOU -->
    <div id="thank-you-page" class="w-full min-h-screen flex flex-col items-center justify-center bg-white relative hidden-page">
        <div class="absolute top-6 left-6 md:top-10 md:left-10">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="flex-grow flex flex-col items-center justify-center text-center px-6 fade-in">
            <div class="w-32 h-32 bg-viu-yellow rounded-full flex items-center justify-center mb-10 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2 class="text-4xl md:text-5xl font-bold text-black mb-6">Thank you so much!</h2>
            <p class="text-gray-500 text-xl font-medium mb-16">Your feedback has been recorded.</p>
            <button onclick="resetSurvey()" class="text-viu-yellow font-bold text-lg tracking-widest uppercase border-b-2 border-viu-yellow pb-1 hover:text-yellow-500 hover:border-yellow-500 transition-colors">BACK TO HOME</button>
        </div>
    </div>

    <!-- ==================== CHATBOT SECTION ==================== -->
    
    <!-- Chatbot Floating Button (Icon Image) -->
    <div class="fixed bottom-6 right-6 md:bottom-10 md:right-10 z-50">
        <img src="/chatbot.svg" alt="Chat" class="w-16 h-16 cursor-pointer hover:scale-105 transition-transform" onclick="toggleChat()" />
    </div>

    <!-- Chatbot Modal Overlay (Centered) -->
    <div id="chat-overlay" class="chat-overlay">
        <div id="chat-window">
            
            <!-- Header -->
            <div class="chat-header">
                <div class="flex items-center gap-2">
                    <img src="/chatbot.svg" class="chat-avatar" alt="Bot" />
                    <span class="font-extrabold text-base">Virtual Assistant</span>
                </div>
                <div class="flex items-center gap-3">
                    <button class="text-black hover:text-white"><i data-lucide="more-horizontal" class="w-5 h-5"></i></button>
                    <button class="text-black hover:text-white" onclick="toggleChat()"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
            </div>

            <!-- Body -->
            <div class="chat-body">
                <!-- Initial greeting will be loaded dynamically -->
            </div>

            <!-- Suggested Questions -->
            <div id="suggested-questions" class="px-4 pb-2 flex flex-wrap gap-2">
                <!-- Will be populated dynamically -->
            </div>

            <!-- Input -->
            <div class="chat-input-area">
                <input type="text" placeholder="Ask something..." class="chat-input" />
                <button class="text-viu-yellow hover:text-yellow-600">
                    <i data-lucide="send" class="w-6 h-6"></i>
                </button>
            </div>

        </div>
    </div>

    <script>
        // Shared Toast helper
        </script>
        <script src="/toast.js"></script>
        <script>
        // Fallback if JS errors stop initial transition
        setTimeout(() => {
            const splash = document.getElementById('splash-screen');
            const welcome = document.getElementById('welcome-page');
            if (splash && !splash.classList.contains('fade-out')) {
                splash.classList.add('fade-out');
                if (welcome && welcome.style.opacity === '0') welcome.style.opacity = '1';
            }
        }, 2500);
        // ==================== 1. INITIAL DATA ====================
        const defaultQuestions = [
            { title: "Content Variety", subtitle: "How fresh are the latest movies?", rating: 0 },
            { title: "Streaming Quality", subtitle: "Are the videos smooth and clear?", rating: 0 },
            { title: "Discovery & Search", subtitle: "Can you easily find new shows/movies?", rating: 0 },
            { title: "Subtitles & Dubbing", subtitle: "Are the translations accurate and timely?", rating: 0 },
            { title: "App Performance", subtitle: "How stable and fast is the app (loading times)?", rating: 0 },
            { title: "Value for Money", subtitle: "Is the subscription worth the content quality?", rating: 0 },
            { title: "Download Feature", subtitle: "How reliable and easy to use is offline viewing?", rating: 0 },
            { title: "Ad Experience (If applicable)", subtitle: "Are the advertisements too disruptive?", rating: 0 },
            { title: "Account Management", subtitle: "Is it easy to manage your subscription/devices?", rating: 0 },
            { title: "Personalized Recommendations", subtitle: "How accurate are the suggestions based on your taste?", rating: 0 }
        ];
        
        let questions = [];
        let selectedGenres = [];
        let currentQIndex = 0;

        // ==================== 2. INIT ====================
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            loadQuestionsFromStorage(); // Load admin edits
            // Immediately reveal welcome page and hide splash
            const splash = document.getElementById('splash-screen');
            const welcome = document.getElementById('welcome-page');
            if (welcome) {
                welcome.classList.remove('hidden-page');
                welcome.style.opacity = '1';
            }
            if (splash) {
                splash.classList.add('fade-out');
            }
        });

        async function loadQuestionsFromStorage() {
            const stored = localStorage.getItem('viu_survey_questions');
            if (stored) {
                questions = JSON.parse(stored);
                questions.forEach(q => q.rating = 0);
            } else {
                try {
                    const res = await fetch("{{ url('/api/questions') }}", { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        const list = (data && data.questions) ? data.questions : [];
                        if (list.length) {
                            questions = list.map(q => ({ id: q.id, title: q.title, subtitle: q.subtitle || '', rating: 0 }));
                            return;
                        }
                    }
                    questions = JSON.parse(JSON.stringify(defaultQuestions));
                } catch (_) {
                    questions = JSON.parse(JSON.stringify(defaultQuestions));
                }
            }
        }

        // ==================== 3. CHATBOT LOGIC ====================
        const chatState = { 
            opened: false,
            conversationHistory: [], // Enhancement #1: Conversation memory
            lastActivity: Date.now(),
            userLanguage: 'en', // Enhancement #3: Multi-language
            inactivityTimer: null, // Enhancement #8: Proactive suggestions
            feedbackGiven: new Set(), // Enhancement #14: Track feedback
            conversationId: null // API conversation ID
        };
        
        // Load persistent chat history from localStorage
        if(localStorage.getItem('viu_chat_history')) {
            try {
                chatState.conversationHistory = JSON.parse(localStorage.getItem('viu_chat_history'));
            } catch(e) {}
        }
        
        function toggleChat() {
            const overlay = document.getElementById('chat-overlay');
            chatState.opened = !chatState.opened;
            overlay.classList.toggle('visible', chatState.opened);
            
            // Start inactivity timer
            if(chatState.opened) {
                resetInactivityTimer();
            }
        }
        
        // Enhancement #15: Restore chat history
        function restoreChatHistory() {
            const body = document.querySelector('.chat-body');
            body.innerHTML = ''; // Clear existing
            chatState.conversationHistory.forEach(msg => {
                if(msg.type === 'user') {
                    addUserMessage(msg.text, msg.timestamp, true);
                } else {
                    addBotMessage(msg.text, msg.timestamp, msg.buttons, true);
                }
            });
        }
        
        // Enhancement #8: Proactive suggestions
        function resetInactivityTimer() {
            clearTimeout(chatState.inactivityTimer);
            chatState.lastActivity = Date.now();
            chatState.inactivityTimer = setTimeout(() => {
                if(chatState.opened && Date.now() - chatState.lastActivity > 30000) {
                    const proactiveMessages = [
                        'Need help? I can explain any question! ðŸ˜Š',
                        'Stuck? Ask me about the survey or Viu features!',
                        'I\'m here if you need assistance! Just type your question.',
                    ];
                    const msg = proactiveMessages[Math.floor(Math.random() * proactiveMessages.length)];
                    addBotMessage(msg, Date.now(), ['Help with survey', 'Tell me about Viu']);
                }
            }, 30000);
        }

        // Optional: wire simple client send to backend
        (function initChat() {
            const body = document.querySelector('.chat-body');
            const input = document.querySelector('.chat-input');
            const sendBtn = document.querySelector('.chat-input-area button');
            
            // Enhancement #3: Multi-language support
            const translations = {
                en: {
                    greeting: 'Hello, Viu Fam! ðŸ‘‹ I\'m your Virtual Assistant.',
                    surveyStart: 'To start the survey: 1) Click "Start Survey" on the welcome screen',
                    countries: 'Viu is available in: ðŸ‡­ðŸ‡° Hong Kong, ðŸ‡¸ðŸ‡¬ Singapore, ðŸ‡²ðŸ‡¾ Malaysia',
                    help: 'I can help with: ðŸ“‹ Survey questions, ðŸ“º What Viu offers'
                },
                zh: {
                    greeting: 'æ‚¨å¥½ï¼ŒViuå®¶æ—ï¼ðŸ‘‹ æˆ‘æ˜¯æ‚¨çš„è™šæ‹ŸåŠ©æ‰‹ã€‚',
                    surveyStart: 'å¼€å§‹è°ƒæŸ¥ï¼š1) ç‚¹å‡»æ¬¢è¿Žå±å¹•ä¸Šçš„"å¼€å§‹è°ƒæŸ¥"',
                    countries: 'Viu å¯ç”¨åœ°åŒº: ðŸ‡­ðŸ‡° é¦™æ¸¯, ðŸ‡¸ðŸ‡¬ æ–°åŠ å¡, ðŸ‡²ðŸ‡¾ é©¬æ¥è¥¿äºš',
                    help: 'æˆ‘å¯ä»¥å¸®åŠ©: ðŸ“‹ è°ƒæŸ¥é—®é¢˜, ðŸ“º Viuæä¾›ä»€ä¹ˆ'
                },
                th: {
                    greeting: 'à¸ªà¸§à¸±à¸ªà¸”à¸µ Viu Family! ðŸ‘‹ à¸‰à¸±à¸™à¸„à¸·à¸­à¸œà¸¹à¹‰à¸Šà¹ˆà¸§à¸¢à¹€à¸ªà¸¡à¸·à¸­à¸™à¸‚à¸­à¸‡à¸„à¸¸à¸“',
                    surveyStart: 'à¹€à¸£à¸´à¹ˆà¸¡à¹à¸šà¸šà¸ªà¸³à¸£à¸§à¸ˆ: 1) à¸„à¸¥à¸´à¸ "à¹€à¸£à¸´à¹ˆà¸¡à¹à¸šà¸šà¸ªà¸³à¸£à¸§à¸ˆ" à¸šà¸™à¸«à¸™à¹‰à¸²à¸•à¹‰à¸­à¸™à¸£à¸±à¸š',
                    countries: 'Viu à¸¡à¸µà¹ƒà¸«à¹‰à¸šà¸£à¸´à¸à¸²à¸£à¹ƒà¸™: ðŸ‡­ðŸ‡° à¸®à¹ˆà¸­à¸‡à¸à¸‡, ðŸ‡¸ðŸ‡¬ à¸ªà¸´à¸‡à¸„à¹‚à¸›à¸£à¹Œ, ðŸ‡²ðŸ‡¾ à¸¡à¸²à¹€à¸¥à¹€à¸‹à¸µà¸¢',
                    help: 'à¸‰à¸±à¸™à¸ªà¸²à¸¡à¸²à¸£à¸–à¸Šà¹ˆà¸§à¸¢: ðŸ“‹ à¸„à¸³à¸–à¸²à¸¡à¹à¸šà¸šà¸ªà¸³à¸£à¸§à¸ˆ, ðŸ“º Viu à¸¡à¸µà¸­à¸°à¹„à¸£à¸šà¹‰à¸²à¸‡'
                }
            };
            
            // Enhancement #5: Fuzzy matching
            function fuzzyMatch(text, patterns) {
                const normalized = text.toLowerCase().replace(/[^a-z0-9\s]/g, '');
                return patterns.some(p => {
                    const pattern = p.toLowerCase().replace(/[^a-z0-9\s]/g, '');
                    const distance = levenshteinDistance(normalized, pattern);
                    return distance <= 2 || normalized.includes(pattern) || pattern.includes(normalized);
                });
            }
            
            function levenshteinDistance(a, b) {
                const matrix = [];
                for(let i = 0; i <= b.length; i++) matrix[i] = [i];
                for(let j = 0; j <= a.length; j++) matrix[0][j] = j;
                for(let i = 1; i <= b.length; i++) {
                    for(let j = 1; j <= a.length; j++) {
                        if(b.charAt(i-1) === a.charAt(j-1)) {
                            matrix[i][j] = matrix[i-1][j-1];
                        } else {
                            matrix[i][j] = Math.min(matrix[i-1][j-1] + 1, matrix[i][j-1] + 1, matrix[i-1][j] + 1);
                        }
                    }
                }
                return matrix[b.length][a.length];
            }
            
            // Enhancement #9: Sentiment analysis
            function detectSentiment(text) {
                const negativeWords = ['bad', 'terrible', 'worst', 'hate', 'awful', 'frustrated', 'angry', 'broken', 'useless', 'stuck', 'help me', 'not working', 'cant', 'cannot', 'wont', 'doesnt'];
                const q = text.toLowerCase();
                const negativeCount = negativeWords.filter(w => q.includes(w)).length;
                const hasMultipleExclamation = (text.match(/!/g) || []).length >= 2;
                const hasAllCaps = text === text.toUpperCase() && text.length > 5;
                
                if(negativeCount >= 2 || hasMultipleExclamation || hasAllCaps) {
                    return 'negative';
                }
                return 'neutral';
            }
            
            // Message rendering helpers
            function addUserMessage(text, timestamp = Date.now(), skipHistory = false) {
                const ur = document.createElement('div');
                ur.className = 'chat-row user';
                const ub = document.createElement('div');
                ub.className = 'chat-bubble-user';
                ub.textContent = text;
                ur.appendChild(ub);
                body.appendChild(ur);
                
                if(!skipHistory) {
                    chatState.conversationHistory.push({ type: 'user', text, timestamp });
                    saveChatHistory();
                }
            }
            
            function addBotMessage(text, timestamp = Date.now(), buttons = [], skipHistory = false) {
                const br = document.createElement('div');
                br.className = 'chat-row bot';
                
                const ba = document.createElement('img');
                ba.className = 'chat-avatar';
                ba.src = '/chatbot.svg';
                ba.alt = 'Bot';
                
                const bb = document.createElement('div');
                bb.className = 'chat-bubble-bot';
                
                // Add message text
                const textDiv = document.createElement('div');
                textDiv.className = 'bot-message-text';
                textDiv.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
                bb.appendChild(textDiv);
                
                // Quick action buttons
                if(buttons && buttons.length > 0) {
                    const btnContainer = document.createElement('div');
                    btnContainer.className = 'quick-action-buttons';
                    buttons.forEach(btnText => {
                        const btn = document.createElement('button');
                        btn.className = 'quick-action-btn';
                        btn.textContent = btnText;
                        btn.addEventListener('click', () => {
                            input.value = btnText;
                            sendBtn.click();
                        });
                        btnContainer.appendChild(btn);
                    });
                    bb.appendChild(btnContainer);
                }
                
                br.appendChild(ba);
                br.appendChild(bb);
                body.appendChild(br);
                
                lucide.createIcons();
                
                if(!skipHistory) {
                    chatState.conversationHistory.push({ type: 'bot', text, timestamp, buttons });
                    saveChatHistory();
                }
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function saveChatHistory() {
                try {
                    localStorage.setItem('viu_chat_history', JSON.stringify(chatState.conversationHistory.slice(-50)));
                } catch(e) {}
            }
            
            async function send(text) {
                // Reset inactivity timer
                resetInactivityTimer();
                
                // Hide suggestions immediately when user sends first message
                if(suggestedContainer && chatState.conversationHistory.filter(m => m.type === 'user').length === 0) {
                    suggestedContainer.style.display = 'none';
                }
                
                // Add user message
                addUserMessage(text);
                
                // Show typing indicator
                const br = document.createElement('div');
                br.className = 'chat-row bot typing-indicator';
                const ba = document.createElement('img');
                ba.className = 'chat-avatar';
                ba.src = '/chatbot.svg';
                ba.alt = 'Bot';
                const bb = document.createElement('div');
                bb.className = 'chat-bubble-bot';
                const ell = document.createElement('div');
                ell.className = 'typing';
                ell.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
                bb.appendChild(ell);
                br.appendChild(ba);
                br.appendChild(bb);
                body.appendChild(br);
                body.scrollTop = body.scrollHeight;
                
                try {
                    // Use persistent conversation ID for entire session
                    if (!chatState.conversationId) {
                        chatState.conversationId = 'chat-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    }
                    const res = await fetch('/api/chatbot/ask', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            question: text,
                            conversation_id: chatState.conversationId
                        })
                    });
                    
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    
                    const data = await res.json();
                    
                    // Store conversation ID
                    if(data.conversation_id) {
                        chatState.conversationId = data.conversation_id;
                    }
                    
                    // Remove typing indicator
                    if(body.contains(br)) body.removeChild(br);
                    
                    // Add bot response from API
                    const response = data.data?.answer || data.answer || 'Sorry, no response';
                    addBotMessage(response, Date.now(), []);
                } catch (err) {
                    console.error('API Error:', err);
                    // Remove typing indicator
                    if(body.contains(br)) body.removeChild(br);
                    // Show error message with proper emoji
                    addBotMessage('Bestie, may konting connection issue. Paki-try ulit? 🥺', Date.now(), []);
                }
                
                body.scrollTop = body.scrollHeight;
            }
            sendBtn.addEventListener('click', () => { const t = (input.value||'').trim(); if(!t) return; input.value=''; send(t); });
            input.addEventListener('keypress', (e) => { if(e.key==='Enter'){ sendBtn.click(); }});
            document.querySelectorAll('.quick-reply').forEach(btn => btn.addEventListener('click', () => { input.value = btn.dataset.text; sendBtn.click(); }));
            
            // Clear old localStorage chat history on page load to show fresh welcome
            if(localStorage.getItem('viu_chat_history')) {
                localStorage.removeItem('viu_chat_history');
                chatState.conversationHistory = [];
            }
            
            // Initialize with greeting from API
            async function initGreeting() {
                try {
                    const res = await fetch('/api/chatbot/ask', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            question: 'hello',
                            conversation_id: chatState.conversationId || 'init-' + Date.now()
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if(data.conversation_id) chatState.conversationId = data.conversation_id;
                        const greeting = data.data?.answer || data.answer || 'Hello! ðŸ‘‹';
                        addBotMessage(greeting, Date.now(), []);
                    } else {
                        // API failed - show error
                        addBotMessage('API connection issue (' + res.status + '). Try refreshing? 😭', Date.now(), []);
                    }
                } catch(e) {
                    console.error('Init greeting failed:', e);
                    addBotMessage('Cannot connect to AI. Error: ' + e.message, Date.now(), []);
                }
            }
            
            // Load greeting when chat body is ready
            if(body.children.length === 0) {
                initGreeting();
            }

            // ==================== SUGGESTED QUESTIONS ====================
            const suggestedContainer = document.getElementById('suggested-questions');
            
            const suggestedQuestions = [
                "What's new on Viu?",
                "How do I download content?",
                "Paano mag-subscribe?",
                "How to cancel subscription?",
                "Ano ang mga genre?",
                "What languages are available?",
                "How many devices can I use?",
                "Magkano ang premium?",
                "Is there Korean drama?",
                "How to change password?"
            ];

            function showSuggestedQuestions() {
                if(!suggestedContainer) return;
                
                // Hide suggestions if user has already sent a message
                if(chatState.conversationHistory.filter(m => m.type === 'user').length > 0) {
                    suggestedContainer.style.display = 'none';
                    return;
                }
                
                suggestedContainer.style.display = 'flex';
                
                // Get 3 random questions
                const shuffled = [...suggestedQuestions].sort(() => Math.random() - 0.5);
                const selected = shuffled.slice(0, 3);
                
                suggestedContainer.innerHTML = '';
                selected.forEach(q => {
                    const chip = document.createElement('button');
                    chip.className = 'suggested-chip';
                    chip.textContent = q;
                    chip.onclick = () => {
                        input.value = q;
                        sendBtn.click();
                        // Hide suggestions immediately after first user message
                        suggestedContainer.style.display = 'none';
                    };
                    suggestedContainer.appendChild(chip);
                });
            }

            // Show suggestions on load (only if no conversation history)
            showSuggestedQuestions();
            
            // Refresh suggestions every 15 seconds ONLY if user hasn't sent any messages
            setInterval(() => {
                if(chatState.conversationHistory.length === 0 && Date.now() - chatState.lastActivity > 15000) {
                    showSuggestedQuestions();
                }
            }, 15000);
        })();

        // ==================== 4. NAVIGATION ====================
        function goToPage(pageId, progress) {
            // Hide all pages EXCEPT splash screen, chatbot overlay, and floating button
            document.querySelectorAll('body > div:not(#splash-screen):not(.chat-overlay):not(.fixed)').forEach(p => p.classList.add('hidden-page'));
            document.getElementById(pageId).classList.remove('hidden-page');
            if(progress !== undefined) updateProgress(progress);
            lucide.createIcons();
        }

        function goBack(pageId) {
            document.querySelectorAll('body > div:not(#splash-screen):not(.chat-overlay):not(.fixed)').forEach(p => p.classList.add('hidden-page'));
            document.getElementById(pageId).classList.remove('hidden-page');
            if(pageId === 'user-details-page') updateProgress(0);
            if(pageId === 'privacy-page') updateProgress(8);
            if(pageId === 'genre-page') updateProgress(17);
            if(pageId === 'welcome-page') updateProgress(0);
            if(pageId === 'final-page') updateProgress(95);
        }

        function updateProgress(percent) {
            document.querySelectorAll('.progress-fill').forEach(el => el.style.width = percent + '%');
            document.querySelectorAll('.progress-text').forEach(el => el.innerText = percent + '%');
        }

        // ==================== 5. FLOW LOGIC ====================
        function handlePrivacyContinue() {
            if(!document.getElementById('privacy-check').checked) return alert('Please accept privacy policy.');
            goToPage('genre-page', 17);
        }

        function validateUserDetails(){
            const emailEl = document.getElementById('user-email');
            const errorEl = document.getElementById('user-email-error');
            const email = (emailEl.value||'').trim();
            // Reset state
            emailEl.classList.remove('border-red-500');
            if(errorEl){ errorEl.classList.add('hidden'); errorEl.textContent = 'Please enter a valid email (e.g., name@gmail.com).'; }
            if(email){
                const re = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
                if(!re.test(email)){
                    // Show inline hint and block
                    emailEl.classList.add('border-red-500');
                    if(errorEl){ errorEl.classList.remove('hidden'); }
                    // Optional toast
                    if(window.showToast){ showToast('error','Please enter a valid email (e.g., name@gmail.com).'); }
                    emailEl.focus();
                    return false;
                }
            }
            return true;
        }

        function toggleGenre(el) {
            el.classList.toggle('selected');
            const text = el.innerText;
            if(selectedGenres.includes(text)) {
                selectedGenres = selectedGenres.filter(g => g !== text);
                if(text === 'Others') { document.getElementById('others-input-row').classList.add('hidden'); }
            } else {
                selectedGenres.push(text);
                if(text === 'Others') { document.getElementById('others-input-row').classList.remove('hidden'); }
            }
            console.log('Genre toggled:', text, '| Current selection:', selectedGenres);
        }

        function syncOthersValue(){
            const inputEl = document.getElementById('others-input');
            if(!inputEl) return;
            const val = inputEl.value.trim();
            console.log('syncOthersValue called - input value:', val, '| length:', val.length);
            
            // Only proceed if "Others" was selected
            const hasOthers = selectedGenres.some(g => g === 'Others' || g.startsWith('Others: '));
            if(!hasOthers) return;
            
            // Remove base 'Others' and any previous 'Others: xxx' entries
            selectedGenres = selectedGenres.filter(g => g !== 'Others' && !g.startsWith('Others: '));
            
            // Add the full custom tag if there's text
            if(val){
                const customTag = `Others: ${val}`;
                selectedGenres.push(customTag);
                console.log('Added custom Others:', customTag);
            } else {
                // If no text yet, keep base 'Others' so we know it was selected
                selectedGenres.push('Others');
                console.log('Kept base Others (no text yet)');
            }
            console.log('syncOthersValue result - selectedGenres:', selectedGenres);
        }

        function startQuestions() {
            currentQIndex = 0;
            loadQuestion();
            goToPage('question-page', 25);
        }

        function loadQuestion() {
            const data = questions[currentQIndex];
            const num = currentQIndex + 1;
            const qTitle = document.getElementById('q-title');
            
            document.getElementById('q-number').innerText = num < 10 ? `0${num}` : num;
            qTitle.innerText = data.title;
            document.getElementById('q-subtitle').innerText = data.subtitle;

            if (data.title.length > 30) {
                qTitle.className = "text-3xl md:text-4xl font-bold text-black mb-2";
            } else {
                qTitle.className = "text-4xl md:text-5xl font-bold text-black mb-2";
            }

            const progress = Math.round(25 + (currentQIndex * 7)); 
            document.getElementById('q-progress-fill').style.width = progress + '%';
            document.getElementById('q-progress-text').innerText = progress + '%';
            renderStars(data.rating);
            updateContinueState();
        }

        function renderStars(currentRating) {
            const container = document.getElementById('star-container');
            container.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const starDiv = document.createElement('div');
                const isFilled = i <= currentRating;
                const fillColor = isFilled ? '#F6BE00' : '#E5E7EB'; 
                const iconClass = isFilled ? 'star-icon active' : 'star-icon';
                starDiv.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${fillColor}" stroke="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${iconClass}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
                starDiv.onclick = () => { questions[currentQIndex].rating = i; renderStars(i); updateContinueState(); };
                starDiv.onmouseenter = () => previewStars(i);
                container.appendChild(starDiv);
            }
            container.onmouseleave = () => renderStars(questions[currentQIndex].rating);
        }

        function updateContinueState(){
            const btn = document.getElementById('continue-btn');
            if(!btn) return;
            const rated = Number(questions[currentQIndex]?.rating || 0) >= 1;
            if(rated){
                btn.classList.remove('is-disabled');
                btn.setAttribute('title', 'Continue');
                btn.removeAttribute('aria-disabled');
            } else {
                btn.classList.add('is-disabled');
                btn.setAttribute('title', 'Please rate this question before continuing.');
                btn.setAttribute('aria-disabled', 'true');
            }
        }

        function previewStars(hoverIndex) {
            const svgs = document.querySelectorAll('#star-container svg');
            svgs.forEach((svg, idx) => {
                if (idx < hoverIndex) { svg.style.fill = '#F6BE00'; svg.style.color = '#F6BE00'; } 
                else { svg.style.fill = '#E5E7EB'; svg.style.color = '#E5E7EB'; }
            });
        }

        function nextQuestion() {
            const currentRating = Number(questions[currentQIndex]?.rating || 0);
            if(currentRating < 1) { return; }
            if(currentQIndex < questions.length - 1) {
                currentQIndex++;
                loadQuestion();
                const mainContent = document.querySelector('#question-page .fade-in');
                if(mainContent) {
                    mainContent.classList.remove('fade-in');
                    void mainContent.offsetWidth; 
                    mainContent.classList.add('fade-in');
                }
            } else {
                goToPage('final-page', 95);
            }
        }

        function handleQuestionBack() {
            if(currentQIndex > 0) { currentQIndex--; loadQuestion(); } else { goBack('genre-page'); }
        }

        function handleFinalBack() {
            currentQIndex = questions.length - 1; 
            loadQuestion(); 
            goToPage('question-page', 95);
        }

        function goToSummary() {
            const listContainer = document.getElementById('summary-list');
            listContainer.innerHTML = ''; 
            questions.forEach((q, index) => {
                const num = index + 1;
                const numStr = num < 10 ? `0${num}` : num;
                const rating = q.rating || 0; 
                const row = document.createElement('div');
                row.className = 'flex justify-between items-center py-4 border-b border-gray-100 last:border-0 hover:bg-gray-50 px-2 rounded-lg transition-colors';
                row.innerHTML = `<div class="text-gray-500 font-medium text-lg"><span class="font-bold text-gray-400 mr-2">${numStr}.</span> ${q.title}</div><div class="flex items-center gap-1 text-viu-yellow font-bold text-xl"><span>${rating}</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#F6BE00" stroke="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>`;
                listContainer.appendChild(row);
            });
            goToPage('summary-page', 100);
        }

        async function submitSurvey() {
            console.log('Before sync - selectedGenres:', selectedGenres);
            // Sync Others value one final time before submission
            syncOthersValue();
            console.log('After sync - selectedGenres:', selectedGenres);
            
            const country = document.getElementById('user-country').value || null;
            const email = document.getElementById('user-email').value || null;
            const name = document.getElementById('user-name').value || null;
            const suggestion = document.getElementById('final-comment').value || null;
            // Capture all selected genres; include custom Others expansion
            const servicesVoted = (selectedGenres && selectedGenres.length) ? selectedGenres.slice() : ['General'];
            console.log('Final payload - services:', servicesVoted);
            console.log('Final payload - country:', country);
            console.log('Final payload - name:', name);
            console.log('Final payload - email:', email);
            const payload = {
                country,
                email,
                name,
                services: servicesVoted,
                service: servicesVoted[0],
                ratings: questions.map(q => ({ question_id: q.id || null, title: q.title, rating: q.rating })),
                suggestion,
                submitted_at: new Date().toISOString()
            };
            try {
                const res = await fetch("{{ url('/api/public/responses') }}", {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
                    body: JSON.stringify(payload)
                });
                if(!res.ok){
                    const msg = await res.text();
                    showToast('error', msg || 'Failed to submit');
                    return;
                }
                // Also persist locally to support admin listing fallback
                try {
                    const key = 'viu_submissions';
                    const arr = JSON.parse(localStorage.getItem(key) || '[]');
                    const withId = Object.assign({ id: Date.now().toString() }, payload);
                    arr.push(withId);
                    localStorage.setItem(key, JSON.stringify(arr));
                } catch(_) { /* ignore local storage errors */ }
            } catch(e) {
                showToast('error','Network error while submitting');
                return;
            }
            goToPage('thank-you-page');
        }

        function resetSurvey() {
            loadQuestionsFromStorage();
            currentQIndex = 0;
            selectedGenres = [];
            document.querySelectorAll('input').forEach(input => { if(input.type === 'checkbox') input.checked = false; else input.value = ''; });
            document.querySelectorAll('textarea').forEach(t => t.value = '');
            document.querySelectorAll('.genre-pill').forEach(p => p.classList.remove('selected'));
            goToPage('welcome-page', 0);
        }

        // ==================== COUNTRY DROPDOWN ====================
        const viuCountries = [
            { name:'Hong Kong', code:'HKG', iso2:'hk' },
            { name:'Singapore', code:'SGP', iso2:'sg' },
            { name:'Malaysia', code:'MYS', iso2:'my' },
            { name:'Indonesia', code:'IDN', iso2:'id' },
            { name:'Thailand', code:'THA', iso2:'th' },
            { name:'Philippines', code:'PHL', iso2:'ph' },
            { name:'United Arab Emirates', code:'ARE', iso2:'ae' },
            { name:'Saudi Arabia', code:'SAU', iso2:'sa' },
            { name:'Qatar', code:'QAT', iso2:'qa' },
            { name:'Kuwait', code:'KWT', iso2:'kw' },
            { name:'Oman', code:'OMN', iso2:'om' },
            { name:'Bahrain', code:'BHR', iso2:'bh' },
            { name:'Jordan', code:'JOR', iso2:'jo' },
            { name:'Egypt', code:'EGY', iso2:'eg' },
            { name:'South Africa', code:'ZAF', iso2:'za' }
        ];
        document.addEventListener('DOMContentLoaded', () => {
            buildCountryDropdown();
        });
        function buildCountryDropdown(){
            const listEl = document.getElementById('country-list');
            if(!listEl) return;
            listEl.innerHTML = '';
            viuCountries.forEach(c => {
                const li = document.createElement('li');
                li.className = 'py-3 px-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3 text-gray-700';
                li.innerHTML = `<span class="fi fi-${c.iso2} flag-ico"></span><span class="flex-1">${c.name} - ${c.code}</span>`;
                li.addEventListener('click', () => selectCountry(c));
                listEl.appendChild(li);
            });
            const trigger = document.getElementById('country-select-trigger');
            trigger.addEventListener('click', toggleCountryDropdown);
            document.addEventListener('click', (e) => {
                const wrap = document.getElementById('country-select-wrapper');
                if(!wrap.contains(e.target)) {
                    document.getElementById('country-dropdown').classList.add('hidden');
                }
            });
        }
        function toggleCountryDropdown(){
            document.getElementById('country-dropdown').classList.toggle('hidden');
        }
        function selectCountry(c){
            document.getElementById('user-country').value = c.name;
            const el = document.getElementById('country-selected-text');
            el.innerHTML = `<span class="fi fi-${c.iso2} flag-ico align-middle"></span> <span class="align-middle">${c.name} (${c.code})</span>`;
            el.classList.remove('text-gray-400');
            document.getElementById('country-dropdown').classList.add('hidden');
        }
    </script>
</body>
</html>
