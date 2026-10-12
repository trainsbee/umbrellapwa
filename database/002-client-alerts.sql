-- ==================================================================
-- UmbrellaApp PWA update 002: client pipeline alerts.
-- Run once in the Supabase SQL editor (after pwa-setup.sql).
--
--  * Adds two compliance alerts: client in verification, and client
--    cancelled in verification.
--  * New-lead alerts only fire for leads created in the PROCESSING status.
-- Existing rules, devices and triggers are kept.
-- ==================================================================

alter table public.pwa_notification_rules drop constraint if exists pwa_notification_rules_event_check;
alter table public.pwa_notification_rules add constraint pwa_notification_rules_event_check check (event in (
    'processing.new', 'sales.signing', 'sales.signed',
    'compliance.checking', 'compliance.completed', 'compliance.cancelled'));

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
