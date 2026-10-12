-- ==================================================================
-- UmbrellaApp PWA: app access, push notifications and event triggers.
-- Run once in the Supabase SQL editor of the Umbrella project.
--
-- Safe for the CRM:
--  * Only NEW tables (pwa_*) are created; existing tables are untouched
--    except for three AFTER triggers.
--  * The trigger function never raises: if anything fails it simply
--    returns, so inserts and updates in Umbrella can never be blocked.
--  * New tables have Row Level Security enabled and no policies, so only
--    the server (service role) can read or write them.
--
-- Before running, replace the two placeholders at the bottom:
--   __WEBHOOK_URL__     https://pwa.umbrellasync.com/api/push-event.php
--   __WEBHOOK_SECRET__  the PUSH_WEBHOOK_SECRET from includes/config.local.php
-- ==================================================================

create extension if not exists pg_net with schema extensions;

-- Who can use the PWA (admins always can)
create table if not exists public.pwa_access (
    user_id    uuid primary key references public.users(id) on delete cascade,
    granted_by uuid references public.users(id) on delete set null,
    granted_at timestamptz not null default now()
);

-- Which notifications each user receives, and for whom
--   scope: all  = the whole operation
--          team = sales of the agents they manage
--          own  = only their own clients
create table if not exists public.pwa_notification_rules (
    user_id    uuid not null references public.users(id) on delete cascade,
    event      text not null constraint pwa_notification_rules_event_check check (event in (
                   'processing.new', 'sales.signing', 'sales.signed',
                   'compliance.checking', 'compliance.completed', 'compliance.cancelled')),
    scope      text not null default 'all' check (scope in ('all', 'team', 'own')),
    muted      boolean not null default false,
    updated_at timestamptz not null default now(),
    primary key (user_id, event)
);

-- Devices subscribed to push (one row per browser / installed app)
create table if not exists public.pwa_push_subscriptions (
    id           uuid primary key default gen_random_uuid(),
    user_id      uuid not null references public.users(id) on delete cascade,
    endpoint     text not null unique,
    p256dh       text not null,
    auth         text not null,
    user_agent   text,
    created_at   timestamptz not null default now(),
    last_used_at timestamptz
);
create index if not exists pwa_push_subscriptions_user_id_idx on public.pwa_push_subscriptions (user_id);

-- Where the triggers send events
create table if not exists public.pwa_config (
    key   text primary key,
    value text not null
);

alter table public.pwa_access             enable row level security;
alter table public.pwa_notification_rules enable row level security;
alter table public.pwa_push_subscriptions enable row level security;
alter table public.pwa_config             enable row level security;

-- ------------------------------------------------------------------
-- Event trigger: posts {event, id} to the PWA when something happens
-- ------------------------------------------------------------------
create or replace function public.pwa_notify_event()
returns trigger
language plpgsql
security definer
set search_path = public, extensions
as $$
declare
    v_url    text;
    v_secret text;
    v_event  text;
begin
    if tg_table_name = 'processing' then
        -- Only new leads that start in Processing
        if new.status = 'PROCESSING' then
            v_event := 'processing.new';
        end if;
    elsif tg_table_name = 'sales' then
        if new.status = 'SIGNING' and (tg_op = 'INSERT' or old.status is distinct from 'SIGNING') then
            v_event := 'sales.signing';
        elsif new.status in ('SIGNED', 'THREE_DAYS_WAIT')
              and (tg_op = 'INSERT' or old.status is null or old.status not in ('SIGNED', 'THREE_DAYS_WAIT')) then
            v_event := 'sales.signed';
        end if;
    elsif tg_table_name = 'compliance' then
        if new.compliance_status in ('PENDING_COMPLIANCE', 'PENDING VERIFICATION', 'CHECKING')
           and (tg_op = 'INSERT' or old.compliance_status is null
                or old.compliance_status not in ('PENDING_COMPLIANCE', 'PENDING VERIFICATION', 'CHECKING')) then
            v_event := 'compliance.checking';
        elsif new.compliance_status = 'COMPLETED'
           and (tg_op = 'INSERT' or old.compliance_status is distinct from 'COMPLETED') then
            v_event := 'compliance.completed';
        elsif new.compliance_status in ('CANCELLED', 'CANCEL AFTER COMPLIANCE')
           and (tg_op = 'INSERT' or old.compliance_status is null
                or old.compliance_status not in ('CANCELLED', 'CANCEL AFTER COMPLIANCE')) then
            v_event := 'compliance.cancelled';
        end if;
    end if;

    if v_event is null then
        return new;
    end if;

    select value into v_url from public.pwa_config where key = 'webhook_url';
    select value into v_secret from public.pwa_config where key = 'webhook_secret';
    if v_url is null or v_secret is null then
        return new;
    end if;

    -- Asynchronous: queued by pg_net, the CRM transaction does not wait for it
    perform net.http_post(
        url     := v_url,
        body    := jsonb_build_object('event', v_event, 'id', new.id),
        headers := jsonb_build_object('Content-Type', 'application/json', 'X-Webhook-Secret', v_secret)
    );
    return new;
exception when others then
    -- Never block Umbrella because of a notification
    return new;
end;
$$;

drop trigger if exists pwa_processing_notify on public.processing;
create trigger pwa_processing_notify
    after insert on public.processing
    for each row execute function public.pwa_notify_event();

drop trigger if exists pwa_sales_notify on public.sales;
create trigger pwa_sales_notify
    after insert or update of status on public.sales
    for each row execute function public.pwa_notify_event();

drop trigger if exists pwa_compliance_notify on public.compliance;
create trigger pwa_compliance_notify
    after insert or update of compliance_status on public.compliance
    for each row execute function public.pwa_notify_event();

-- ------------------------------------------------------------------
-- Configuration (replace the placeholders)
-- ------------------------------------------------------------------
insert into public.pwa_config (key, value) values
    ('webhook_url', '__WEBHOOK_URL__'),
    ('webhook_secret', '__WEBHOOK_SECRET__')
on conflict (key) do update set value = excluded.value;
