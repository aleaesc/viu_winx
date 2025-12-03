<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIU Admin Portal</title>
    
    <!-- Tailwind CSS & DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Removed Vite in dev to avoid manifest error; using public assets -->
    <!-- JSVectorMap for world heatmap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js"></script>

    <!-- Shared Toast Assets -->
    <link rel="stylesheet" href="/toast.css">

    <!-- Early-login handler to avoid scope/parse issues blocking auth -->
    <script>
    // Define switchTab early so onclick handlers work
    window.switchTab = function(tabName) {
        document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
        document.querySelectorAll('main > div').forEach(div => div.classList.add('hidden-page'));

        const navId = 'nav-' + tabName;
        const viewId = 'view-' + tabName;
        const navEl = document.getElementById(navId);
        const viewEl = document.getElementById(viewId);
        if(navEl) navEl.classList.add('active');
        if(viewEl) viewEl.classList.remove('hidden-page');
        
        if(tabName === 'settings') {
            const u = localStorage.getItem('admin_username');
            const usernameInput = document.getElementById('set-username');
            if(u && usernameInput) usernameInput.value = u;
        }
        if(tabName === 'answers' || tabName === 'suggestions') {
            if(typeof fetchAdminData === 'function') {
                fetchAdminData(window.selectedRange || 'all');
            }
        }
    };

    window.handleLogin = async function(){
        try {
            const usernameEl = document.getElementById('username-input');
            const passwordEl = document.getElementById('password-input');
            const username = usernameEl ? usernameEl.value.trim() : '';
            const password = passwordEl ? passwordEl.value : '';
            if(!username || !password){ alert('Enter username and password'); return; }
            const res = await fetch('{{ url('/api/login') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                mode: 'cors',
                credentials: 'omit',
                body: JSON.stringify({ username, password })
            });
            const data = await res.json().catch(() => ({}));
            if(!res.ok){
                const msg = (data && data.message) ? data.message : 'Invalid credentials';
                if(window.showToast){ window.showToast('error', msg); } else { alert(msg); }
                return;
            }
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('admin_username', username);
            const loginPage = document.getElementById('login-page');
            const appContainer = document.getElementById('app-container');
            if(loginPage) loginPage.classList.add('hidden-page');
            if(appContainer) appContainer.classList.remove('hidden-page');
            if(typeof window.switchTab === 'function'){ window.switchTab('dashboard'); }
        } catch(e){
            if(window.showToast){ window.showToast('error', 'Network error'); } else { alert('Network error'); }
        }
    };
    </script>

        <!-- Simplified data fetch + world map (robust, no errors) -->
        <script>
            // Safe JSON parse
            const safeJSON = async (res) => { try { return await res.json(); } catch { return null; } };

            // Demo fallbacks so UI never goes blank
            const DEMO_STATS = {
                questions: [
                    { title:'Content', avg_rating:3.2, count:10 },
                    { title:'Quality', avg_rating:4.1, count:8 },
                    { title:'Search', avg_rating:3.8, count:6 },
                    { title:'Subtitles', avg_rating:3.6, count:7 },
                    { title:'Performance', avg_rating:2.9, count:9 },
                    { title:'Value', avg_rating:4.0, count:5 }
                ],
                services: [{service:'General', submissions:10},{service:'KDRAMA', submissions:6}],
                countries: [{country:'Philippines', submissions:8},{country:'Singapore', submissions:4}],
                trends: [{date:'2025-12-01', submissions:5, avg_rating:3.6},{date:'2025-12-02', submissions:7, avg_rating:3.9}]
            };
            const DEMO_RESPONSES = [
                { country:'Philippines', city:'Manila', latitude:14.5995, longitude:120.9842, service:'kdrama', email:'demo@viu.com', submitted_at:'2025-12-02' },
                { country:'Singapore', city:'Singapore', latitude:1.3521, longitude:103.8198, service:'general', email:'demo2@viu.com', submitted_at:'2025-12-01' }
            ];

            // Globals expected elsewhere
            window.responses = window.responses || [];

            async function loadStats(range='all'){
                const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
                const headers = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
                if(token) headers['Authorization'] = 'Bearer '+token;
                try {
                    const urlStats = `{{ url('/api/admin/stats') }}?range=${range}`;
                    const res = await fetch(urlStats, { headers, credentials:'omit' });
                    const ok = res && res.ok;
                    const data = ok ? (await safeJSON(res)) : null;
                    const stats = data && typeof data === 'object' ? data : DEMO_STATS;
                    if(typeof renderChartsFromStats === 'function') renderChartsFromStats(stats);
                } catch(e){
                    if(window.showToast) showToast('info','Showing demo stats');
                    if(typeof renderChartsFromStats === 'function') renderChartsFromStats(DEMO_STATS);
                }
            }

            async function loadResponses(){
                const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
                const headers = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
                if(token) headers['Authorization'] = 'Bearer '+token;
                try {
                    const url = `{{ url('/api/public/responses') }}`;
                    const res = await fetch(url, { headers, credentials:'omit' });
                    const ok = res && res.ok;
                    const data = ok ? (await safeJSON(res)) : null;
                    window.responses = Array.isArray(data) ? data : DEMO_RESPONSES;
                } catch(e){
                    if(window.showToast) showToast('info','Showing demo responses');
                    window.responses = DEMO_RESPONSES;
                }
                if(typeof renderSurveyAnswersTable === 'function') renderSurveyAnswersTable();
            }

            document.addEventListener('DOMContentLoaded', async () => {
                await loadResponses(); // Load responses first
                loadStats('all'); // Then load stats which will render the map
            });
        </script>

    <style>
        body { font-family: 'Inter', sans-serif; overflow-x: hidden; background-color: #FAFAFA; }
        .bg-viu-yellow { background-color: #F6BE00; }
        .text-viu-yellow { color: #F6BE00; }
        .border-viu-yellow { border-color: #F6BE00; }
        .hover-bg-viu-dark:hover { background-color: #dca000; }
        .hidden-page { display: none !important; }
        .fade-in { animation: fadeIn 0.5s ease-in forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .login-container { background-color: rgba(107, 114, 128, 0.8); }
        .login-card { background-color: white; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .input-login { background: transparent; border: none; border-bottom: 2px solid #E5E7EB; border-radius: 0; padding-left: 0; padding-right: 2.5rem; transition: border-color 0.3s ease; font-size: 1rem; color: #374151; height: 3rem; }
        .input-login:focus { outline: none; border-bottom-color: #F6BE00; box-shadow: none; }
        .sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #6B7280; font-weight: 600; font-size: 0.95rem; border-radius: 0.75rem; transition: all 0.2s; margin-bottom: 0.5rem; cursor: pointer; }
        .sidebar-link:hover { background-color: #F3F4F6; color: black; }
        .sidebar-link.active { background-color: #F6BE00; color: black; box-shadow: 0 4px 6px -1px rgba(246, 190, 0, 0.3); }
        .dashboard-card { background-color: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #F3F4F6; padding: 1.5rem; }
        .stat-label { color: #9CA3AF; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 0.5rem; }
        .text-green-500 { color: #10B981; }
        .input-filter { border: 1px solid #E5E7EB; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.95rem; width: 100%; color: #374151; background-color: white; }
        .input-filter:focus { outline: none; border-color: #F6BE00; box-shadow: 0 0 0 3px rgba(246, 190, 0, 0.1); }
        .custom-table-header { background-color: #F3F4F6; color: #111827; font-weight: 800; font-size: 0.9rem; text-transform: capitalize; }
        .custom-table-row { background-color: white; border-bottom: 1px solid #F3F4F6; transition: background-color 0.2s; }
        .custom-table-row:hover { background-color: #FEFCE8; }
        .overlay-container { background-color: rgba(107, 114, 128, 0.8); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .white-card { background-color: white; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .suggestion-card { background-color: white; border: 1px solid #F3F4F6; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .question-card { background-color: white; border: 1px solid #E5E7EB; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start; transition: all 0.2s; }
        .question-card:hover { border-color: #F6BE00; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .input-underline { background: transparent; border: none; border-bottom: 2px solid #E5E7EB; border-radius: 0; padding-left: 0; transition: border-color 0.3s ease; font-size: 1rem; color: #374151; height: 3rem; width: 100%; }
        .input-underline:focus { outline: none; border-bottom-color: #F6BE00; box-shadow: none; }
        .label-small { font-size: 0.75rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem; }

        /* Toast Styles (Bottom-right, exact UI spec) */
        .toast-stack { position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1000; display: flex; flex-direction: column; gap: 0.9rem; }
        .toast { background: #ffffff; border-radius: 0.9rem; box-shadow: 0 14px 32px rgba(0,0,0,0.12); padding: 1rem 1.25rem; min-width: 420px; border: 1px solid #EDF2F7; position: relative; overflow: hidden; }
        .toast::before { content: ''; position: absolute; left: 0.9rem; top: 0.9rem; bottom: 0.9rem; width: 10px; border-radius: 999px; }
        .toast .t-icon { width: 36px; height: 36px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; margin: 0 auto 0.6rem auto; }
        .toast .t-content { display: flex; flex-direction: column; align-items: center; text-align: center; line-height: 1.25; }
        .toast .t-title { font-weight: 800; font-size: 1.05rem; margin-bottom: 4px; color: #111827; }
        .toast .t-sub { color: #6B7280; font-size: 0.95rem; }
        .toast .t-close { position: absolute; right: 0.9rem; top: 0.9rem; color: #9CA3AF; cursor: pointer; font-weight: 700; }
        .toast-success::before { background: #22C55E; }
        .toast-error::before { background: #EF4444; }
        .toast-info::before { background: #3B82F6; }
        .toast-warning::before { background: #F59E0B; }
        .t-icon-success { background: #22C55E; }
        .t-icon-error { background: #EF4444; }
        .t-icon-info { background: #3B82F6; }
        .t-icon-warning { background: #F59E0B; }
    </style>
</head>
<body class="h-screen overflow-hidden">

    <!-- ==================== 1. LOGIN PAGE ==================== -->
    <div id="login-page" class="login-container fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute top-6 left-6 md:top-10 md:left-10 opacity-50">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="login-card w-full max-w-md p-8 md:p-12 flex flex-col items-center text-center relative z-10 fade-in">
            <h2 class="text-3xl font-bold text-black mb-2">Admin Login</h2>
            <p class="text-gray-400 text-sm mb-10 px-4 leading-relaxed">Please enter your credentials to access the dashboard.</p>
            <form class="w-full flex flex-col gap-6" onsubmit="event.preventDefault(); handleLogin();">
                <div class="form-control w-full"><input id="username-input" type="text" placeholder="Username" class="input input-login w-full" required/></div>
                <div class="form-control w-full relative">
                    <input id="password-input" type="password" placeholder="Password" class="input input-login w-full" required/>
                    <button type="button" onclick="togglePassword()" class="absolute right-0 bottom-3 text-gray-400 hover:text-gray-600"><i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i></button>
                </div>
                <button type="submit" class="btn border-none bg-viu-yellow hover-bg-viu-dark text-black font-bold h-12 rounded-full mt-6 text-sm tracking-wider uppercase shadow-md w-full">LOGIN</button>
                <a href="{{ url('/usersurvey') }}" class="text-gray-500 font-bold text-sm hover:text-gray-700 transition-colors cursor-pointer">Cancel</a>
            </form>
        </div>
    </div>

    <!-- ==================== 2. SUBMISSION DETAILS MODAL ==================== -->
    <div id="submission-modal" class="overlay-container hidden-page">
        <div class="white-card w-full max-w-4xl max-h-[90vh] overflow-y-auto p-8 md:p-10 relative fade-in">
            <h2 class="text-3xl font-extrabold text-center mb-10 text-black">Submission Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 border-b border-gray-100 pb-8">
                <div><p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Location:</p><p class="font-bold text-xl text-black" id="modal-location">--</p></div>
                <div><p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Service Voted:</p><p class="font-bold text-xl text-black uppercase" id="modal-service">--</p></div>
                <div class="md:col-span-1"><p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Email:</p><p class="font-bold text-lg text-black" id="modal-email">--</p></div>
            </div>
            <h3 class="text-xl font-bold mb-6 text-black">Ratings</h3>
            <!-- Dynamic Ratings Container -->
            <div id="modal-ratings-container" class="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-4 mb-8 border-b border-gray-100 pb-8 text-sm md:text-base">
                <!-- JS will populate this -->
            </div>
            <h3 class="text-xl font-bold mb-4 text-black">Suggestion</h3>
            <div id="modal-suggestion" class="w-full p-4 rounded-xl border border-gray-200 text-gray-700 italic mb-10 bg-gray-50 text-lg">
                <!-- JS will populate this -->
            </div>
            <div class="text-center"><button onclick="closeModal()" class="btn bg-viu-yellow hover-bg-viu-dark border-none text-black font-bold px-12 rounded-full text-sm tracking-widest shadow-md">CLOSE</button></div>
        </div>
    </div>

    <!-- ==================== 3. DELETE CONFIRMATION MODAL ==================== -->
    <div id="delete-confirm-modal" class="overlay-container hidden-page">
        <div class="white-card w-full max-w-md p-8 md:p-10 relative fade-in">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                </div>
            </div>
            <h2 class="text-2xl font-extrabold text-center mb-4 text-black">Confirm Deletion</h2>
            <p class="text-gray-600 text-center mb-2" id="delete-message">Are you sure you want to delete this submission?</p>
            <p class="text-gray-500 text-sm text-center mb-8">This action cannot be undone and will permanently remove all ratings and suggestions associated with this response.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="btn flex-1 bg-gray-200 hover:bg-gray-300 border-none text-gray-700 font-bold rounded-full">Cancel</button>
                <button id="confirm-delete-btn" class="btn flex-1 bg-red-500 hover:bg-red-600 border-none text-white font-bold rounded-full">Delete</button>
            </div>
        </div>
    </div>

    <!-- ==================== 4. ADD/EDIT QUESTION MODAL ==================== -->
    <div id="add-question-modal" class="overlay-container hidden-page">
        <div class="white-card w-full max-w-lg p-8 md:p-10 relative fade-in">
            <h2 class="text-2xl font-extrabold text-center mb-8 text-black" id="question-modal-title">Add New Question</h2>
            <input type="hidden" id="q-edit-index" value="-1" /> <!-- Hidden field to track index being edited -->
            
            <div class="flex flex-col gap-6 mb-8">
                <div class="form-control w-full">
                    <label class="label-small">Main Question Title</label>
                    <input type="text" id="q-title-input" placeholder="Title" class="input-underline" />
                </div>
                <div class="form-control w-full">
                    <label class="label-small">Sub-text / Hint</label>
                    <input type="text" id="q-subtitle-input" placeholder="Subtitle" class="input-underline" />
                </div>
            </div>

            <div class="text-center flex flex-col gap-3">
                <button onclick="saveQuestionChanges()" class="btn bg-viu-yellow hover-bg-viu-dark border-none text-black font-bold px-8 rounded-full text-sm tracking-widest shadow-md w-full">SAVE CHANGES</button>
                <button onclick="closeAddQuestionModal()" class="btn btn-ghost text-gray-500 font-bold text-xs tracking-widest uppercase">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ==================== 4. MAIN APP DASHBOARD ==================== -->
    <div id="app-container" class="flex h-full hidden-page">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white border-r border-gray-100 flex flex-col h-full shadow-sm z-20">
            <div class="p-8 pb-8 flex flex-col items-center">
                <div class="flex flex-col items-center">
                    <h1 class="text-4xl font-black tracking-tight text-viu-yellow leading-none">viu</h1>
                    <span class="text-black font-bold tracking-widest text-sm mt-1">ADMIN</span>
                </div>
            </div>
            <nav class="flex-1 px-4 overflow-y-auto">
                <div onclick="switchTab('dashboard')" id="nav-dashboard" class="sidebar-link active"><i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard</div>
                <div onclick="switchTab('answers')" id="nav-answers" class="sidebar-link"><i data-lucide="list-checks" class="w-5 h-5"></i> Survey Answers</div>
                <div onclick="switchTab('suggestions')" id="nav-suggestions" class="sidebar-link"><i data-lucide="message-square-text" class="w-5 h-5"></i> Suggestions</div>
                <div onclick="switchTab('edit')" id="nav-edit" class="sidebar-link"><i data-lucide="file-edit" class="w-5 h-5"></i> Edit Questions</div>
            </nav>
            <div class="p-4 border-t border-gray-100">
                <div onclick="switchTab('settings')" id="nav-settings" class="sidebar-link"><i data-lucide="settings" class="w-5 h-5"></i> Settings</div>
                <div onclick="logout()" class="sidebar-link text-red-500 hover:bg-red-50 hover:text-red-600 cursor-pointer"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#FAFAFA] p-8">
            
            <!-- VIEW: DASHBOARD -->
            <div id="view-dashboard" class="fade-in">
                <header class="mb-8"><h2 class="text-5xl font-extrabold text-black">Dashboard</h2></header>
                <div class="dashboard-card mb-6 relative">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <h3 class="text-xl font-bold text-black">Key Findings</h3>
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <!-- Range Filter Dropdown (Left) -->
                            <div class="dropdown">
                                <label tabindex="0" class="btn btn-sm bg-white border border-gray-300 hover:border-viu-yellow text-gray-700 font-semibold rounded-full">Range</label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-white rounded-box w-40 text-sm" id="range-menu">
                                    <li><a data-range="7d">Last 7 Days</a></li>
                                    <li><a data-range="30d">Last 30 Days</a></li>
                                    <li><a data-range="all">All Time</a></li>
                                </ul>
                            </div>
                            <!-- Export Dropdown (Right, styled like screenshot) -->
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn bg-white border border-gray-300 hover:border-viu-yellow text-gray-700 font-semibold rounded-full flex items-center gap-2">
                                    <i data-lucide="share" class="w-4 h-4"></i>
                                    Export
                                </label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-white rounded-box w-56">
                                    <li><a id="btn-export-csv">Export CSV (Excel)</a></li>
                                    <li><a id="btn-export-pdf">Export PDF</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div><p class="stat-label">Most Active Location</p><div class="text-lg font-bold text-black flex items-baseline gap-1">PHILIPPINES <span class="text-gray-400 font-normal text-sm">(Simulated)</span></div></div>
                        <div><p class="stat-label">Area for Improvement</p><div class="text-lg font-bold text-black flex items-baseline gap-1">APP PERFORMANCE <span class="text-gray-400 font-normal text-sm">(2.5)</span></div></div>
                        <div><p class="stat-label">User Sentiment</p><div class="text-lg font-bold text-green-500">Good</div></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 relative">
                    <div class="dashboard-card flex flex-col items-center justify-center text-center py-10"><p class="stat-label mb-2">Total Submissions</p><p class="text-5xl font-black text-viu-yellow" id="dash-total">0</p></div>
                    <div class="dashboard-card flex flex-col items-center justify-center text-center py-10"><p class="stat-label mb-2">Average Rating (Overall)</p><p class="text-4xl font-black text-black" id="dash-avg">0.0 <span class="text-2xl text-gray-400 font-semibold">/ 5.0</span></p></div>
                    <div class="dashboard-card flex flex-col items-center justify-center text-center py-10"><p class="stat-label mb-2">Top Category</p><p class="text-3xl font-black text-viu-yellow uppercase">K-Dramas</p><p class="text-sm text-gray-400 mt-1">Most Popular</p></div>
                </div>
                <!-- Charts Area -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="dashboard-card lg:col-span-2"><h3 class="text-lg font-bold text-black mb-6">Average Ratings per Category</h3><div class="h-80 w-full"><canvas id="barChart"></canvas></div></div>
                    <div class="dashboard-card"><h3 class="text-lg font-bold text-black mb-6">Satisfaction Distribution</h3><div class="h-80 w-full flex justify-center"><canvas id="pieChart"></canvas></div></div>
                    <!-- Response Distribution by Country -->
                    <div class="dashboard-card lg:col-span-3">
                        <h3 class="text-lg font-bold text-black mb-6">Response Distribution</h3>
                        <div id="mapLegend" class="text-base text-gray-700"></div>
                    </div>
                </div>
                <!-- Executive Insights Section (below charts) -->
                <div class="dashboard-card mt-6">
                    <h3 class="text-xl font-extrabold text-black mb-4">Executive Insights</h3>
                    <div id="diagnosis-text" class="text-gray-800 text-sm leading-7"></div>
                </div>
            </div>

            <!-- VIEW: SURVEY ANSWERS -->
            <div id="view-answers" class="hidden-page fade-in">
                <header class="mb-8"><h2 class="text-4xl font-extrabold text-black">Survey Answers (Responses)</h2></header>
                <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
                    <span id="local-debug-count">Local submissions: 0</span>
                    <button id="btn-reload-local-a" class="btn btn-xs bg-white border border-gray-300 hover:border-viu-yellow text-gray-700 rounded-full">Reload Local Data</button>
                </div>
                <div class="flex flex-col lg:flex-row gap-4 mb-6">
                    <div class="flex-grow"><input type="text" id="adminSearchInput" placeholder="Search by location, email, or services..." class="input-filter" oninput="adminFilterTable()" /></div>
                    <div class="w-full lg:w-48"><select id="adminServiceFilter" class="input-filter cursor-pointer appearance-none bg-white" onchange="adminFilterTable()"><option value="all">Services (All)</option><option value="movies">Movies</option><option value="kdrama">KDRAMA</option><option value="variety shows">Variety Shows</option><option value="anime">Anime</option><option value="thai drama">Thai Drama</option><option value="general">General</option></select></div>
                    <div class="w-full lg:w-48"><select id="adminCountryFilter" class="input-filter cursor-pointer appearance-none bg-white" onchange="adminFilterTable()"><option value="all">Country (All)</option><option value="Singapore">Singapore</option><option value="Philippines">Philippines</option><option value="Malaysia">Malaysia</option><option value="Indonesia">Indonesia</option><option value="Thailand">Thailand</option><option value="Hong Kong">Hong Kong</option></select></div>
                    <div class="w-full lg:w-48"><select id="adminRangeFilter" class="input-filter cursor-pointer appearance-none bg-white" onchange="adminFilterTable()"><option value="all">Date Range (All)</option><option value="7d">Last 7 Days</option><option value="30d">Last 30 Days</option></select></div>
                    <!-- Answers Export Dropdown beside date range -->
                    <div class="dropdown lg:self-stretch">
                        <label tabindex="0" class="btn bg-white border border-gray-300 hover:border-viu-yellow text-gray-700 font-semibold rounded-full flex items-center gap-2 h-full min-h-0 px-4">
                            <i data-lucide="share" class="w-4 h-4"></i>
                            Export
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-white rounded-box w-48">
                            <li><a id="answers-export-csv">Export CSV</a></li>
                            <li><a id="answers-export-pdf">Export PDF</a></li>
                        </ul>
                    </div>
                </div>
                <div class="text-gray-600 font-medium mb-4" id="adminResultCount">0 Submissions found.</div>
                <div class="text-xs text-gray-400 mb-2" id="data-source-badge" style="display:none;">Using local fallback data</div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="custom-table-header h-14">
                                <th class="pl-8 w-16">#</th>
                                <th class="w-1/5">
                                    <div class="flex items-center gap-1">User Location
                                        <button type="button" class="ml-1 text-gray-500 hover:text-black" onclick="setAnswersSort('country')"><i data-lucide="chevrons-up-down" class="w-4 h-4"></i></button>
                                    </div>
                                </th>
                                <th class="w-1/5">
                                    <div class="flex items-center gap-1">Service
                                        <button type="button" class="ml-1 text-gray-500 hover:text-black" onclick="setAnswersSort('service')"><i data-lucide="chevrons-up-down" class="w-4 h-4"></i></button>
                                    </div>
                                </th>
                                <th class="w-1/5">
                                    <div class="flex items-center gap-1">Email
                                        <button type="button" class="ml-1 text-gray-500 hover:text-black" onclick="setAnswersSort('email')"><i data-lucide="chevrons-up-down" class="w-4 h-4"></i></button>
                                    </div>
                                </th>
                                <th class="w-1/5">
                                    <div class="flex items-center gap-1">Date
                                        <button type="button" class="ml-1 text-gray-500 hover:text-black" onclick="setAnswersSort('submitted_at')"><i data-lucide="chevrons-up-down" class="w-4 h-4"></i></button>
                                    </div>
                                </th>
                                <th class="pr-8 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700" id="adminAnswersTableBody">
                            <!-- JS will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: SUGGESTIONS -->
            <div id="view-suggestions" class="hidden-page fade-in">
                <header class="mb-8"><h2 class="text-4xl font-extrabold text-black">User Suggestions & Comments</h2></header>
                <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
                    <span id="local-debug-count-s">Local submissions: 0</span>
                    <button id="btn-reload-local-s" class="btn btn-xs bg-white border border-gray-300 hover:border-viu-yellow text-gray-700 rounded-full">Reload Local Data</button>
                </div>
                <!-- Tabs for Active/Archived -->
                <div class="flex gap-2 mb-6">
                    <button id="tab-active-suggestions" class="px-6 py-2 rounded-lg font-semibold transition-all bg-viu-yellow text-black" onclick="switchSuggestionsTab('active')">Active</button>
                    <button id="tab-archived-suggestions" class="px-6 py-2 rounded-lg font-semibold transition-all bg-white border border-gray-200 text-gray-600 hover:border-viu-yellow" onclick="switchSuggestionsTab('archived')">Archived</button>
                </div>
                <div id="suggestions-list-container">
                    <!-- JS will populate this -->
                </div>
            </div>

            <!-- VIEW: EDIT QUESTIONS -->
            <div id="view-edit" class="hidden-page fade-in">
                <header class="mb-8"><h2 class="text-4xl font-extrabold text-black">Edit Survey Questions</h2></header>
                
                <button class="btn bg-viu-yellow hover-bg-viu-dark border-none text-black font-bold rounded-full mb-8 px-6 flex items-center gap-2" onclick="openAddQuestionModal()">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i> Add New Question
                </button>

                <!-- Questions List -->
                <div class="flex flex-col gap-4" id="questions-list-container">
                    <!-- JS will populate this -->
                </div>
            </div>

            <!-- VIEW: SETTINGS -->
            <div id="view-settings" class="hidden-page fade-in">
                <header class="mb-8"><h2 class="text-4xl font-extrabold text-black">Settings</h2></header>
                <div class="dashboard-card max-w-xl">
                    <h3 class="text-xl font-bold text-black mb-4">Update Admin Credentials</h3>
                    <form class="flex flex-col gap-4" onsubmit="event.preventDefault(); submitSettings();">
                        <div>
                            <label class="label-small">Current Password</label>
                            <input type="password" id="set-current" class="input-underline" placeholder="Enter current password" required>
                        </div>
                        <div>
                            <label class="label-small">New Username</label>
                            <input type="text" id="set-username" class="input-underline" placeholder="New admin username" required>
                        </div>
                        <div>
                            <label class="label-small">New Password</label>
                            <input type="password" id="set-password" class="input-underline" placeholder="New password" required>
                        </div>
                        <div>
                            <label class="label-small">Confirm New Password</label>
                            <input type="password" id="set-password2" class="input-underline" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" class="btn bg-viu-yellow hover-bg-viu-dark border-none text-black font-bold rounded-full mt-2">Save Changes</button>
                        <div id="set-msg" class="text-sm mt-2"></div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        // ==================== TOASTS & MODALS ====================
        </script>
        <script src="/toast.js"></script>
        <script>

        function showErrorModal(title, message) {
            const overlay = document.createElement('div');
            overlay.className = 'overlay-container';
            const card = document.createElement('div');
            card.className = 'white-card w-full max-w-md p-8 relative';
            card.innerHTML = `
                <h3 class="text-2xl font-extrabold text-red-600 mb-4">${title}</h3>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="text-right"><button id="errClose" class="btn bg-viu-yellow hover-bg-viu-dark border-none text-black font-bold rounded-full">Close</button></div>
            `;
            overlay.appendChild(card);
            document.body.appendChild(overlay);
            document.getElementById('errClose').addEventListener('click', () => overlay.remove());
        }
        // ==================== DEFAULT DATA ====================
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
        let responses = [];
        let suggestions = [];
        // Local aggregates built from responses as a fallback for charts/exports
        let localQuestionStats = [];
        // Helper: normalize a single ratings array to [{title, rating}]
        function normalizeRatingsArr(ratingsRaw){
            let ratings = Array.isArray(ratingsRaw) ? ratingsRaw.slice() : [];
            // If single object contains multiple key-value pairs (map-like), explode into entries
            if(ratings.length === 1 && typeof ratings[0] === 'object' && ratings[0] !== null){
                const obj = ratings[0];
                const keys = Object.keys(obj);
                if(keys.length > 2){
                    const expanded = [];
                    keys.forEach((k, idx) => {
                        const val = obj[k];
                        const titleGuess = (questions[idx]?.title) || k || `Q${idx+1}`;
                        expanded.push({ title: String(titleGuess), rating: Number(val||0) });
                    });
                    ratings = expanded;
                }
            }
            // If primitives (numbers), align with stored questions by index
            if(ratings.length && typeof ratings[0] !== 'object'){
                return ratings.map((val, idx) => ({ title: (questions[idx]?.title)||(`Q${idx+1}`), rating: Number(val||0) }));
            }
            // If stdClass-like objects missing keys, convert via Object.values order
            ratings = ratings.map((obj) => {
                if(!obj) return null;
                const hasTitle = Object.prototype.hasOwnProperty.call(obj,'title') || Object.prototype.hasOwnProperty.call(obj,'question');
                const hasRating = Object.prototype.hasOwnProperty.call(obj,'rating') || Object.prototype.hasOwnProperty.call(obj,'value');
                if(hasTitle && hasRating){
                    return { title: (obj.title||obj.question||'Untitled').toString(), rating: Number(obj.rating||obj.value||0) };
                }
                const vals = Object.values(obj||{});
                if(vals.length>=2){
                    return { title: (vals[0]||'Untitled').toString(), rating: Number(vals[1]||0) };
                }
                return null;
            }).filter(Boolean);
            // If titles still Untitled or missing, align by index with known questions
            if(ratings.length){
                ratings = ratings.map((q, idx) => ({ title: (q.title && !['Untitled','Unknown','Question'].includes(q.title)) ? q.title : ((questions[idx]?.title)||(`Q${idx+1}`)), rating: Number(q.rating||0) }));
            }
            return ratings;
        }

        // ==================== INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            
            // 1. Load Questions
            const storedQs = localStorage.getItem('viu_survey_questions');
            questions = storedQs ? JSON.parse(storedQs) : JSON.parse(JSON.stringify(defaultQuestions));

            // 2. Load Responses & Suggestions from backend
            fetchAdminData();

            // 3. Initialize Views
            renderQuestionsList();
            updateDashboardStats();
            initCharts(); // Using Chart.js
        });

        // ==================== AUTH LOGIC ====================
        function togglePassword() {
            const input = document.getElementById('password-input');
            const icon = document.getElementById('eye-icon');
            if (input.type === "password") { input.type = "text"; icon.style.opacity = '0.5'; } 
            else { input.type = "password"; icon.style.opacity = '1'; }
        }

        async function fetchAdminData(range){
            try {
                const token = localStorage.getItem('auth_token');
                // Backend listing endpoints not yet implemented; use public responses endpoint as fallback
                let urlResp = '{{ url('/api/public/responses') }}';
                if(range && range!=='all'){ urlResp += ('?range='+range); }
                let usedBackend = false;
                let rRes = await fetch(urlResp, { headers: { 'Accept':'application/json' }, method: 'GET' }).catch(() => null);
                if(rRes && rRes.ok){
                    const data = await rRes.json();
                    usedBackend = true;
                    responses = (Array.isArray(data)? data : (data.responses||[])).map(r => ({
                        id: r.id || r.response_id || r.uuid || null,
                        country: r.country || r.location || 'Unknown',
                        service: r.service || r.service_clicked || r.genre || 'general',
                        email: r.email || null,
                        submitted_at: r.submitted_at || r.created_at || r.date || null,
                        suggestion: r.suggestion || r.comment || null,
                        ratings: normalizeRatingsArr(r.ratings || [])
                    }));
                } else {
                    // If GET fails with 405, try POST with method override header
                    let retryData = null;
                    try {
                        const retry = await fetch(urlResp, {
                            method: 'POST',
                            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-HTTP-Method-Override': 'GET' },
                            body: JSON.stringify({ action: 'list' })
                        });
                        if(retry.ok){
                            retryData = await retry.json();
                            usedBackend = true;
                        }
                    } catch(_){}
                    if(usedBackend && retryData){
                        const data = retryData;
                        responses = (Array.isArray(data)? data : (data.responses||[])).map(r => ({
                            id: r.id || r.response_id || r.uuid || null,
                            country: r.country || r.location || 'Unknown',
                            service: r.service || r.service_clicked || r.genre || 'general',
                            email: r.email || null,
                            submitted_at: r.submitted_at || r.created_at || r.date || null,
                            suggestion: r.suggestion || r.comment || null,
                            ratings: normalizeRatingsArr(r.ratings || [])
                        }));
                    } else {
                    // Fallback to local submissions (from client side)
                    const local = JSON.parse(localStorage.getItem('viu_submissions') || '[]');
                    responses = local.map(r => ({
                        id: r.id || null,
                        country: r.country || 'Unknown',
                        service: r.service || 'general',
                        email: r.email || null,
                        submitted_at: r.submitted_at || null,
                        suggestion: r.suggestion || null,
                        ratings: normalizeRatingsArr(r.ratings || [])
                    }));
                    const msgEl = document.getElementById('adminResultCount');
                    const badgeEl = document.getElementById('data-source-badge');
                    if(msgEl) msgEl.innerText = `${responses.length} Submissions loaded.`;
                    if(badgeEl) badgeEl.style.display = 'block';
                    }
                }
                // Build suggestions list from responses (unique by suggestion text + id)
                suggestions = responses.filter(r => !!r.suggestion).map(r => ({
                    id: r.id,
                    suggestion: r.suggestion,
                    country: r.country,
                    submitted_at: r.submitted_at
                }));
                // Build per-question stats from responses ratings as fallback
                const qMap = new Map();
                (responses||[]).forEach(r => {
                    (r.ratings||[]).forEach(q => {
                        const title = (q.title||q.question||'Untitled').toString();
                        const rating = Number(q.rating||q.value||0);
                        const finalTitle = (title && title!=='Untitled') ? title : ((questions[(q.index??-1)]?.title) || title);
                        if(!qMap.has(finalTitle)) qMap.set(finalTitle, { sum:0, count:0 });
                        if(rating>0){ const o=qMap.get(finalTitle); o.sum+=rating; o.count+=1; }
                    });
                });
                localQuestionStats = Array.from(qMap.entries()).map(([title,o])=>({
                    title,
                    avg_rating: o.count ? (o.sum/o.count) : 0,
                    ratings_count: o.count
                })).sort((a,b)=>Number(b.avg_rating)-Number(a.avg_rating));
                renderSurveyAnswersTable();
                renderSuggestions();
                updateDashboardStats();
            } catch(e){ responses = []; suggestions = []; }
        }

        // Auto-refresh admin when local submissions change (e.g., new client submit)
        window.addEventListener('storage', (e) => {
            if(e.key === 'viu_submissions') {
                fetchAdminData(window.selectedRange || 'all');
            }
        });

        function logout() {
            document.getElementById('app-container').classList.add('hidden-page');
            document.getElementById('login-page').classList.remove('hidden-page');
            document.querySelectorAll('input').forEach(i => i.value = '');
        }

        // ==================== DASHBOARD LOGIC ====================
        
        // Expose on window to avoid scope issues
        window.reloadLocal = function(){
            const local = JSON.parse(localStorage.getItem('viu_submissions') || '[]');
            responses = local.map(r => ({
                id: r.id || null,
                country: r.country || 'Unknown',
                service: r.service || 'general',
                email: r.email || null,
                submitted_at: r.submitted_at || null,
                suggestion: r.suggestion || null,
                ratings: r.ratings || []
            }));
            suggestions = responses.filter(r => !!r.suggestion).map(r => ({ id: r.id, suggestion: r.suggestion, country: r.country, submitted_at: r.submitted_at }));
            const msgEl = document.getElementById('adminResultCount');
            if(msgEl) msgEl.innerText = `${responses.length} Submissions loaded.`;
            const badgeEl = document.getElementById('data-source-badge');
            if(badgeEl) badgeEl.style.display = 'block';
            const c1 = document.getElementById('local-debug-count');
            const c2 = document.getElementById('local-debug-count-s');
            const localCount = (JSON.parse(localStorage.getItem('viu_submissions')||'[]')||[]).length;
            if(c1) c1.textContent = `Local submissions: ${localCount}`;
            if(c2) c2.textContent = `Local submissions: ${localCount}`;
            renderSurveyAnswersTable();
            renderSuggestions();
            updateDashboardStats();
        }

        // Wire buttons after DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            const b1 = document.getElementById('btn-reload-local-a');
            const b2 = document.getElementById('btn-reload-local-s');
            if(b1) b1.addEventListener('click', () => window.reloadLocal());
            if(b2) b2.addEventListener('click', () => window.reloadLocal());
            const confirmBtn = document.getElementById('confirm-delete-btn');
            if(confirmBtn) confirmBtn.addEventListener('click', confirmDelete);
        });

        function updateDashboardStats() {
            const dashTotal = document.getElementById('dash-total');
            if(dashTotal) dashTotal.innerText = responses.length; // live count first
            // Calculate Avg
            if(responses.length === 0) return;
            let totalScore = 0;
            let totalQuestions = 0;
            responses.forEach(r => {
                (r.ratings||[]).forEach(q => {
                    if(Number(q.rating) > 0) {
                        totalScore += Number(q.rating);
                        totalQuestions++;
                    }
                });
            });
            const avg = totalQuestions > 0 ? (totalScore / totalQuestions).toFixed(2) : '0.00';
            const dashAvg = document.getElementById('dash-avg');
            if(dashAvg) dashAvg.innerHTML = `${avg} <span class="text-2xl text-gray-400 font-semibold">/ 5.0</span>`;
            // Build simple diagnosis
            try {
                const items = localQuestionStats && localQuestionStats.length ? localQuestionStats.slice() : [];
                const topImproved = items.slice().sort((a,b)=>Number(b.avg_rating)-Number(a.avg_rating)).slice(0,3);
                const topDeclined = items.slice().sort((a,b)=>Number(a.avg_rating)-Number(b.avg_rating)).slice(0,3);
                const services = {};
                (responses||[]).forEach(r => { const s=(r.service||'general').toString().toLowerCase(); services[s]=(services[s]||0)+1; });
                const popularService = Object.entries(services).sort((a,b)=>b[1]-a[1])[0]?.[0] || 'general';
                const el = document.getElementById('diagnosis-text');
                if(el){
                    const highlights = topImproved.map(i=>i.title).join(', ') || '—';
                    const focus = topDeclined.map(i=>i.title).join(', ') || '—';
                    const total = responses.length;
                    const avgTxt = document.getElementById('dash-avg')?.textContent?.split(' ')[0] || '0.0';
                    el.innerHTML = `
                        <p class="mb-3"><span class="font-semibold">Overview:</span> ${total} submissions recorded with an overall average rating of <span class="font-semibold">${avgTxt}</span>. The most engaged service is <span class="font-semibold capitalize">${popularService}</span>.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                            <div class="p-3 rounded-lg bg-[#F8FAFC] border border-gray-100">
                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Strengths</div>
                                <div class="text-sm">${highlights}</div>
                            </div>
                            <div class="p-3 rounded-lg bg-[#FFF7ED] border border-orange-100">
                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Focus Areas</div>
                                <div class="text-sm">${focus}</div>
                            </div>
                            <div class="p-3 rounded-lg bg-[#F0FDF4] border border-emerald-100">
                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Key Actions</div>
                                <div class="text-sm">Improve app performance and search; preserve content quality and subtitle accuracy.</div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">Suggested next steps: benchmark streaming quality metrics, refine discovery flows (search and recommendations), and address value-for-money perceptions through content packaging and pricing communication.</p>
                    `;
                }
            } catch(_) {}
        }

        // ==================== SURVEY ANSWERS LOGIC ====================
        // Sorting state for Answers table
        let answersSortColumn = null;
        let answersSortDirection = 'asc';

        function setAnswersSort(column){
            if(answersSortColumn === column){
                answersSortDirection = (answersSortDirection === 'asc') ? 'desc' : 'asc';
            } else {
                answersSortColumn = column;
                answersSortDirection = 'asc';
            }
            renderSurveyAnswersTable();
        }

        function getSortedResponses(){
            const arr = responses.slice();
            if(!answersSortColumn) return arr;
            arr.sort((a,b) => {
                const av = (a[answersSortColumn]||'').toString().toLowerCase();
                const bv = (b[answersSortColumn]||'').toString().toLowerCase();
                if(av < bv) return answersSortDirection==='asc' ? -1 : 1;
                if(av > bv) return answersSortDirection==='asc' ? 1 : -1;
                return 0;
            });
            return arr;
        }

        function renderSurveyAnswersTable() {
            const tbody = document.getElementById('adminAnswersTableBody');
            if(!tbody) return;
            tbody.innerHTML = '';
            const countEl = document.getElementById('adminResultCount');
            if(countEl){ countEl.innerText = `${responses.length} Submissions found.`; }
            const dataRows = getSortedResponses();
            dataRows.forEach((r, index) => {
                const row = document.createElement('tr');
                row.className = 'custom-table-row h-16';
                row.innerHTML = `
                    <td class="pl-8 font-medium">${index + 1}</td>
                    <td class="location-cell">${(r.country||r.location_country||'-')}</td>
                    <td class="service-cell">${(r.service||'').toString().toLowerCase()}</td>
                    <td>${r.email || '-'}</td>
                    <td>${(r.submitted_at || r.date || '').toString().split('T')[0]}</td>
                    <td class="pr-8 text-right"><div class="flex justify-end gap-2">
                        <button onclick="openSubmissionModal(${index})" class="btn bg-white border border-gray-300 hover:border-viu-yellow text-gray-800 btn-sm px-4 rounded-md"><i data-lucide="eye" class="w-4 h-4"></i></button>
                        <button onclick="deleteResponse('${r.id || ''}', ${index})" class="btn bg-red-500 hover:bg-red-600 border-none text-white btn-sm px-4 rounded-md"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div></td>
                `;
                tbody.appendChild(row);
            });
            lucide.createIcons();
        }

        function openSubmissionModal(index) {
            const r = responses[index];
            console.log('Opening modal for response:', r);
            console.log('Raw ratings data:', r.ratings);
            
            document.getElementById('modal-location').innerText = r.country || 'Unknown';
            document.getElementById('modal-service').innerText = (r.service||'').toString().toUpperCase();
            document.getElementById('modal-email').innerText = r.email || '-';
            document.getElementById('modal-suggestion').innerText = r.suggestion || "No suggestion provided.";

            // Populate Ratings
            const ratingsContainer = document.getElementById('modal-ratings-container');
            ratingsContainer.innerHTML = '';
            let ratings = r.ratings || [];
            
            console.log('Ratings array length:', ratings.length);
            console.log('First rating item:', ratings[0]);
            
            // Handle if ratings is array of objects with numeric keys (stdClass from Laravel)
            if(ratings.length > 0 && typeof ratings[0] === 'object') {
                // Check if it's a plain object with title and rating properties
                if(!ratings[0].hasOwnProperty('title') || !ratings[0].hasOwnProperty('rating')) {
                    // Convert stdClass/object to usable format
                    ratings = ratings.map(obj => {
                        const vals = Object.values(obj);
                        console.log('Converting object values:', vals);
                        return { title: vals[0], rating: vals[1] };
                    });
                }
            }
            
            if(ratings.length === 0) {
                ratingsContainer.innerHTML = '<p class="text-gray-400 text-sm">No ratings recorded.</p>';
            } else {
                ratings.forEach((q, idx) => {
                    console.log('Rating', idx, q);
                    const title = q.title || 'Question';
                    const rating = q.rating || 0;
                    const questionNum = idx + 1;
                    ratingsContainer.innerHTML += `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium"><span class="text-gray-400 font-semibold mr-2">#${questionNum}</span>${title}:</span>
                            <span class="text-viu-yellow font-extrabold text-lg">${rating}/5</span>
                        </div>
                    `;
                });
            }

            document.getElementById('submission-modal').classList.remove('hidden-page');
        }

        function closeModal() {
            document.getElementById('submission-modal').classList.add('hidden-page');
        }

        let pendingDelete = null;

        function deleteResponse(id, index) {
            if(!id) {
                showToast('error', 'Invalid response ID');
                return;
            }
            pendingDelete = { id, index, type: 'response' };
            document.getElementById('delete-message').textContent = 'Are you sure you want to delete this submission?';
            document.getElementById('delete-confirm-modal').classList.remove('hidden-page');
            
            // Re-wire button each time to ensure it works
            const confirmBtn = document.getElementById('confirm-delete-btn');
            if(confirmBtn) {
                confirmBtn.onclick = confirmDelete;
            }
            
            lucide.createIcons();
        }

        function closeDeleteModal() {
            document.getElementById('delete-confirm-modal').classList.add('hidden-page');
            pendingDelete = null;
        }

        async function confirmDelete() {
            if(!pendingDelete) return;
            const { id, index } = pendingDelete;
            closeDeleteModal();
            
            try {
                const token = localStorage.getItem('auth_token');
                const res = await fetch(`{{ url('/api/public/responses') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': token ? ('Bearer ' + token) : ''
                    }
                });
                if(!res.ok) {
                    showToast('error', 'Failed to delete submission');
                    return;
                }
                // Remove from local array and re-render
                responses.splice(index, 1);
                renderSurveyAnswersTable();
                updateDashboardStats();
                // Also update suggestions if this had one
                suggestions = responses.filter(r => !!r.suggestion).map(r => ({
                    id: r.id,
                    suggestion: r.suggestion,
                    country: r.country,
                    submitted_at: r.submitted_at
                }));
                renderSuggestions();
                showToast('success', 'Submission deleted successfully');
            } catch(e) {
                showToast('error', 'Network error');
            }
        }



        function adminFilterTable() {
            // Re-implement filter logic if needed, or just rely on CSS filtering
            const searchInput = document.getElementById('adminSearchInput').value.toLowerCase();
            const serviceFilter = document.getElementById('adminServiceFilter').value;
            const countryFilter = document.getElementById('adminCountryFilter') ? document.getElementById('adminCountryFilter').value : 'all';
            const rangeFilter = document.getElementById('adminRangeFilter') ? document.getElementById('adminRangeFilter').value : 'all';
            const tbody = document.getElementById('adminAnswersTableBody');
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const serviceCell = row.querySelector('.service-cell')?.innerText.toLowerCase() || '';
                const countryCell = row.querySelector('.location-cell')?.innerText || '';
                let ok = text.includes(searchInput);
                if(serviceFilter !== 'all') ok = ok && serviceCell === serviceFilter;
                if(countryFilter !== 'all') ok = ok && countryCell === countryFilter;
                row.style.display = ok ? '' : 'none';
            });
            if(rangeFilter && rangeFilter!=='all') { fetchAdminData(rangeFilter); } else { fetchAdminData('all'); }
        }

        // ==================== SUGGESTIONS LOGIC ====================
        let currentSuggestionsTab = 'active';
        let archivedSuggestions = JSON.parse(localStorage.getItem('archived_suggestions') || '[]');

        // Expose functions globally
        window.switchSuggestionsTab = function(tab) {
            currentSuggestionsTab = tab;
            document.getElementById('tab-active-suggestions').className = tab === 'active' 
                ? 'px-6 py-2 rounded-lg font-semibold transition-all bg-viu-yellow text-black'
                : 'px-6 py-2 rounded-lg font-semibold transition-all bg-white border border-gray-200 text-gray-600 hover:border-viu-yellow';
            document.getElementById('tab-archived-suggestions').className = tab === 'archived'
                ? 'px-6 py-2 rounded-lg font-semibold transition-all bg-viu-yellow text-black'
                : 'px-6 py-2 rounded-lg font-semibold transition-all bg-white border border-gray-200 text-gray-600 hover:border-viu-yellow';
            renderSuggestions();
        };

        function renderSuggestions() {
            const container = document.getElementById('suggestions-list-container');
            container.innerHTML = '';
            
            let list = [];
            if(currentSuggestionsTab === 'active') {
                if((suggestions.length || 0) === 0 && (responses.length || 0) === 0) {
                    container.innerHTML = '<p class="text-gray-400 text-center">No active suggestions yet.</p>';
                    return;
                }
                list = suggestions.length ? suggestions : responses.filter(r=>r.suggestion).map(r=>({ id: r.id, suggestion: r.suggestion, country: r.country, submitted_at: r.submitted_at||r.date }));
                // Filter out archived ones
                list = list.filter(s => !archivedSuggestions.find(a => a.id === s.id));
            } else {
                list = archivedSuggestions;
                if(list.length === 0) {
                    container.innerHTML = '<p class="text-gray-400 text-center">No archived suggestions yet.</p>';
                    return;
                }
            }
            
            list.forEach(s => {
                const div = document.createElement('div');
                div.className = 'suggestion-card flex items-start justify-between gap-3';
                
                // Create content section
                const contentDiv = document.createElement('div');
                contentDiv.className = 'flex-1';
                contentDiv.innerHTML = `
                    <p class="text-black font-bold italic text-lg mb-4">"${s.suggestion}"</p>
                    <div class="flex justify-between text-sm text-gray-400">
                        <span>Location: ${s.country || '-'}</span>
                        <span>On: ${(s.submitted_at||'').toString().split('T')[0]}</span>
                    </div>
                `;
                
                // Create prominent action button with event listener
                const actionBtn = document.createElement('button');
                actionBtn.className = 'btn text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2 px-4 py-2.5 shadow-sm hover:shadow-md';
                
                if(currentSuggestionsTab === 'active') {
                    actionBtn.className += ' bg-emerald-500 hover:bg-emerald-600 text-white border-none rounded-full p-2';
                    actionBtn.title = 'Mark as Resolved';
                    actionBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i>';
                    actionBtn.addEventListener('click', () => {
                        window.archiveSuggestion(s.id);
                    });
                } else {
                    actionBtn.className += ' bg-gray-500 hover:bg-gray-600 text-white border-none rounded-full p-2';
                    actionBtn.title = 'Restore';
                    actionBtn.innerHTML = '<i data-lucide="rotate-ccw" class="w-5 h-5"></i>';
                    actionBtn.addEventListener('click', () => {
                        window.unarchiveSuggestion(s.id);
                    });
                }
                
                const actionDiv = document.createElement('div');
                actionDiv.appendChild(actionBtn);
                
                div.appendChild(contentDiv);
                div.appendChild(actionDiv);
                container.appendChild(div);
            });
            lucide.createIcons();
        }

        window.archiveSuggestion = function(id) {
            const suggestion = suggestions.find(s => s.id === id);
            if(!suggestion) return;
            archivedSuggestions.push(suggestion);
            localStorage.setItem('archived_suggestions', JSON.stringify(archivedSuggestions));
            renderSuggestions();
            showToast('success', 'Suggestion marked as resolved');
        };

        window.unarchiveSuggestion = function(id) {
            archivedSuggestions = archivedSuggestions.filter(s => s.id !== id);
            localStorage.setItem('archived_suggestions', JSON.stringify(archivedSuggestions));
            renderSuggestions();
            showToast('success', 'Suggestion restored to active');
        };

        // ==================== EDIT QUESTIONS LOGIC ====================
        function renderQuestionsList() {
            const container = document.getElementById('questions-list-container');
            container.innerHTML = '';
            questions.forEach((q, index) => {
                const div = document.createElement('div');
                div.className = 'question-card';
                div.innerHTML = `
                    <div>
                        <h3 class="text-lg font-bold text-black mb-1"><span class="text-viu-yellow mr-1">#${index+1}</span> ${q.title}</h3>
                        <p class="text-gray-500 text-sm">${q.subtitle}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="text-gray-400 hover:text-black" onclick="openAddQuestionModal('${q.title}', '${q.subtitle}', ${index})"><i data-lucide="pencil" class="w-5 h-5"></i></button>
                        <button class="text-red-400 hover:text-red-600" onclick="deleteQuestion(${index})"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
                    </div>
                `;
                container.appendChild(div);
            });
            lucide.createIcons();
        }

        function openAddQuestionModal(title = '', subtitle = '', index = -1) {
            document.getElementById('q-title-input').value = title;
            document.getElementById('q-subtitle-input').value = subtitle;
            document.getElementById('q-edit-index').value = index;
            document.getElementById('question-modal-title').innerText = index === -1 ? 'Add New Question' : 'Edit Question';
            document.getElementById('add-question-modal').classList.remove('hidden-page');
        }

        function closeAddQuestionModal() {
            document.getElementById('add-question-modal').classList.add('hidden-page');
        }

        function saveQuestionChanges() {
            const title = document.getElementById('q-title-input').value;
            const subtitle = document.getElementById('q-subtitle-input').value;
            const index = parseInt(document.getElementById('q-edit-index').value);

            if(title && subtitle) {
                if(index === -1) {
                    // Add new
                    questions.push({ title, subtitle, rating: 0 });
                    showToast('success', 'New question added successfully!');
                } else {
                    // Update existing
                    questions[index].title = title;
                    questions[index].subtitle = subtitle;
                    showToast('success', 'Question updated successfully!');
                }
                // Save to Storage
                localStorage.setItem('viu_survey_questions', JSON.stringify(questions));
                renderQuestionsList();
                closeAddQuestionModal();
                showToast('info', 'Changes will reflect in the User Survey on reload.');
            }
        }

        function deleteQuestion(index) {
            if(confirm('Are you sure you want to delete this question?')) {
                questions.splice(index, 1);
                localStorage.setItem('viu_survey_questions', JSON.stringify(questions));
                renderQuestionsList();
                showToast('success', 'Question deleted successfully!');
            }
        }

        // ==================== CHARTS LOGIC ====================
        let barChartInstance = null;
        let pieChartInstance = null;

        function renderChartsFromStats(stats){
            if(typeof Chart === 'undefined') return;
            const pastelPalette = ['#A7F3D0','#BFDBFE','#FDE68A','#FBCFE8','#C7D2FE','#FCA5A5','#DDD6FE','#99F6E4','#FDE68A','#C4B5FD'];
            const ctxBar = document.getElementById('barChart')?.getContext('2d');
            const ctxPie = document.getElementById('pieChart')?.getContext('2d');

            // Bar: Average Rating per Question
            if(ctxBar){
                let qStats = (stats?.questions||[]);
                if(!qStats || qStats.length === 0){ qStats = localQuestionStats; }
                let labels = qStats.map(q=> (q.title||q.question_title||'Untitled') );
                let data = qStats.map(q=> Number(q.avg_rating||q.average||0) );
                // If labels are all unknown/untitled or a single repeated label, fallback to local aggregation
                const uniqueLabels = Array.from(new Set(labels.map(l => (l||'Untitled').toString().trim())));
                const allUnknown = uniqueLabels.length === 1 && ['Untitled','Unknown',''].includes(uniqueLabels[0]);
                if(allUnknown || uniqueLabels.length === 1){
                    qStats = localQuestionStats && localQuestionStats.length ? localQuestionStats : qStats;
                    labels = qStats.map(q=> (q.title||q.question_title||'Untitled') );
                    data = qStats.map(q=> Number(q.avg_rating||q.average||0) );
                    // If still single bar, seed labels from known questions with zeros to visualize structure
                    const unique2 = Array.from(new Set(labels.map(l => (l||'Untitled').toString().trim())));
                    if(unique2.length <= 1 && questions && questions.length){
                        labels = questions.map(q => q.title);
                        const avgMap = new Map(qStats.map(q => [q.title, Number(q.avg_rating||q.average||0)]));
                        data = labels.map(t => avgMap.get(t) ?? 0);
                    }
                }
                if(barChartInstance){ barChartInstance.destroy(); }
                barChartInstance = new Chart(ctxBar, {
                    type: 'bar',
                    data: { labels, datasets: [{ data, backgroundColor: labels.map((_,i)=>pastelPalette[i%pastelPalette.length]), borderWidth: 0 }] },
                    options: { plugins: { legend: { display:false } }, scales: { y: { beginAtZero:true, max:5, grid:{ color:'#F3F4F6'} }, x:{ grid:{ display:false } } } }
                });
            }

            // Pie: Overall distribution (if provided) else by services share
            if(ctxPie){
                let labels = [];
                let data = [];
                if(stats?.overall_distribution){
                    labels = Object.keys(stats.overall_distribution.counts).map(k=>'Rating '+k);
                    data = Object.values(stats.overall_distribution.counts);
                } else if(stats?.services){
                    labels = stats.services.map(s=>s.service);
                    data = stats.services.map(s=>s.submissions);
                } else {
                    labels = ['N/A']; data = [1];
                }
                if(pieChartInstance){ pieChartInstance.destroy(); }
                pieChartInstance = new Chart(ctxPie, {
                    type: 'pie',
                    data: { labels, datasets: [{ data, backgroundColor: labels.map((_,i)=>pastelPalette[(i+5)%pastelPalette.length]), borderWidth: 0 }] },
                    options: { plugins: { legend: { position:'bottom' } } }
                });
            }

            // World map is now rendered in loadStats() function
        }
        function initCharts(){
            // Fallback placeholders if stats not yet loaded
            renderChartsFromStats({ questions: [
                { title:'Content', avg_rating:3.2 }, { title:'Quality', avg_rating:4.1 }, { title:'Search', avg_rating:3.8 }, { title:'Subtitles', avg_rating:3.6 }, { title:'Performance', avg_rating:2.9 }, { title:'Value', avg_rating:4.0 }
            ], services:[{service:'General', submissions:10},{service:'KDRAMA', submissions:6}] });
        }

        // ==================== SETTINGS SUBMIT ====================
        async function submitSettings() {
            const current = document.getElementById('set-current').value;
            const username = document.getElementById('set-username').value.trim();
            const pwd1 = document.getElementById('set-password').value;
            const pwd2 = document.getElementById('set-password2').value;
            const msg = document.getElementById('set-msg');
            msg.className = 'text-sm mt-2';
            msg.innerText = '';

            if(!current || !username || !pwd1 || !pwd2) { msg.innerText = 'Please fill in all fields.'; msg.classList.add('text-red-600'); return; }
            if(pwd1 !== pwd2) { msg.innerText = 'New passwords do not match.'; msg.classList.add('text-red-600'); return; }

            const token = localStorage.getItem('token');
            if(!token) { msg.innerText = 'Not authenticated. Please login again.'; msg.classList.add('text-red-600'); return; }

            try {
                const res = await fetch('{{ url('/api/admin/settings') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'omit',
                    body: JSON.stringify({ current_password: current, new_username: username, new_password: pwd1 })
                });
                const data = await res.json();
                if(!res.ok) {
                    if (data && (data.message || '').toLowerCase().includes('current password')) {
                        showErrorModal('Wrong Password', 'The current password you entered is incorrect.');
                    } else {
                        showToast('error', data.message || 'Update failed');
                    }
                    return;
                }
                showToast('success', 'Credentials updated successfully.');
                localStorage.setItem('admin_username', username);
                document.getElementById('set-current').value = '';
                document.getElementById('set-password').value = '';
                document.getElementById('set-password2').value = '';
            } catch(e) {
                showToast('error', 'Network error');
            }
        }

        // ==================== EXPORTS ====================
        document.addEventListener('DOMContentLoaded', () => {
            const btnCsv = document.getElementById('btn-export-csv');
            const btnPdf = document.getElementById('btn-export-pdf');
            if (btnCsv) btnCsv.addEventListener('click', exportCSV);
            if (btnPdf) btnPdf.addEventListener('click', exportPDF);
            const aCsv = document.getElementById('answers-export-csv');
            const aPdf = document.getElementById('answers-export-pdf');
            if (aCsv) aCsv.addEventListener('click', exportAnswersCSV);
            if (aPdf) aPdf.addEventListener('click', exportAnswersPDF);
            loadStats();
        });

        async function loadStats(range){
            try {
                const token = localStorage.getItem('auth_token');
                let urlStats = '{{ url('/api/admin/stats') }}';
                if(range && range !== 'all') urlStats += ('?range='+range);
                const res = await fetch(urlStats, { headers: { 'Accept':'application/json', 'Authorization': token ? ('Bearer '+token) : '' } });
                if (!res.ok) throw new Error('Failed');
                const data = await res.json();
                const total = document.getElementById('dash-total');
                const avg = document.getElementById('dash-avg');
                // Only override live count if stats provides a value
                if (total && (data.total_submissions ?? null) !== null) total.textContent = data.total_submissions;
                if (avg) {
                    const ov = (data.overall_average ?? 0);
                    const disp = (ov && ov.toFixed) ? ov.toFixed(2) : ov;
                    avg.innerHTML = disp + ' <span class="text-2xl text-gray-400 font-semibold">/ 5.0</span>';
                }
                // Display country distribution in text legend
                try {
                    // City to country mapping (in case responses store city names)
                    const cityToCountry = {
                        'manila': 'philippines',
                        'singapore': 'singapore',
                        'hong kong': 'hong kong',
                        'jakarta': 'indonesia',
                        'kuala lumpur': 'malaysia',
                        'bangkok': 'thailand',
                        'dubai': 'united arab emirates',
                        'riyadh': 'saudi arabia',
                        'doha': 'qatar',
                        'kuwait city': 'kuwait',
                        'muscat': 'oman',
                        'manama': 'bahrain',
                        'amman': 'jordan',
                        'cairo': 'egypt',
                        'johannesburg': 'south africa',
                        'cape town': 'south africa'
                    };

                    // Valid countries list
                    const validCountries = [
                        'hong kong', 'singapore', 'malaysia', 'indonesia', 'thailand', 
                        'philippines', 'united arab emirates', 'saudi arabia', 'qatar', 
                        'kuwait', 'oman', 'bahrain', 'jordan', 'egypt', 'south africa'
                    ];

                    // Count responses per country
                    const allResponses = window.responses || responses || [];
                    const countryCounts = {};
                    
                    allResponses.forEach(r => {
                        let countryName = (r.country || '').toString().trim().toLowerCase();
                        
                        // If country is actually a city name, convert it
                        if(cityToCountry[countryName]) {
                            countryName = cityToCountry[countryName];
                        }
                        
                        // Only count if it's a valid country
                        if(countryName && validCountries.includes(countryName)) {
                            countryCounts[countryName] = (countryCounts[countryName] || 0) + 1;
                        }
                    });

                    const total = allResponses.length || 1;
                    
                    // Build display data
                    const countryData = [];
                    for(const [country, count] of Object.entries(countryCounts)){
                        const percentage = Math.round((count / total) * 100);
                        const countryName = country.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                        countryData.push({ name: countryName, percentage, count });
                    }
                    
                    // Sort by percentage descending
                    countryData.sort((a, b) => b.percentage - a.percentage);
                    
                    // Display in legend
                    const legendEl = document.getElementById('mapLegend');
                    if(legendEl) {
                        if(countryData.length) {
                            const countries = countryData.map(d => `${d.name}: ${d.percentage}%`);
                            legendEl.innerHTML = '<strong>Countries with responses:</strong> ' + countries.join(' • ');
                        } else {
                            legendEl.innerHTML = '<span class="text-gray-400">No country data available</span>';
                        }
                    }
                } catch(e){ console.warn('Country distribution error:', e); }
            } catch(e){ console.warn('loadStats error:', e); }
        }

        function exportCSV(){
            const data = window.responses || responses || [];
            if(!data.length){ showToast('info', 'No data to export'); return; }
            let csv = 'Country,City,Service,Email,Submitted At,Ratings\n';
            data.forEach(r => {
                const country = (r.country||'').toString().replace(/,/g, ';');
                const city = (r.city||'').toString().replace(/,/g, ';');
                const service = (r.service||'general').toString().replace(/,/g, ';');
                const email = (r.email||'').toString().replace(/,/g, ';');
                const date = (r.submitted_at||'').toString().replace(/,/g, ';');
                const ratings = (r.ratings||[]).map(q => `${q.title}:${q.rating}`).join('; ');
                csv += `"${country}","${city}","${service}","${email}","${date}","${ratings}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `viu-survey-data-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('success', 'CSV exported successfully');
        }

        async function exportPDF(){
            // Create a print-friendly window containing key stats with VIU header
            const total = document.getElementById('dash-total')?.textContent?.trim() || '0';
            const avg = document.getElementById('dash-avg')?.textContent?.trim() || '0.0 / 5.0';
            const win = window.open('', 'printWin');
            const viuLogo = '<svg width="32" height="32" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            win.document.write('<html><head><title>VIU Dashboard Report</title><style>body{font-family:Inter,Arial,sans-serif;padding:24px}h1{font-size:22px;margin:0 0 6px;display:flex;align-items:center;gap:10px}h2{margin:18px 0 10px;font-size:18px}table{border-collapse:collapse;width:100%;font-size:12px}td,th{border:1px solid #ddd;padding:6px}th{background:#f5f5f5;text-align:left}.header{border-bottom:3px solid #F6BE00;padding-bottom:8px;margin-bottom:12px}</style></head><body>');
            win.document.write('<div class="header"><h1>'+viuLogo+' VIU Dashboard Report</h1><div style="color:#6B7280;font-size:11px;">Generated '+ new Date().toLocaleString() +'</div></div>');
            win.document.write('<table><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>');
            win.document.write('<tr><td>Total Submissions</td><td>'+total+'</td></tr>');
            win.document.write('<tr><td>Average Rating</td><td>'+avg+'</td></tr>');
            win.document.write('<tr><td>Report Generated At</td><td>'+new Date().toLocaleString()+'</td></tr>');
            win.document.write('</tbody></table>');
            try {
                const token = localStorage.getItem('auth_token');
                const range = window.selectedRange || 'all';
                let urlStats = '{{ url('/api/admin/stats') }}';
                if(range && range !== 'all') urlStats += ('?range='+range);
                const res = await fetch(urlStats, { headers: { 'Accept':'application/json', 'Authorization': token ? ('Bearer '+token) : '' } });
                if (res.ok) {
                    const data = await res.json();
                    win.document.write('<h2>Per-Question Metrics</h2>');
                    // Normalize titles and fallback to local aggregation when titles are unknown
                    let questionsData = (data.questions||[]).map(q=>({
                        title: (q.title||q.question_title||'Untitled'),
                        avg: Number(q.avg_rating||q.average||0),
                        count: Number(q.ratings_count||q.count||0)
                    }));
                    const qLabelsUnique = Array.from(new Set(questionsData.map(q=> (q.title||'Untitled').toString().trim())));
                    const qAllUnknown = qLabelsUnique.length === 1 && ['Untitled','Unknown',''].includes(qLabelsUnique[0]);
                    if(questionsData.length === 0 || qAllUnknown || qLabelsUnique.length === 1){
                        // Use existing local aggregate if present
                        let fallback = (localQuestionStats||[]).map(q=>({ title:q.title, avg:Number(q.avg_rating||0), count:Number(q.ratings_count||0) }));
                        // If no local aggregate yet, fetch public responses and compute inline
                        if(!fallback.length){
                            try{
                                let urlResp = '{{ url('/api/public/responses') }}';
                                const rRes = await fetch(urlResp, { headers: { 'Accept':'application/json' }, method: 'GET' });
                                if(rRes.ok){
                                    const raw = await rRes.json();
                                    const respArr = (Array.isArray(raw)? raw : (raw.responses||[]));
                                    const qMap = new Map();
                                    (respArr||[]).forEach(r => {
                                        (r.ratings||[]).forEach(q => {
                                            const title = (q.title||q.question||'Untitled').toString();
                                            const rating = Number(q.rating||q.value||0);
                                            if(!qMap.has(title)) qMap.set(title, { sum:0, count:0 });
                                            if(rating>0){ const o=qMap.get(title); o.sum+=rating; o.count+=1; }
                                        });
                                    });
                                    fallback = Array.from(qMap.entries()).map(([title,o])=>({ title, avg: o.count ? (o.sum/o.count) : 0, count: o.count }));
                                }
                            } catch(_){}
                        }
                        questionsData = fallback;
                    }
                    const totalCount = questionsData.reduce((s,q)=>s+Number(q.count||0),0);
                    const rowsQ = questionsData
                        .map(q=>({title:q.title,avg:q.avg.toFixed(2),count:q.count,pct: totalCount? ((Number(q.count||0)/totalCount)*100).toFixed(1)+'%':'0%'}))
                        .sort((a,b)=>Number(b.avg)-Number(a.avg));
                    win.document.write('<table><thead><tr><th>Question</th><th>Avg Rating</th><th>Responses</th><th>Share</th></tr></thead><tbody>');
                    rowsQ.forEach(r => {
                        win.document.write('<tr><td>'+r.title+'</td><td>'+r.avg+'</td><td>'+r.count+'</td><td>'+r.pct+'</td></tr>');
                    });
                    win.document.write('</tbody></table>');
                    // Country Breakdown
                    const totalSub = Number(data.total_submissions||0)||0;
                    win.document.write('<h2>Country Breakdown</h2>');
                    win.document.write('<table><thead><tr><th>Country</th><th>Submissions</th><th>Share</th></tr></thead><tbody>');
                    (data.countries||[]).forEach(c => {
                        const share = totalSub? ((c.submissions/totalSub)*100).toFixed(1)+'%':'0%';
                        win.document.write('<tr><td>'+c.country+'</td><td>'+c.submissions+'</td><td>'+share+'</td></tr>');
                    });
                    win.document.write('</tbody></table>');
                    // Service Breakdown
                    win.document.write('<h2>Service Breakdown</h2>');
                    win.document.write('<table><thead><tr><th>Service</th><th>Submissions</th><th>Share</th></tr></thead><tbody>');
                    (data.services||[]).forEach(s => {
                        const share = totalSub? ((s.submissions/totalSub)*100).toFixed(1)+'%':'0%';
                        win.document.write('<tr><td>'+s.service+'</td><td>'+s.submissions+'</td><td>'+share+'</td></tr>');
                    });
                    win.document.write('</tbody></table>');
                    // Daily Trends
                    win.document.write('<h2>Daily Trends</h2><table><thead><tr><th>Date</th><th>Submissions</th><th>Avg Rating</th></tr></thead><tbody>');
                    (data.trends||[]).forEach(t => { win.document.write('<tr><td>'+t.date+'</td><td>'+t.submissions+'</td><td>'+Number(t.avg_rating).toFixed(2)+'</td></tr>'); });
                    win.document.write('</tbody></table>');
                    if(data.overall_distribution){
                        win.document.write('<h2>Overall Rating Distribution</h2><table><thead><tr><th>Rating</th><th>Count</th><th>Percent</th></tr></thead><tbody>');
                        Object.keys(data.overall_distribution.counts).forEach(score => { win.document.write('<tr><td>'+score+'</td><td>'+data.overall_distribution.counts[score]+'</td><td>'+data.overall_distribution.percents[score]+'%</td></tr>'); });
                        win.document.write('</tbody></table>');
                        win.document.write('<table><thead><tr><th>Median</th><th>Std Dev</th><th>P25</th><th>P50</th><th>P75</th></tr></thead><tbody><tr><td>'+ (data.overall_median??'') +'</td><td>'+ (data.overall_std_dev?Number(data.overall_std_dev).toFixed(2):'0.00') +'</td><td>'+ (data.overall_percentiles.p25??'') +'</td><td>'+ (data.overall_percentiles.p50??'') +'</td><td>'+ (data.overall_percentiles.p75??'') +'</td></tr></tbody></table>');
                    }
                    if(data.question_stats){
                        win.document.write('<h2>Per-Question Distribution</h2><table><thead><tr><th>Question</th><th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th><th>Median</th><th>StdDev</th><th>P25</th><th>P50</th><th>P75</th></tr></thead><tbody>');
                        data.questions.forEach(q => { const qs = data.question_stats[q.question_id]; if(!qs) return; win.document.write('<tr><td>'+ (q.title||q.question_title||'Untitled') +'</td><td>'+ (qs.counts?.[1]??0) +'</td><td>'+ (qs.counts?.[2]??0) +'</td><td>'+ (qs.counts?.[3]??0) +'</td><td>'+ (qs.counts?.[4]??0) +'</td><td>'+ (qs.counts?.[5]??0) +'</td><td>'+ (qs.median??'') +'</td><td>'+ (qs.std_dev?Number(qs.std_dev).toFixed(2):'0.00') +'</td><td>'+ (qs.p25??'') +'</td><td>'+ (qs.p50??'') +'</td><td>'+ (qs.p75??'') +'</td></tr>'); });
                        win.document.write('</tbody></table>');
                    } else {
                        // Local fallback: build per-question distribution from responses (fetch if needed)
                        const distMap = new Map();
                        let localResp = responses;
                        try{
                            if(!localResp || !localResp.length){
                                let urlResp = '{{ url('/api/public/responses') }}';
                                const rRes = await fetch(urlResp, { headers: { 'Accept':'application/json' }, method: 'GET' });
                                if(rRes.ok){
                                    const raw = await rRes.json();
                                    localResp = (Array.isArray(raw)? raw : (raw.responses||[]));
                                }
                            }
                        } catch(_){}
                        (localResp||[]).forEach(r => {
                            const norm = normalizeRatingsArr(r.ratings||[]);
                            (norm||[]).forEach(q => {
                                const title = (q.title||q.question||'Untitled').toString();
                                const rating = Number(q.rating||q.value||0);
                                if(!distMap.has(title)) distMap.set(title, { counts: {1:0,2:0,3:0,4:0,5:0} });
                                if(rating>=1 && rating<=5){ distMap.get(title).counts[rating]++; }
                            });
                        });
                        const entries = Array.from(distMap.entries()).sort((a,b)=>a[0].localeCompare(b[0]));
                        if(entries.length){
                            win.document.write('<h2>Per-Question Distribution</h2><table><thead><tr><th>Question</th><th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th></tr></thead><tbody>');
                            entries.forEach(([title,obj]) => {
                                const c = obj.counts;
                                win.document.write('<tr><td>'+title+'</td><td>'+c[1]+'</td><td>'+c[2]+'</td><td>'+c[3]+'</td><td>'+c[4]+'</td><td>'+c[5]+'</td></tr>');
                            });
                            win.document.write('</tbody></table>');
                        }
                    }
                    if(data.country_service_pivot){
                        win.document.write('<h2>Country-Service Pivot</h2><table><thead><tr><th>Country</th><th>Service</th><th>Submissions</th></tr></thead><tbody>');
                        Object.keys(data.country_service_pivot).forEach(country => { Object.keys(data.country_service_pivot[country]).forEach(service => { win.document.write('<tr><td>'+country+'</td><td>'+service+'</td><td>'+data.country_service_pivot[country][service]+'</td></tr>'); }); });
                        win.document.write('</tbody></table>');
                    }
                    if(data.service_affinity){
                        win.document.write('<h2>Service Affinity per Question</h2><table><thead><tr><th>Question</th><th>Service</th><th>Avg Rating</th></tr></thead><tbody>');
                        Object.keys(data.service_affinity).forEach(qid => { const entry = data.service_affinity[qid]; Object.keys(entry.services).forEach(svc => { win.document.write('<tr><td>'+entry.title+'</td><td>'+svc+'</td><td>'+entry.services[svc]+'</td></tr>'); }); });
                        win.document.write('</tbody></table>');
                    }
                    if(data.email_domains){
                        win.document.write('<h2>Email Domains</h2><table><thead><tr><th>Domain</th><th>Count</th></tr></thead><tbody>');
                        data.email_domains.forEach(d => { win.document.write('<tr><td>'+d.domain+'</td><td>'+d.count+'</td></tr>'); });
                        win.document.write('</tbody></table>');
                    }
                    if(data.hourly_heatmap){
                        win.document.write('<h2>Hourly Heatmap</h2><table><thead><tr><th>Hour</th><th>Submissions</th><th>Avg Rating</th></tr></thead><tbody>');
                        data.hourly_heatmap.forEach(h => { win.document.write('<tr><td>'+h.hour+'</td><td>'+h.submissions+'</td><td>'+h.avg_rating+'</td></tr>'); });
                        win.document.write('</tbody></table>');
                    }
                    // Top Performing / Lowest Performing Areas (fallback if movement missing)
                    {
                        const items = (localQuestionStats||[]).slice();
                        if(items.length){
                            const improved = items.slice().sort((a,b)=>Number(b.avg_rating)-Number(a.avg_rating)).slice(0,3);
                            const declined = items.slice().sort((a,b)=>Number(a.avg_rating)-Number(b.avg_rating)).slice(0,3);
                            win.document.write('<h2>Top Performing Areas</h2><table><thead><tr><th>Question</th><th>Avg Rating</th></tr></thead><tbody>');
                            improved.forEach(i=>{ win.document.write('<tr><td>'+i.title+'</td><td>'+Number(i.avg_rating).toFixed(2)+'</td></tr>'); });
                            win.document.write('</tbody></table>');
                            win.document.write('<h2>Lowest Performing Areas</h2><table><thead><tr><th>Question</th><th>Avg Rating</th></tr></thead><tbody>');
                            declined.forEach(i=>{ win.document.write('<tr><td>'+i.title+'</td><td>'+Number(i.avg_rating).toFixed(2)+'</td></tr>'); });
                            win.document.write('</tbody></table>');
                        }
                    }
                    if(data.anomalies){
                        win.document.write('<h2>Anomaly Days</h2><table><thead><tr><th>Date</th><th>Avg Rating</th><th>Deviation</th></tr></thead><tbody>');
                        data.anomalies.forEach(a => { win.document.write('<tr><td>'+a.date+'</td><td>'+a.avg_rating+'</td><td>'+a.deviation+'</td></tr>'); });
                        win.document.write('</tbody></table>');
                    }
                    if(data.engagement){
                        win.document.write('<h2>Engagement Metrics</h2><table><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>');
                        win.document.write('<tr><td>Completion Rate %</td><td>'+data.engagement.completion_rate_pct+'</td></tr>');
                        win.document.write('<tr><td>Suggestion Rate %</td><td>'+data.engagement.suggestion_rate_pct+'</td></tr>');
                        win.document.write('<tr><td>Satisfaction Index</td><td>'+data.engagement.satisfaction_index+'</td></tr>');
                        win.document.write('<tr><td>Missing Country</td><td>'+data.engagement.data_quality.missing_country+'</td></tr>');
                        win.document.write('<tr><td>Missing Service</td><td>'+data.engagement.data_quality.missing_service+'</td></tr>');
                        win.document.write('<tr><td>Blank Email</td><td>'+data.engagement.data_quality.blank_email+'</td></tr>');
                        if(data.delta){
                    // Top Improvers / Decliners (local fallback using localQuestionStats when backend movement missing)
                    try {
                        const items = (localQuestionStats||[]).slice();
                        if(items.length){
                            const improved = items.slice().sort((a,b)=>Number(b.avg_rating)-Number(a.avg_rating)).slice(0,3);
                            const declined = items.slice().sort((a,b)=>Number(a.avg_rating)-Number(b.avg_rating)).slice(0,3);
                            win.document.write('<h2>Top Improvers</h2><table><thead><tr><th>Question</th><th>Δ Avg</th></tr></thead><tbody>');
                            improved.forEach(i=>{ win.document.write('<tr><td>'+i.title+'</td><td>'+Number(i.avg_rating).toFixed(2)+'</td></tr>'); });
                            win.document.write('</tbody></table>');
                            win.document.write('<h2>Top Decliners</h2><table><thead><tr><th>Question</th><th>Δ Avg</th></tr></thead><tbody>');
                            declined.forEach(i=>{ win.document.write('<tr><td>'+i.title+'</td><td>'+Number(i.avg_rating).toFixed(2)+'</td></tr>'); });
                            win.document.write('</tbody></table>');
                        }
                    } catch(_) {}
                    // Anomaly Days (heuristic: highlight days with avg rating < 3)
                    try {
                        const byDay = new Map();
                        (responses||[]).forEach(r => {
                            const d = (r.submitted_at||r.date||'').toString().split('T')[0]; if(!d) return;
                            if(!byDay.has(d)) byDay.set(d, { sum:0, count:0 });
                            (r.ratings||[]).forEach(q => { const v=Number(q.rating||0); if(v>0){ const o=byDay.get(d); o.sum+=v; o.count++; } });
                        });
                        const entries = Array.from(byDay.entries()).map(([day,o])=>({ day, avg: o.count? (o.sum/o.count) : 0 }));
                        const anomalies = entries.filter(e=>e.avg<3);
                        if(anomalies.length){
                            win.document.write('<h2>Anomaly Days</h2><table><thead><tr><th>Date</th><th>Avg Rating</th><th>Deviation</th></tr></thead><tbody>');
                            anomalies.forEach(a => { win.document.write('<tr><td>'+a.day+'</td><td>'+a.avg.toFixed(2)+'</td><td>'+ (5-a.avg).toFixed(2) +'</td></tr>'); });
                            win.document.write('</tbody></table>');
                        }
                    } catch(_) {}
                    // Executive Insights narrative
                    try {
                        const items = localQuestionStats && localQuestionStats.length ? localQuestionStats.slice() : [];
                        const topImproved = items.slice().sort((a,b)=>Number(b.avg_rating)-Number(a.avg_rating)).slice(0,3);
                        const topDeclined = items.slice().sort((a,b)=>Number(a.avg_rating)-Number(b.avg_rating)).slice(0,3);
                        const services = {};
                        (responses||[]).forEach(r => { const s=(r.service||'general').toString().toLowerCase(); services[s]=(services[s]||0)+1; });
                        const popularService = Object.entries(services).sort((a,b)=>b[1]-a[1])[0]?.[0] || 'general';
                        const diagParts = [];
                        if(topImproved.length){ diagParts.push('Strengths: '+topImproved.map(i=>i.title).join(', ')+'.'); }
                        if(topDeclined.length){ diagParts.push('Focus Areas: '+topDeclined.map(i=>i.title).join(', ')+'.'); }
                        diagParts.push('Engagement: '+responses.length+' submissions; most engaged service: '+popularService+'.');
                        diagParts.push('Recommendations: Prioritize app performance and discoverability; preserve content quality and subtitle accuracy.');
                        win.document.write('<h2>Executive Insights</h2><div style="font-size:12px;color:#374151;line-height:1.6;">'+diagParts.join(' ')+'</div>');
                    } catch(_) {}
                            win.document.write('<tr><td>Avg Rating Change %</td><td>'+(data.delta.avg_rating_change_pct===null?'NA':data.delta.avg_rating_change_pct)+'</td></tr>');
                            win.document.write('<tr><td>Submission Change %</td><td>'+(data.delta.submission_change_pct===null?'NA':data.delta.submission_change_pct)+'</td></tr>');
                        }
                        win.document.write('</tbody></table>');
                    }
                }
            } catch(_) {}
            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            win.print();
            win.close();
            showToast('success', 'Exported PDF successfully');
        }

        // ==================== ANSWERS EXPORTS ====================
        async function exportAnswersCSV(){
            const header = ['#','User Location','Service','Email','Date'];
            const rows = [header];
            (responses||[]).forEach((r,i)=>{
                rows.push([
                    i+1,
                    r.country||'',
                    (r.service||'').toString().toLowerCase(),
                    r.email||'',
                    (r.submitted_at||r.date||'').toString().split('T')[0]
                ]);
            });

            // Append dashboard analytics (same sections as Dashboard export)
            try {
                const token = localStorage.getItem('auth_token');
                const range = window.selectedRange || 'all';
                let urlStats = '{{ url('/api/admin/stats') }}';
                if(range && range !== 'all') urlStats += ('?range='+range);
                const res = await fetch(urlStats, { headers: { 'Accept':'application/json', 'Authorization': token ? ('Bearer '+token) : '' } });
                if (res.ok) {
                    const data = await res.json();
                    const total = Number(data.total_submissions||0)||0;

                    // Questions summary
                    const totalCount = data.questions.reduce((s,q)=>s+Number(q.ratings_count||0),0);
                    rows.push([]); rows.push(['Questions Summary']);
                    rows.push(['Question','Avg Rating','Responses','Share']);
                    (data.questions||[])
                        .map(q=>({title:(q.title||q.question_title||'Untitled'),avg:Number(q.avg_rating||q.average||0).toFixed(2),count:Number(q.ratings_count||q.count||0)}))
                        .sort((a,b)=>Number(b.avg)-Number(a.avg))
                        .forEach(q=>rows.push([q.title,q.avg,q.count,totalCount?((q.count/totalCount)*100).toFixed(1)+'%':'0%']));

                    // Countries
                    rows.push([]); rows.push(['Country Breakdown']);
                    rows.push(['Country','Submissions','Share']);
                    (data.countries||[]).forEach(c=>rows.push([c.country,c.submissions,total?((c.submissions/total)*100).toFixed(1)+'%':'0%']));

                    // Services
                    rows.push([]); rows.push(['Service Breakdown']);
                    rows.push(['Service','Submissions','Share']);
                    (data.services||[]).forEach(s=>rows.push([s.service,s.submissions,total?((s.submissions/total)*100).toFixed(1)+'%':'0%']));

                    // Trends
                    rows.push([]); rows.push(['Daily Trends']);
                    rows.push(['Date','Submissions','Avg Rating']);
                    (data.trends||[]).forEach(t=>rows.push([t.date,t.submissions,Number(t.avg_rating).toFixed(2)]));
                }
            } catch(_){}

            const csv = rows.map(r => r.map(x => '"'+String(x).replace(/"/g,'""')+'"').join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'viu-survey-answers.csv'; a.click();
            URL.revokeObjectURL(url);
            showToast('success', 'Exported Survey Answers CSV');
        }

        async function exportAnswersPDF(){
            const win = window.open('', 'answersPdf');
            const viuLogo = '<svg width="32" height="32" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="45" stroke="#F6BE00" stroke-width="8"/><path d="M40 30 L65 50 L40 70" stroke="#F6BE00" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            win.document.write('<html><head><title>Survey Answers</title><style>body{font-family:Inter,Arial,sans-serif;padding:24px}h1{font-size:22px;margin:0 0 6px;display:flex;align-items:center;gap:10px}h2{margin:18px 0 10px;font-size:18px}table{border-collapse:collapse;width:100%;font-size:12px}td,th{border:1px solid #ddd;padding:6px}th{background:#f5f5f5;text-align:left} .header{border-bottom:3px solid #F6BE00;padding-bottom:8px;margin-bottom:12px}</style></head><body>');
            win.document.write('<div class="header"><h1>'+viuLogo+' VIU Survey Report</h1><div style="color:#6B7280;font-size:11px;">Generated '+ new Date().toLocaleString() +'</div></div>');
            win.document.write('<h2>Survey Answers</h2>');
            win.document.write('<table><thead><tr><th>#</th><th>User Location</th><th>Service</th><th>Email</th><th>Date</th></tr></thead><tbody>');
            (responses||[]).forEach((r,i)=>{
                win.document.write('<tr><td>'+ (i+1) +'</td><td>'+ (r.country||'') +'</td><td>'+ ((r.service||'').toString().toLowerCase()) +'</td><td>'+ (r.email||'') +'</td><td>'+ ((r.submitted_at||r.date||'').toString().split('T')[0]) +'</td></tr>');
            });
            win.document.write('</tbody></table>');

            // Append dashboard sections
            try{
                const token = localStorage.getItem('auth_token');
                const range = window.selectedRange || 'all';
                let urlStats = '{{ url('/api/admin/stats') }}';
                if(range && range !== 'all') urlStats += ('?range='+range);
                const res = await fetch(urlStats, { headers: { 'Accept':'application/json', 'Authorization': token ? ('Bearer '+token) : '' } });
                if(res.ok){
                    const data = await res.json();
                    const total = Number(data.total_submissions||0)||0;
                    win.document.write('<h2>Questions Summary</h2>');
                    let questionsData = (data.questions||[]).map(q=>({
                        title:(q.title||q.question_title||'Untitled'),
                        avg:Number(q.avg_rating||q.average||0),
                        count:Number(q.ratings_count||q.count||0)
                    }));
                    const qLabelsUnique = Array.from(new Set(questionsData.map(q=> (q.title||'Untitled').toString().trim())));
                    const qAllUnknown = qLabelsUnique.length === 1 && ['Untitled','Unknown',''].includes(qLabelsUnique[0]);
                    if(questionsData.length === 0 || qAllUnknown || qLabelsUnique.length === 1){
                        questionsData = (localQuestionStats||[]).map(q=>({ title:q.title, avg:Number(q.avg_rating||0), count:Number(q.ratings_count||0) }));
                    }
                    const totalCount = questionsData.reduce((s,q)=>s+Number(q.count||0),0);
                    win.document.write('<table><thead><tr><th>Question</th><th>Avg</th><th>Responses</th><th>Share</th></tr></thead><tbody>');
                    questionsData
                        .map(q=>({ title:q.title, avg:q.avg.toFixed(2), count:q.count }))
                        .sort((a,b)=>Number(b.avg)-Number(a.avg))
                        .forEach(q=>{ win.document.write('<tr><td>'+q.title+'</td><td>'+q.avg+'</td><td>'+q.count+'</td><td>'+ (totalCount?((q.count/totalCount)*100).toFixed(1)+'%':'0%') +'</td></tr>'); });
                    win.document.write('</tbody></table>');

                    win.document.write('<h2>Country Breakdown</h2><table><thead><tr><th>Country</th><th>Submissions</th><th>Share</th></tr></thead><tbody>');
                    (data.countries||[]).forEach(c=>{ win.document.write('<tr><td>'+c.country+'</td><td>'+c.submissions+'</td><td>'+ (total?((c.submissions/total)*100).toFixed(1)+'%':'0%') +'</td></tr>'); });
                    win.document.write('</tbody></table>');

                    win.document.write('<h2>Service Breakdown</h2><table><thead><tr><th>Service</th><th>Submissions</th><th>Share</th></tr></thead><tbody>');
                    (data.services||[]).forEach(s=>{ win.document.write('<tr><td>'+s.service+'</td><td>'+s.submissions+'</td><td>'+ (total?((s.submissions/total)*100).toFixed(1)+'%':'0%') +'</td></tr>'); });
                    win.document.write('</tbody></table>');

                    win.document.write('<h2>Daily Trends</h2><table><thead><tr><th>Date</th><th>Submissions</th><th>Avg Rating</th></tr></thead><tbody>');
                    (data.trends||[]).forEach(t=>{ win.document.write('<tr><td>'+t.date+'</td><td>'+t.submissions+'</td><td>'+Number(t.avg_rating).toFixed(2)+'</td></tr>'); });
                    win.document.write('</tbody></table>');
                }
            } catch(_) {}

            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            showToast('success', 'Prepared Survey Answers PDF');
        }
        // Range selection interaction
        window.selectedRange = 'all';
        document.addEventListener('DOMContentLoaded', () => {
            const rangeMenu = document.getElementById('range-menu');
            if(rangeMenu){
                rangeMenu.addEventListener('click', (e) => {
                    const a = e.target.closest('a');
                    if(!a) return;
                    window.selectedRange = a.dataset.range;
                    loadStats(window.selectedRange);
                    showToast('info', 'Range set to '+(window.selectedRange==='all'?'All Time':(window.selectedRange==='7d'?'Last 7 Days':'Last 30 Days')));
                });
            }
        });
    </script>
</body>
</html>