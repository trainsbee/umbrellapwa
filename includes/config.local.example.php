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

// Leader Board: managers shown as one combined group in "Agents without sales today"
const LEADERBOARD_COMBINED_MANAGERS = ['manager-one', 'manager-two'];
const LEADERBOARD_COMBINED_LABEL = 'Manager One & Manager Two';
