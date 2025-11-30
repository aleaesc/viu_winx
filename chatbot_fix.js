function localBot(text){
    const q = text.toLowerCase().trim();
    
    // Detect Tagalog/Filipino
    const isTagalog = /\b(kamusta|ano|kumusta|salamat|pano|paano|saan|kelan|sino|bakit|mga|lang|naman|talaga|sobra|grabe|diba|kasi|yung|yun|nung|pag|kung|ganun|ganyan|po|opo|oo|ka|mo|ko)\b/i.test(text);
    
    // Greetings
    if(q.includes('hi') || q.includes('hello') || q.includes('hey') || q.includes('kamusta') || q.includes('kumusta') || q.includes('musta') || q.includes('morning') || q.includes('afternoon') || q.includes('sup') || q.includes('yo')) {
        if(isTagalog) {
            return 'Kamusta, Viu Fam! 👋 Ako ang iyong Virtual Assistant! Tanungin mo lang ako tungkol sa survey, Viu shows, o kahit ano! Paano kita matutulungan?';
        }
        return 'Hello, Viu Fam! 👋 Kamusta? I\'m your Virtual Assistant and I\'m here to help! Ask me anything - about the survey, Viu content, or just chat! What\'s up? 😊';
    }
    
    // Survey questions
    if((q.includes('survey') || q.includes('simula')) && (q.includes('start') || q.includes('begin') || q.includes('take') || q.includes('paano') || q.includes('how'))) {
        if(isTagalog) {
            return 'Para magsimula ng survey: 1) I-click ang "Start Survey" sa welcome screen, 2) Piliin ang bansa mo, 3) Accept privacy policy, 4) Piliin favorite genres mo, tapos i-rate mo ang 10 categories! Easy lang, Viu Fam! 💪';
        }
        return 'Easy peasy! To start the survey: 1) Click "Start Survey" on the welcome screen, 2) Select your country, 3) Accept the privacy policy, 4) Choose your favorite genres, then rate your experience across 10 categories! Takes just 5 mins! ⏱️';
    }
    
    if(q.includes('question') || q.includes('tanong')) {
        if(isTagalog) {
            return 'May 10 questions sa survey, Viu Fam! ⭐\n\n1. Video Quality - HD ba?\n2. App Performance - Mabilis ba?\n3. Content Library - Marami bang shows?\n4. Subtitle Quality - Okay ba translation?\n5. User Interface - Ganda ng design?\n6. Search - Madali hanapin shows?\n7. Recommendations - Swak suggestions?\n8. Offline Download - Pwede download?\n9. Customer Support - Helpful ba?\n10. Value for Money - Sulit ba?\n\nRate mo lang 1-5 stars each! Tapos pwede mag-comment kung gusto mo! 💯';
        }
        return 'Great question! The survey covers 10 awesome topics! 🌟\n\n1. Video Quality - Is it crispy HD?\n2. App Performance - Smooth or laggy?\n3. Content Library - Enough variety?\n4. Subtitle Quality - Readable?\n5. User Interface - Pretty design?\n6. Search Functionality - Easy to find?\n7. Recommendations - Good suggestions?\n8. Offline Download - Works well?\n9. Customer Support - Helpful?\n10. Value for Money - Worth it?\n\nJust rate 1-5 stars for each! Plus optional comments at the end! 😎';
    }
    
    // About Viu
    if((q.includes('what') && q.includes('viu')) || q.includes('about viu') || q.includes('ano ang viu')) {
        if(isTagalog) {
            return 'Ako si Viu! 🎉 Pinakamagandang streaming service para sa Asian content! May K-dramas, Thai shows, anime, movies - lahat nandito! Think Netflix pero puro Asian hits! Galing Korea, Japan, Thailand, China - fresh episodes pa! Saan ka pa?! 🔥';
        }
        return 'Viu is like your bestie who knows ALL the best Asian shows! 🎬✨ We\'re the ultimate streaming service for K-dramas, Thai lakorn, anime, Asian movies, and exclusive originals! Think of us as Netflix\'s cool Asian cousin! 😎 Fresh from Korea, Japan, Thailand, and more - we got the tea! ☕';
    }
    
    // Content
    if(q.includes('content') || q.includes('watch') || q.includes('show') || q.includes('movie') || q.includes('palabas') || q.includes('kdrama') || q.includes('korean')) {
        if(isTagalog) {
            return 'Grabe, dami namin! 🤩\n\n📺 Latest K-dramas - kilig to the bones!\n🎬 Asian movies - award-winning pa!\n🎭 Variety shows - super funny!\n🌌 Anime - para sa mga otaku!\n🇹🇭 Thai dramas - LSS sa OST!\n⭐ Viu Originals - exclusive satin!\n\nPopular ngayon: True Beauty, Vincenzo, Hometown Cha-Cha-Cha! Binge-worthy lahat! 🍿';
        }
        return 'Oh man, where do I even start?! 🎉\n\n📺 K-dramas - ALL the feels!\n🎬 Asian cinema - Oscar-worthy stuff!\n🎭 Variety shows - laugh till you cry!\n🌌 Anime - for the culture!\n🇹🇭 Thai dramas - chef\'s kiss!\n⭐ Viu Originals - can\'t find anywhere else!\n\nTrending now: True Beauty, Vincenzo, Hometown Cha-Cha-Cha, My Name! Pure fire! 🔥🍿';
    }
    
    // Countries
    if(q.includes('country') || q.includes('countries') || q.includes('available') || q.includes('where') || q.includes('saan') || q.includes('bansa')) {
        if(isTagalog) {
            return 'Meron kami sa maraming bansa, Viu Fam! 🌏\n\n🇵🇭 Philippines - Kabayan!\n🇭🇰 Hong Kong\n🇸🇬 Singapore\n🇲🇾 Malaysia\n🇮🇩 Indonesia\n🇹🇭 Thailand\n🌍 Middle East pa!\n\nKung nandito ka, pwede ka manood! Swerte mo! 🎉';
        }
        return 'We\'re EVERYWHERE in Asia (and beyond)! 🌏\n\n🇵🇭 Philippines - Mabuhay!\n🇭🇰 Hong Kong\n🇸🇬 Singapore  \n🇲🇾 Malaysia\n🇮🇩 Indonesia\n🇹🇭 Thailand\n🌍 Middle East too!\n\nIf you\'re in any of these places, you\'re in luck! 🍀✨';
    }
    
    // Pricing
    if(q.includes('price') || q.includes('pricing') || q.includes('cost') || q.includes('free') || q.includes('premium') || q.includes('magkano') || q.includes('presyo')) {
        if(isTagalog) {
            return 'May FREE and Premium kami! 💎\n\n✅ FREE - May ads pero okay na rin!\n✅ PREMIUM - Walang ads, HD quality, pwede download, early access!\n\nPresyo: PHP 149/month - mas mura pa sa milk tea! ☕\n\nSulit na sulit! Check viu.com for exact price sayo! 💰';
        }
        return 'We got options for every budget! 💰\n\n✅ FREE - With ads (still awesome!)\n✅ PREMIUM - No ads, HD, downloads, early access!\n\nPricing: PHP 149/month - cheaper than coffee! ☕\n\nTotally worth it! Check viu.com for your region! 🎯';
    }
    
    // Random fun
    if(q.includes('love')) {
        return 'Awww, love you too, Viu Fam! 💕 But not as much as you\'ll love our K-dramas! 😉✨';
    }
    
    if(q.includes('bye') || q.includes('goodbye') || q.includes('paalam')) {
        if(isTagalog) {
            return 'Bye, Viu Fam! 👋 Ingat ka! Balik ka ulit for more K-drama feels! See you! 💛';
        }
        return 'See you later, Viu Fam! 👋 Don\'t be a stranger! Come back for more K-drama tea! 💛✨';
    }
    
    if(q.includes('joke') || q.includes('funny')) {
        return 'Why did the K-drama fan break up with their partner? Because they fell for the second male lead! 😂💔 Classic second lead syndrome! Want more? Watch our variety shows! 🎭';
    }
    
    if(q.includes('thank') || q.includes('salamat')) {
        if(isTagalog) {
            return 'Walang anuman, Viu Fam! 💛 Salamat din sa suporta mo! Keep watching and enjoying! Balik ka ulit ha! 👋✨';
        }
        return 'You\'re so welcome, Viu Fam! 💛 Thanks for being awesome! Keep streaming and stay entertained! Come back anytime! 👋✨';
    }
    
    // Default
    if(isTagalog) {
        return 'Hmm, hindi ko sure kung gets kita pero game ako sumagot! 😄 Tanong mo lang ako about:\n\n📋 Survey - paano magsimula, ano tanong\n📺 Viu shows - K-drama, anime, movies  \n🌏 Available countries\n💎 Premium benefits\n\nO kahit random lang! Chat tayo, Viu Fam! Kamusta? 💛';
    }
    return 'Interesting question! 😄 I\'m here to chat about anything! Try asking me about:\n\n📋 The survey - how to start, what to expect\n📺 Viu content - K-dramas, anime, movies\n🌏 Where Viu is available  \n💎 Premium perks\n\nOr just chat randomly - I\'m fun like that! What\'s on your mind, Viu Fam? ✨';
}
