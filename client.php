<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/client-data.php';

$user = require_login();

// client.php?id=<processing id>; notifications for a sale use the same id
$id = (string) ($_GET['id'] ?? '');
$client = preg_match('/^[a-f0-9-]{36}$/', $id) ? client_profile($id) : null;
if ($client === null) {
    http_response_code(404);
}

/** "Oct 12, 4:05 PM" in business time. */
function client_when(?string $timestamp): string
{
    if ($timestamp === null || $timestamp === '') {
        return '';
    }
    return (new DateTimeImmutable($timestamp))->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('M j, g:i A');
}

const TONE_ICONS = ['green' => 'check', 'red' => 'x', 'amber' => 'clock', 'accent' => 'loader', 'neutral' => 'minus'];

$pageTitle = 'Client';
$activeNav = '';
require __DIR__ . '/includes/shell-top.php';
?>
<?php if ($client === null): ?>
  <section class="empty-state">
    <?= icon('user-x', 22) ?>
    <h2>Client not found</h2>
    <p>This client does not exist or was removed from Umbrella.</p>
  </section>
<?php else:
    $name = $client['name'] !== '' ? $client['name'] : phone($client['phone']);
    $stage = $client['stage'];
    $place = implode(', ', array_filter([$client['city'], $client['state']]));
?>
  <section class="client-hero">
    <div class="client-cover" aria-hidden="true"></div>
    <span class="client-avatar"><?= e(initials($client['name'] !== '' ? $client['name'] : 'Client')) ?></span>
    <div class="client-head">
      <h1 class="client-name"><?= e($name) ?></h1>
      <p class="client-sub">
        <?php if ($place !== ''): ?><span><?= icon('map-pin', 13) ?> <?= e($place) ?></span><?php endif; ?>
        <span><?= icon('calendar', 13) ?> Added <?= e(client_when($client['created_at'])) ?></span>
      </p>
      <span class="status-chip status-chip--<?= e($stage['tone']) ?>"><?= icon(TONE_ICONS[$stage['tone']], 13) ?> <?= e($stage['label']) ?></span>
    </div>
  </section>

  <div class="client-stats">
    <div class="client-stat"><strong><?= e(money($client['debt'])) ?></strong><span>Debt</span></div>
    <div class="client-stat"><strong><?= e($client['state'] !== '' ? $client['state'] : '—') ?></strong><span>State</span></div>
    <div class="client-stat"><strong><?= e($client['program'] !== '' ? $client['program'] : '—') ?></strong><span>Program</span></div>
  </div>

  <section class="client-card">
    <h2 class="client-card-title">Pipeline</h2>
    <ol class="pipeline">
      <?php foreach ($stage['steps'] as $key => $step): ?>
        <li class="pipeline-step pipeline-step--<?= e($step['state']) ?>">
          <span class="pipeline-dot" aria-hidden="true">
            <?php if ($step['state'] === 'done'): ?><?= icon('check', 12) ?><?php elseif ($step['state'] === 'failed'): ?><?= icon('x', 12) ?><?php endif; ?>
          </span>
          <span class="pipeline-text">
            <strong><?= e($step['label']) ?></strong>
            <small><?= e(match ($step['state']) {
                'done' => $step['at'] ? client_when($step['at']) : 'Done',
                'current' => 'In progress' . ($step['at'] ? ' · since ' . client_when($step['at']) : ''),
                'failed' => 'Cancelled' . ($step['at'] ? ' · ' . client_when($step['at']) : ''),
                default => 'Pending',
            }) ?></small>
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="client-card">
    <h2 class="client-card-title">Client</h2>
    <dl class="client-facts">
      <div><dt><?= icon('user', 14) ?> Name</dt><dd><?= e($client['name'] !== '' ? $client['name'] : 'Not available yet') ?></dd></div>
      <div><dt><?= icon('phone', 14) ?> Phone</dt><dd><?php if ($client['phone'] !== ''): ?><a href="tel:<?= e(preg_replace('/\D+/', '', $client['phone'])) ?>"><?= e(phone($client['phone'])) ?></a><?php else: ?>—<?php endif; ?></dd></div>
      <div><dt><?= icon('layers', 14) ?> Program</dt><dd><?= e($client['program'] !== '' ? $client['program'] : '—') ?></dd></div>
      <div><dt><?= icon('map-pin', 14) ?> State</dt><dd><?= e($place !== '' ? $place : '—') ?></dd></div>
      <?php if ($client['language'] !== ''): ?><div><dt><?= icon('globe', 14) ?> Language</dt><dd><?= e(ucfirst(strtolower($client['language']))) ?></dd></div><?php endif; ?>
      <?php if ($client['lead_source'] !== ''): ?><div><dt><?= icon('radio', 14) ?> Campaign</dt><dd><?= e($client['lead_source']) ?></dd></div><?php endif; ?>
    </dl>
  </section>

  <section class="client-card">
    <h2 class="client-card-title">Team</h2>
    <?php if ($client['processor'] !== ''): ?>
      <!-- The Processing user fills in all of the client's data: shown first -->
      <div class="data-owner">
        <span class="data-owner-avatar"><?= e(initials($client['processor'])) ?></span>
        <span class="data-owner-text">
          <span class="data-owner-role"><?= icon('edit-3', 11) ?> Data entry</span>
          <strong><?= e($client['processor']) ?></strong>
          <small>Filled in the client's data at Processing</small>
        </span>
      </div>
    <?php endif; ?>
    <dl class="client-facts">
      <div><dt><?= icon('briefcase', 14) ?> Sales agent</dt><dd><?= e($client['agent'] !== '' ? $client['agent'] : 'Not assigned yet') ?></dd></div>
      <?php if ($client['manager'] !== ''): ?><div><dt><?= icon('users', 14) ?> Sales manager</dt><dd><?= e($client['manager']) ?></dd></div><?php endif; ?>
    </dl>
  </section>

  <section class="client-card">
    <h2 class="client-card-title">Verification</h2>
    <dl class="client-facts">
      <div><dt><?= icon('wifi', 14) ?> Signature IP</dt><dd><span class="status-chip status-chip--<?= e($client['ip']['tone']) ?>"><?= icon(TONE_ICONS[$client['ip']['tone']], 12) ?> <?= e($client['ip']['label']) ?></span></dd></div>
      <div><dt><?= icon('credit-card', 14) ?> Account change</dt><dd><span class="status-chip status-chip--<?= e($client['account_change']['tone']) ?>"><?= icon(TONE_ICONS[$client['account_change']['tone']], 12) ?> <?= e($client['account_change']['label']) ?></span></dd></div>
    </dl>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
