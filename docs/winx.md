# VIU WINX System Architecture & Flow

This document explains the complete flow of the system — from frontend interactions to backend processing and database persistence — along with functions, features, roles, and operational guidance. It is intended for reporting, onboarding, and troubleshooting.

## Overview

-   Frontend: Blade views and JavaScript widgets in `resources/views` and `public/*.js`.
-   API: Laravel routes in `routes/api.php` and `routes/web.php`.
-   Controllers: Primary request handlers in `app/Http/Controllers`.
-   Services: Chatbot orchestration, AI providers, prompt building, moderation in `app/Services/*`.
-   Models: Eloquent models in `app/Models/*` representing core entities.
-   Auth: Laravel Sanctum for token-based API authentication.
-   Database: Migrations and seeders in `database/migrations` and `database/seeders`.

## End-to-End Request Flow

### 1) User Authentication (API)

1. UI submits login via JS or form to `/api/login`.
2. `routes/api.php` maps POST `/login` → `AuthController@login`.
3. `AuthController@login`:
    - Validates `username` and `password` (`app/Http/Requests/Auth/LoginRequest.php`).
    - Finds `User` by `username` and checks bcrypt via `Hash::check`.
    - Issues Sanctum token on success and returns JSON with user role and token.
4. Frontend stores token (localStorage or in-memory) and uses it for subsequent requests (Authorization: Bearer).

### 2) Admin Dashboard & Views

-   `resources/views/admin.blade.php` renders admin UI and loads stats/responses via JS calling protected APIs.
-   Superadmin is redirected or gains elevated controls for system-level actions (users, surveys, configuration).

### 3) Chatbot Interaction

1. Frontend widget (`public/chatbot.js`) sends a POST to `/api/chatbot/ask` with user message and optional context.
2. `routes/api.php` maps `/chatbot/ask` → `ChatbotController@ask` or similar in `app/Services/ChatbotService.php`.
3. `ChatbotService` orchestrates:
    - Builds prompt via `PromptBuilder` based on domain rules and user context.
    - Uses `AiProviderManager` to select provider (OpenAI/Gemini/Groq) with fallback logic.
    - Optionally runs `ContentModerationService` to flag disallowed content.
4. Returns AI-generated response; frontend renders it in the chat widget.

### 4) Surveys & Public Responses

-   Public endpoints (in `routes/api.php`) accept survey submissions to `PublicSurveyResponse`.
-   Admin endpoints (protected by Sanctum) list questions, collect analytics, and export data.

## API Surface (Key Endpoints)

From `routes/api.php`:

-   Auth:

    -   `POST /api/register` → Register new user (if allowed).
    -   `POST /api/login` → Login and receive Sanctum token.
    -   `POST /api/logout` → Invalidate current token (auth required).
    -   `GET /api/me` → Get current user details (auth required).

-   Chatbot:

    -   `POST /api/chatbot/ask` → Ask the chatbot; returns AI response.

-   Surveys & Public:

    -   `GET /api/public/questions` → Fetch survey questions (public).
    -   `POST /api/public/responses` → Submit public survey response.
    -   `GET /api/admin/stats` → Admin stats (auth: admin/superadmin).

-   Additional routes may exist; verify via `routes/api.php` and `routes/web.php`.

## Controllers & Requests

-   `app/Http/Controllers/Api/AuthController.php`

    -   `register(Request)`
    -   `login(LoginRequest)`
    -   `me(Request)`
    -   `logout(Request)`

-   `app/Http/Requests/Auth/LoginRequest.php`

    -   Validates `username` and `password` and attempts auth.

-   Other controllers in `app/Http/Controllers` handle chatbot, survey CRUD, and admin views.

## Services Architecture

-   `app/Services/ChatbotService.php`

    -   `ask(userContext, message)` → Core entry to process a chatbot query.
    -   Calls `PromptBuilder` and `AiProviderManager`.

-   `app/Services/AiProviderManager.php`

    -   `getResponse(prompt, options)` → Selects provider, handles fallbacks and timeouts.
    -   Providers: OpenAI, Gemini, Groq (configured in `config/services.php`).

-   `app/Services/PromptBuilder.php`

    -   `buildPrompt(domainRules, userContext, input)` → Produces concise, guided prompts with constraints.

-   `app/Services/ContentModerationService.php`

    -   `moderate(input)` → Flags or rewrites content based on policy.

-   `app/Services/ChatKnowledgeService.php`
    -   `search(query)` → Retrieves domain knowledge for augmented responses.

## Models & Database

-   `app/Models/User.php`

    -   Fields: `username`, `email`, `password`, `role`, `country_id`, profile metadata.
    -   Auth via Sanctum; bcrypt-hashed passwords.

-   `app/Models/Country.php`

    -   Fields: `name`, `iso_code`.

-   `app/Models/Survey.php`

    -   Fields: title, description, status, relations to questions.

-   `app/Models/SurveyQuestion.php`, `SurveyQuestionVersion.php`

    -   Versioned question content for auditability.

-   `app/Models/SurveyAnswer.php`, `PublicSurveyResponse.php`
    -   Stores responses with timestamps and linkage to user or anonymous sessions.

### Migrations and Seeders

-   Admin auto-seeding migration: `database/migrations/2025_12_06_140000_seed_admins_on_migrate.php`

    -   Ensures four deterministic admin accounts exist with bcrypt hashes on `php artisan migrate`.
    -   Upserts by `username`; can read `storage/seed/admins.json` if present.

-   `database/seeders/DatabaseSeeder.php`

    -   Calls `CountrySeeder` and may call `AdminUserSeeder` (supports external secrets file in `storage/seed`).

-   Run migrations: `php artisan migrate`.

## Authentication & Authorization

-   Uses Laravel Sanctum.
-   Login expects `username` + bcrypt `password`.
-   Token returned on login; include `Authorization: Bearer <token>` in requests.
-   Roles:
    -   `superadmin`: system-level management (users, configuration).
    -   `admin`: survey management, data view/export.
    -   `user`: limited app features.
    -   `public`: anonymous survey and chatbot access (where allowed).

## Frontend Components

-   `resources/views/admin.blade.php`

    -   Login form; JS fetch to `/api/login`.
    -   Renders stats and responses via protected endpoints.

-   `public/chatbot.js`

    -   Chat UI, sends queries to `/api/chatbot/ask`, shows responses.

-   `public/toast.js`, `public/chatbot.css`, `public/toast.css`
    -   UI feedback and styling.

## Features Matrix

-   Authentication:

    -   Login/logout, current-user retrieval, role-based guarding.

-   Admin:

    -   View survey responses, analytics, manage questions, export data.

-   Superadmin:

    -   Manage admins/users, global configuration, country mappings.

-   Surveys:

    -   Public questions list, response submission, versioned questions.

-   Chatbot:
    -   Multi-provider fallback (OpenAI/Gemini/Groq), moderation, domain prompt rules.

## Operational Notes

-   Setup:

    -   Configure `.env` database.
    -   Run `composer install`; `php artisan migrate`.
    -   See `SETUP.md` for step-by-step local setup.

-   Deployment:

    -   Serverless via Laravel Cloud/Vapor using `vapor.yml`.
    -   VPS + Nginx config in `nginx-viu-winx.conf`.
    -   See `DEPLOYMENT.md` for detailed steps.

-   Troubleshooting:

    -   Invalid credentials: ensure `.env` DB is correct; run `php artisan migrate` to seed admins.
    -   Chatbot errors: check provider API keys in `.env` and `config/services.php`.
    -   500 errors: inspect `storage/logs/laravel.log`.

    ## Deploying on Laravel Cloud (Quick Start)

    -   Prerequisites:

        -   Laravel Cloud/Vapor account and team.
        -   GitHub repo connected (this project).
        -   Managed MySQL (e.g., Aiven) or Laravel Cloud DB configured.
        -   Required secrets (APP_KEY, DB creds, AI provider keys).

    -   Files:

        -   `vapor.yml` already exists; defines build and deploy settings for this app.

    -   Steps:

        1. Install CLI locally:

        ```powershell
        composer global require laravel/vapor-cli
        $env:PATH += ";$((Get-Command composer).Source | Split-Path)\\vendor\\bin"
        vapor login
        ```

        2. Initialize project (if not done):

        ```powershell
        vapor init
        ```

        Choose environment (e.g., `production`) and link to your team. 3. Set environment variables/secrets:

        ```powershell
        vapor env:push production
        ```

        Ensure keys like `APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and AI keys are present. 4. Deploy:

        ```powershell
        vapor deploy production
        ```

        5. Run migrations (first deploy):

        ```powershell
        vapor artisan production migrate --force
        ```

        6. Future deploys:

            - Migrations auto-run via `deploy` hook in `vapor.yml`.

    -   Notes:
        -   Queues/Workers: Add a queue worker in `vapor.yml` if using jobs; otherwise API-only deploy is sufficient.
        -   Storage/Cache: Configure S3/Redis in Laravel Cloud if needed; update `.env` accordingly.
        -   Domains/SSL: Attach your custom domain in the Laravel Cloud dashboard; SSL is handled automatically.

    ### Required Environment Variables (Vapor)

    -   Core:

        -   `APP_ENV=production`
        -   `APP_KEY=base64:...`
        -   `APP_URL=https://your-cloud-domain`

    -   Database:

        -   `DB_CONNECTION=mysql`
        -   `DB_HOST=...`
        -   `DB_PORT=3306`
        -   `DB_DATABASE=...`
        -   `DB_USERNAME=...`
        -   `DB_PASSWORD=...`

    -   Chatbot Providers (optional):
        -   `OPENAI_API_KEY` | `GEMINI_API_KEY` | `GROQ_API_KEY`

## Data Flow Summary

1. UI action → API route (public or protected).
2. Controller validates request → calls Service layer.
3. Services perform business logic, AI calls, and moderation.
4. Models persist/retrieve data; migrations ensure schema.
5. Response returns JSON/UI render; tokens guard protected APIs.

## Verification Checklist

-   Run `php artisan migrate` and confirm four admin accounts exist.
-   Login as `superadmin` and `admin` roles; access dashboards.
-   Submit a chatbot question; receive AI response.
-   Create/answer survey questions; view stats.

---

For deeper code references, see:

-   `routes/api.php`, `routes/web.php`
-   `app/Http/Controllers/Api/AuthController.php`
-   `app/Services/*`
-   `app/Models/*`
-   `database/migrations/*`, `database/seeders/*`
-   `resources/views/*`, `public/*.js`

## Outputs & Artifacts

-   API JSON responses: Consistent envelope with `message`, `data`, and errors on validation failures.
-   Chatbot responses: Text content rendered in chat UI; may include suggested actions.
-   Admin dashboards: Aggregated counts (total users, responses), charts (if enabled), and tabular exports.
-   Exports: CSV/JSON downloads for survey responses and question banks.
-   Logs: Application logs in `storage/logs/laravel.log` with contextual IDs for tracing.

## Detailed Features (Smallest to Biggest)

-   UI Feedback:

    -   Toast notifications via `public/toast.js` for success/error/info.
    -   Loading spinners on API calls; disabled buttons to prevent double-submit.
    -   Form validation messages inline for required fields and format errors.

-   Account & Auth:

    -   Signup (`POST /api/register`): Validates username, email, password requirements; creates `User`.
    -   Login (`POST /api/login`): Username + password; returns Sanctum token and role.
    -   Logout (`POST /api/logout`): Revokes token; frontend clears session.
    -   Me (`GET /api/me`): Returns current authenticated user with role and permissions.
    -   Password storage: Bcrypt-hashed; never returned in responses.
    -   Roles & permissions: Superadmin/admin/user/public with route/middleware gating.

-   Chatbot:

    -   Prompt construction with domain rules and user context.
    -   Multi-provider AI selection with fallback; timeouts and retries.
    -   Content moderation to block disallowed content; safe replies.
    -   Knowledge search to augment answers (if configured).
    -   Conversation history: Lightweight context window for better replies.

-   Surveys:

    -   Public question fetch; supports versions for audit.
    -   Response submission with timestamps and optional user linkage.
    -   Admin analytics: totals per question, response breakdowns, and export.

-   Admin & Superadmin:

    -   User management (superadmin): create/update admin accounts, assign roles.
    -   Country mappings and configuration (superadmin).
    -   Survey management (admin): create/edit questions, publish/unpublish.
    -   Data views: filter, paginate, export survey responses.

-   Security & Privacy:

    -   Sanctum tokens with Bearer auth; CSRF protection on web routes.
    -   Input validation for all endpoints; rate limiting optional via middleware.
    -   Secrets in `.env`; no plaintext credentials in source.

-   Performance & Reliability:
    -   Provider fallback for chatbot; graceful degradation.
    -   Database indexes on key columns (e.g., `users.username`).
    -   Error handling with meaningful messages to UI; log correlation IDs.

## Signup, Login, and Token Lifecycle

1. Signup:

    - Endpoint: `POST /api/register`
    - Body: `{ "username": "...", "email": "...", "password": "..." }`
    - Validates uniqueness and password rules; creates `User` with role `user` by default.

2. Login:

    - Endpoint: `POST /api/login`
    - Body: `{ "username": "...", "password": "..." }`
    - On success: `{ token: "...", user: { id, username, role } }`

3. Token Use:

    - Frontend stores token (e.g., localStorage).
    - Include header: `Authorization: Bearer <token>`.

4. Logout:
    - Endpoint: `POST /api/logout`
    - Revokes token server-side; client clears stored token.

## Roles & Permissions Matrix (Summary)

-   Public:

    -   Read public questions, submit responses, ask chatbot (if open).

-   User:

    -   Same as public plus personal features if enabled.

-   Admin:

    -   Manage surveys and view responses; access admin dashboard APIs.

-   Superadmin:
    -   Manage admins/users, global configs, countries; elevated dashboards.

## Frontend Specifics

-   Views:

    -   `resources/views/admin.blade.php`: Login form, dashboard sections, JS fetch calls.
    -   Other pages as configured in `resources/views`.

-   Scripts:

    -   `public/chatbot.js`: Event listeners, API calls, message rendering.
    -   `public/toast.js`: Simple toast display API.

-   Styles:

    -   `public/chatbot.css`, `public/toast.css`: Base styling for components.

-   UX Behaviors:
    -   Redirects based on role; conditional menu visibility.
    -   Pagination and filtering on admin data lists.

## Data Schemas (Essentials)

-   `users`:

    -   `id`, `username` (unique), `email`, `password` (bcrypt), `role`, `country_id`, timestamps.

-   `countries`:

    -   `id`, `name`, `iso_code`.

-   `surveys`, `survey_questions`, `survey_question_versions`:

    -   Relations for versioned questions tied to surveys.

-   `survey_answers`, `public_survey_responses`:
    -   Stores responses with linkage and timestamps.

## Error Handling & Logging

-   Validation errors return 422 with field-specific messages.
-   Server errors return 500 with a generic message; details in logs.
-   Logs include request IDs and context for AI provider calls when available.

## Environment & Configuration

-   `.env` controls database, cache, mail, queue, and AI provider keys.
-   `config/services.php` holds AI provider configurations.
-   Migrations seed deterministic admins on `php artisan migrate`.

## Limitations & Roadmap

-   Limitations:

    -   Conversation context is limited; not a full chat history store by default.
    -   Exports focus on CSV/JSON; advanced BI integrations are out of scope.

-   Roadmap:
    -   Add richer analytics dashboards (charts, trends).
    -   Persist full chat histories per user/session.
    -   Role-based UI customization and audit logs.
