<?php
declare(strict_types=1);

/**
 * Copy this file to includes/config.local.php (git-ignored) and fill in the
 * real values. Never commit the real file: the repository is public.
 */

// Supabase project of the Umbrella CRM
const SUPABASE_URL = 'https://your-project.supabase.co';

// Server-side key. It never reaches the browser: every query runs in PHP.
const SUPABASE_KEY = 'your-service-role-key';

// Closing Report: username of the manager whose team the report covers
const CLOSING_REPORT_MANAGER = 'manager-username';

// Web Push (VAPID). Generate with: php -r 'require "vendor/autoload.php"; print_r(Minishlink\WebPush\VAPID::createVapidKeys());'
const VAPID_PUBLIC_KEY = 'public-key';
const VAPID_PRIVATE_KEY = 'private-key';
const VAPID_SUBJECT = 'https://pwa.umbrellasync.com';

// Shared secret Supabase sends in X-Webhook-Secret when a notification event happens
const PUSH_WEBHOOK_SECRET = 'long-random-string';

// Optional: Supabase anon (public) key. Turns on live "Online now" in Active users
// (Realtime Presence). It is sent to the browser, so never put the service key here.
const SUPABASE_ANON_KEY = '';
