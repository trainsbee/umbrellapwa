<?php
declare(strict_types=1);

/**
 * One client across the pipeline, keyed by the processing id (a processing
 * row and its sale are 1:1 through sales.processing_id).
 *
 *   Lead (processing) -> Signing -> Signed (sales) -> Verification -> Approved (compliance)
 *
 * Before the sale exists there is no name in processing: it comes from the
 * hourly opportunities snapshot (same phone) when available.
 */

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/format.php';

const CLIENT_SIGNED_STATUSES = ['SIGNED', 'THREE_DAYS_WAIT'];
const CLIENT_CANCELLED_STATUSES = ['CANCELLED', 'CANCELATION', 'CANCEL AFTER COMPLIANCE', 'CANCEL AT GCS'];
const COMPLIANCE_CHECKING_STATUSES = ['PENDING_COMPLIANCE', 'PENDING VERIFICATION', 'CHECKING'];
const COMPLIANCE_CANCELLED_STATUSES = ['CANCELLED', 'CANCEL AFTER COMPLIANCE'];

/** Pipeline steps in order: key => label. */
const CLIENT_STEPS = [
    'lead' => 'Lead',
    'signing' => 'Signing',
    'signed' => 'Signed',
    'verification' => 'Verification',
    'approved' => 'Approved',
];

/** "DAVID PEREZ" or "david perez" -> "David Perez". */
function client_name_case(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return $name === '' ? '' : mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
}

/** "Names Surnames" of an embedded user, or ''. */
function client_person(?array $user): string
{
    return $user === null ? '' : trim(($user['names'] ?? '') . ' ' . ($user['surnames'] ?? ''));
}

function client_embed($embed): ?array
{
    if (is_array($embed) && isset($embed[0]) && is_array($embed[0])) {
        return $embed[0];
    }
    return is_array($embed) && $embed !== [] ? $embed : null;
}

/** First time the sale's status timeline reached one of $statuses (UTC ISO), or null. */
function client_timeline_at(array $timeline, array $statuses): ?string
{
    foreach ($timeline as $entry) {
        if (in_array($entry['status'] ?? null, $statuses, true)) {
            return (string) $entry['at'];
        }
    }
    return null;
}

/** Processing id of a sale, for links from sales lists and notifications. */
function client_id_for_sale(string $saleId): ?string
{
    $row = supabase_select('sales', 'select=processing_id&id=eq.' . rawurlencode($saleId) . '&limit=1')[0] ?? null;
    return $row['processing_id'] ?? null;
}

/**
 * Everything the client profile shows, or null when the processing id does
 * not exist.
 */
function client_profile(string $processingId): ?array
{
    $id = rawurlencode($processingId);
    $person = 'names,surnames';
    $data = supabase_select_many([
        'processing' => "processing?select=id,phone,status,program,created_at,transferred_at,creator:created_by($person)&id=eq.$id&limit=1",
        'sale' => 'sales?select=id,client_full_name,status,final_status,us_state,city,country,language,debt,lead_source,created_at,status_timeline,'
            . "agent:assigned_to($person),manager:sales_manager_id($person),"
            . 'compliance(compliance_status,signature_ip,ip_state,ip_city,bank_account_change,bank_account_change_done,legal_protection,created_at,updated_at)'
            . "&processing_id=eq.$id&limit=1",
    ]);
    $processing = $data['processing'][0] ?? null;
    if ($processing === null) {
        return null;
    }
    $sale = $data['sale'][0] ?? null;
    $compliance = client_embed($sale['compliance'] ?? null);
    $timeline = is_array($sale['status_timeline'] ?? null) ? $sale['status_timeline'] : [];

    // Name, state and debt from the sale; before it exists, from opportunities
    $name = client_name_case((string) ($sale['client_full_name'] ?? ''));
    $state = (string) ($sale['us_state'] ?? '');
    $debt = $sale['debt'] ?? null;
    $phoneDigits = preg_replace('/\D+/', '', (string) ($processing['phone'] ?? ''));
    if (($name === '' || $sale === null) && $phoneDigits !== '') {
        $lead = supabase_select('opportunities', 'select=client_name,us_state,total_debt&phone=eq.' . rawurlencode($phoneDigits) . '&order=file_date.desc&limit=1')[0] ?? null;
        $name = $name !== '' ? $name : client_name_case((string) ($lead['client_name'] ?? ''));
        $state = $state !== '' ? $state : (string) ($lead['us_state'] ?? '');
        $debt ??= $lead['total_debt'] ?? null;
    }

    // IP check, same rule as Umbrella's compliance screen: IP state = client state
    $ipState = strtoupper(trim((string) ($compliance['ip_state'] ?? '')));
    $ip = match (true) {
        $compliance === null => ['tone' => 'neutral', 'label' => 'Not checked yet'],
        $ipState === '' => ['tone' => 'neutral', 'label' => 'No IP recorded'],
        $state !== '' && $ipState === strtoupper(trim($state)) => ['tone' => 'green', 'label' => 'Valid · IP in ' . $ipState],
        default => ['tone' => 'red', 'label' => 'Mismatch · IP in ' . $ipState . ($state !== '' ? ', client in ' . strtoupper($state) : '')],
    };

    $accountChange = match (true) {
        $compliance === null => ['tone' => 'neutral', 'label' => 'Not checked yet'],
        !($compliance['bank_account_change'] ?? false) => ['tone' => 'neutral', 'label' => 'Not required'],
        (bool) ($compliance['bank_account_change_done'] ?? false) => ['tone' => 'green', 'label' => 'Required · done'],
        default => ['tone' => 'amber', 'label' => 'Required · pending'],
    };

    return [
        'id' => (string) $processing['id'],
        'sale_id' => $sale['id'] ?? null,
        'name' => $name,
        'phone' => (string) ($processing['phone'] ?? ''),
        'program' => (string) ($processing['program'] ?? ''),
        'state' => strtoupper($state),
        'city' => (string) ($sale['city'] ?? ''),
        'debt' => $debt,
        'language' => (string) ($sale['language'] ?? ''),
        'lead_source' => (string) ($sale['lead_source'] ?? ''),
        'agent' => client_person(client_embed($sale['agent'] ?? null)),
        'manager' => client_person(client_embed($sale['manager'] ?? null)),
        'processor' => client_person(client_embed($processing['creator'] ?? null)),
        'created_at' => (string) $processing['created_at'],
        'ip' => $ip,
        'account_change' => $accountChange,
        'stage' => client_stage((string) ($processing['status'] ?? ''), $sale, $compliance, $timeline, (string) $processing['created_at']),
    ];
}

/**
 * Where the client is: the current status pill and every pipeline step as
 * done / current / failed / upcoming, with the date it happened when known.
 */
function client_stage(string $processingStatus, ?array $sale, ?array $compliance, array $timeline, ?string $createdAt = null): array
{
    $saleStatus = strtoupper((string) ($sale['status'] ?? ''));
    $complianceStatus = strtoupper((string) ($compliance['compliance_status'] ?? ''));
    $signed = in_array($saleStatus, CLIENT_SIGNED_STATUSES, true)
        || ($sale === null && in_array($processingStatus, CLIENT_SIGNED_STATUSES, true))
        || $compliance !== null;

    // Index of the step the client is on, and how it ended if it did
    [$current, $status, $failed] = match (true) {
        $complianceStatus === 'COMPLETED' => [5, ['green', 'Approved'], false],
        in_array($complianceStatus, COMPLIANCE_CANCELLED_STATUSES, true) => [3, ['red', 'Cancelled in verification'], true],
        $compliance !== null => [3, ['accent', 'In verification'], false],
        in_array($saleStatus, CLIENT_CANCELLED_STATUSES, true) => [$signed ? 3 : 1, ['red', status_label($saleStatus)], true],
        $signed => [3, $saleStatus === 'THREE_DAYS_WAIT' || $processingStatus === 'THREE_DAYS_WAIT'
            ? ['amber', 'Signed · CA 3-day wait'] : ['green', 'Signed'], false],
        $saleStatus === 'SIGNING' => [1, ['amber', 'Signing'], false],
        $sale !== null => [1, ['neutral', 'In sales · ' . status_label($saleStatus ?: 'PENDING')], false],
        $processingStatus === 'REJECTED' => [0, ['red', 'Rejected'], true],
        default => [0, ['accent', $processingStatus === 'DOCUMENTS_SENT' ? 'Documents sent' : 'Lead in processing'], false],
    };

    $dates = [
        'lead' => $createdAt,
        'signing' => client_timeline_at($timeline, ['SIGNING']),
        'signed' => client_timeline_at($timeline, CLIENT_SIGNED_STATUSES),
        // Umbrella can create the compliance row before the signature: never show it earlier
        'verification' => isset($compliance['created_at']) ? max((string) $compliance['created_at'], (string) client_timeline_at($timeline, CLIENT_SIGNED_STATUSES)) : null,
        'approved' => $complianceStatus === 'COMPLETED' ? ($compliance['updated_at'] ?? null) : null,
    ];

    $steps = [];
    foreach (array_keys(CLIENT_STEPS) as $i => $key) {
        $steps[$key] = [
            'label' => CLIENT_STEPS[$key],
            'state' => match (true) {
                $i < $current => 'done',
                $i === $current && $failed => 'failed',
                $i === $current => 'current',
                default => 'upcoming',
            },
            'at' => $dates[$key],
        ];
    }
    return ['tone' => $status[0], 'label' => $status[1], 'steps' => $steps];
}
