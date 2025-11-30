<?php

return [
    'kb' => [
        ['q' => 'subscription', 'a' => 'VIU offers multiple subscription plans depending on region.'],
        ['q' => 'download', 'a' => 'You can download selected VIU content on mobile apps.'],
        ['q' => 'support', 'a' => 'Visit the VIU help center for account and billing support.'],
    ],
    'chatbot' => [
        // provider: 'google' for Gemini via Google AI Studio
        'provider' => env('VIU_CHATBOT_PROVIDER', 'google'),
        'api_key' => env('VIU_CHATBOT_API_KEY'),
        // common Gemini models: 'gemini-1.5-flash', 'gemini-1.5-pro'
        'model' => env('VIU_CHATBOT_MODEL', 'gemini-1.5-flash'),
        // Google Generative Language API endpoint for generateContent
        'endpoint' => env('VIU_CHATBOT_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],
];
