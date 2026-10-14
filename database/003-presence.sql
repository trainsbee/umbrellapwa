-- ==================================================================
-- UmbrellaApp PWA update 003: who is using the app.
-- Run once in the Supabase SQL editor (after 002-client-alerts.sql).
--
--  * pwa_presence: last time each user opened a page in the app and on
--    which device. The PWA server writes it at most once every 5 minutes
--    per user (includes/presence.php), never from the browser.
--  * "Online now" does not touch the database: phones join a Supabase
--    Realtime Presence channel while the app is on screen.
-- ==================================================================

create table if not exists public.pwa_presence (
    user_id      uuid primary key references public.users(id) on delete cascade,
    last_seen_at timestamptz not null default now(),
    device       text
);

-- Only the server (service key) reads and writes it
alter table public.pwa_presence enable row level security;
