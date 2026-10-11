<?php
declare(strict_types=1);

/**
 * Public landing page: invites visitors to install the app and shows, at a
 * glance, that the whole operation lives in Umbrella 24/7. The installed app
 * opens straight into login.php (manifest start_url), and anyone reaching this
 * page from the app is redirected. Names and figures in the demo panels are
 * illustrative, not real data.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/charts.php';

$pageTitle = 'Your entire operation, in the palm of your hand';
$pageCss = ['assets/css/landing.css'];
$bodyClass = 'landing';
$bodyAttrs = ['data-install-auto' => 'off'];

// Everything Umbrella brings together, one area per row: [area icon, area, [[icon, module], ...]]
$areas = [
    ['briefcase', 'Operations', [
        ['phone-call', 'Processing'], ['edit-3', 'Sales'], ['shield', 'Compliance'],
        ['clipboard', 'Enrollments'], ['repeat', 'Follow-up'], ['credit-card', 'Payments'],
    ]],
    ['award', 'Quality', [
        ['headphones', 'Quality Control'], ['check-square', 'QA Hub'], ['message-circle', 'Coaching'],
    ]],
    ['bar-chart-2', 'Insights', [
        ['pie-chart', 'Reports'], ['trending-up', 'Manager CPA'], ['flag', 'Leader Board'],
        ['target', 'Campaigns'], ['inbox', 'Opportunities'],
    ]],
    ['cpu', 'Automation', [
        ['monitor', 'Agent Monitor'], ['zap', 'Bots'], ['message-square', 'WhatsApp Reports'], ['bell', 'Push Alerts'],
    ]],
    ['lock', 'Control', [
        ['users', 'Users & Roles'], ['file-text', 'Audit Log'], ['activity', 'Live Users'], ['search', 'Global Search'],
    ]],
];

// Live panel: today's activity by hour (illustrative)
$liveWidth = 600;
$liveHeight = 220;
$liveSeries = [
    'leads'      => [18, 26, 22, 38, 34, 52, 47, 61, 58, 72, 69, 84],
    'signatures' => [8, 12, 15, 14, 24, 22, 31, 36, 33, 45, 52, 58],
    'compliance' => [4, 6, 9, 12, 11, 17, 19, 24, 28, 27, 34, 41],
];

// Every event arrives with its action ready to run: [icon, type, title, details, action]
$feedEvents = [
    ['user-plus',    'lead',       'New lead',             'Andrea M. · Web form',          'Assign'],
    ['edit-3',       'signing',    'Client signing',       'Contract #4821 · in progress',  'Follow'],
    ['check-circle', 'signed',     'Client signed',        'Contract #4817 · completed',    'View'],
    ['shield',       'compliance', 'Compliance completed', 'Verification approved · #4809', 'Review'],
];

// Agent monitor demo: [agent, team, state key, state label]
$agents = [
    ['Agent 01', 'US', 'call',      'On call'],
    ['Agent 02', 'HN', 'available', 'Available'],
    ['Agent 03', 'US', 'ringing',   'Ringing'],
    ['Agent 04', 'HN', 'hold',      'On hold'],
    ['Agent 05', 'US', 'paused',    'Paused'],
    ['Agent 06', 'HN', 'away',      'Away'],
];

require __DIR__ . '/includes/head.php';
?>
<script>
  // The landing page is never shown inside the installed app.
  if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) {
    location.replace('login.php');
  }
</script>

<header class="landing-header">
  <div class="landing-container landing-header-row">
    <a class="landing-brand" href="./"><?= icon('logo', 22) ?><span>Umbrella</span></a>
    <nav class="landing-menu" aria-label="Sections">
      <a href="#platform">Platform</a>
      <a href="#live">Live</a>
      <a href="#technology">Technology</a>
      <a href="#security">Security</a>
    </nav>
    <a class="btn" href="login.php">Sign in</a>
  </div>
</header>

<main>
  <!-- ================================ HERO ================================ -->
  <section class="landing-hero">
    <div class="landing-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="landing-grid" aria-hidden="true"></div>
    <div class="landing-container landing-hero-content">
      <span class="landing-app-icon reveal"><?= icon('logo', 34) ?></span>
      <span class="landing-badge reveal"><i class="landing-live-dot"></i> Real time<span class="landing-hide-small">· Umbrella ecosystem</span></span>
      <h1 class="reveal">Your entire operation,<br><span class="landing-gradient">in the palm of your hand.</span></h1>
      <p class="landing-subtitle reveal">Built on modern technologies, high-performance frameworks and an event-driven architecture, Umbrella brings together automation, security and scale so control of your operation goes wherever you go.</p>
      <div class="landing-actions reveal">
        <button type="button" class="btn btn--primary btn--large" data-landing-install id="heroInstall">
          <?= icon('download', 16) ?> Install app
        </button>
        <p class="landing-note" data-landing-note hidden></p>
        <a class="link-subtle" href="login.php">or continue in the browser</a>
      </div>
      <a class="landing-backed reveal" href="https://trainsbee.com" target="_blank" rel="noopener">
        <?= icon('shield', 13) ?> Infrastructure backed by <strong>Trainsbee</strong>
      </a>
    </div>

    <a class="landing-discover" href="#platform" aria-label="Discover what Umbrella brings together">
      <span>Discover more</span>
      <?= icon('chevron-down', 18) ?>
    </a>
  </section>

  <!-- ============================ PLATFORM (HUB) ============================ -->
  <section class="landing-section" id="platform">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">One platform</span>
        <h2>Everything your operation needs.<br><span class="landing-muted">In one place, 24/7.</span></h2>
      </div>

      <div class="landing-marquees reveal" aria-label="Everything Umbrella brings together">
        <?php foreach ($areas as $row => [$groupIcon, $group, $modules]):
            // Repeat the row until it is wide enough, then duplicate it so the loop is seamless
            $items = [];
            while (count($items) < 10) {
                $items = array_merge($items, [[$groupIcon, $group, true]], array_map(fn ($m) => [$m[0], $m[1], false], $modules));
            }
        ?>
          <div class="landing-marquee area-<?= $row ?><?= $row % 2 ? ' landing-marquee--reverse' : '' ?>" style="--duration: <?= 46 + $row * 7 ?>s">
            <ul class="landing-marquee-track">
              <?php foreach ([false, true] as $isCopy): ?>
                <?php foreach ($items as [$itemIcon, $label, $isGroup]): ?>
                  <li class="landing-node<?= $isGroup ? ' landing-node--group' : '' ?>"<?= $isCopy ? ' aria-hidden="true"' : '' ?>><?= icon($itemIcon, 14) ?> <?= e($label) ?></li>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>

        <div class="landing-marquee-core" aria-hidden="true">
          <span class="landing-marquee-glow"></span>
          <span class="landing-app-icon"><?= icon('logo', 30) ?></span>
          <span class="landing-live-pill"><i class="landing-live-dot"></i> 24/7</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================== LIVE ================================== -->
  <section class="landing-section landing-section--alt" id="live">
    <div class="landing-container">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Live</span>
        <h2>Know what your team is doing.<br><span class="landing-muted">Right now.</span></h2>
      </div>

      <div class="landing-live reveal" aria-hidden="true">
        <div class="landing-live-bar">
          <span class="landing-live-title">Today's activity</span>
          <span class="landing-live-pill"><i class="landing-live-dot"></i> Live</span>
        </div>
        <div class="landing-live-body">
          <div class="landing-live-chart">
            <div class="landing-legend">
              <span class="series-leads">Leads</span>
              <span class="series-signatures">Signatures</span>
              <span class="series-compliance">Compliance</span>
            </div>
            <svg class="landing-chart" viewBox="0 0 <?= $liveWidth ?> <?= $liveHeight ?>" preserveAspectRatio="none">
              <defs>
                <?php foreach ($liveSeries as $key => $_): ?>
                  <linearGradient id="area-<?= $key ?>" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" class="stop-<?= $key ?>" stop-opacity=".28"/>
                    <stop offset="100%" class="stop-<?= $key ?>" stop-opacity="0"/>
                  </linearGradient>
                <?php endforeach; ?>
              </defs>
              <?php foreach ([0.25, 0.5, 0.75] as $guide): ?>
                <line class="landing-chart-guide" x1="0" x2="<?= $liveWidth ?>" y1="<?= $liveHeight * $guide ?>" y2="<?= $liveHeight * $guide ?>"/>
              <?php endforeach; ?>
              <?php foreach ($liveSeries as $key => $values): $chart = line_chart_paths($values, $liveWidth, $liveHeight); ?>
                <path class="landing-area" d="<?= $chart['area'] ?>" fill="url(#area-<?= $key ?>)"/>
                <path class="landing-line series-<?= $key ?>" d="<?= $chart['line'] ?>" pathLength="1000"/>
                <path class="landing-pulse series-<?= $key ?>" d="<?= $chart['line'] ?>" pathLength="1000"/>
                <circle class="landing-dot series-<?= $key ?>" cx="<?= $chart['end'][0] - 4 ?>" cy="<?= $chart['end'][1] ?>" r="4"/>
              <?php endforeach; ?>
            </svg>
            <div class="landing-axis"><span>8 AM</span><span>11 AM</span><span>2 PM</span><span>5 PM</span><span>Now</span></div>
          </div>

          <ul class="landing-feed" data-landing-feed>
            <?php foreach ($feedEvents as [$iconName, $type, $title, $details, $action]): ?>
              <li class="landing-event event-<?= $type ?>">
                <span class="landing-event-icon"><?= icon($iconName, 15) ?></span>
                <span class="landing-event-text"><strong><?= e($title) ?></strong><small><?= e($details) ?></small></span>
                <span class="landing-event-side"><span class="landing-event-time">now</span><span class="landing-event-action"><?= e($action) ?></span></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="landing-two-columns landing-team">
        <div class="landing-heading reveal">
          <h2>Who is on a call.<br><span class="landing-muted">Who is available. Who is away.</span></h2>
        </div>
        <div class="landing-monitor reveal" aria-hidden="true">
          <div class="landing-live-bar">
            <span class="landing-live-title">Agent monitor</span>
            <span class="landing-live-pill"><i class="landing-live-dot"></i> Live</span>
          </div>
          <ul class="landing-monitor-list">
            <?php foreach ($agents as [$agent, $team, $stateKey, $stateLabel]): ?>
              <li>
                <span class="landing-mini-avatar"></span>
                <span class="landing-monitor-name"><strong><?= e($agent) ?></strong><small><?= e($team) ?> team</small></span>
                <span class="landing-agent-state state-<?= $stateKey ?>"><i></i><?= e($stateLabel) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================= TRAINSBEE TECHNOLOGY ========================= -->
  <section class="landing-section landing-technology" id="technology">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">Trainsbee technology</span>
        <h2>Enterprise-grade infrastructure.<br><span class="landing-muted">Ready to operate at scale.</span></h2>
      </div>
      <ul class="landing-chips reveal">
        <?php foreach ([
            ['server', 'Private infrastructure'], ['zap', 'Real-time architecture'], ['cpu', 'Bot automation'],
            ['code', 'First-class frameworks'], ['layers', 'Limitless scale'], ['refresh-cw', 'Always up to date'],
        ] as [$iconName, $title]): ?>
          <li class="landing-node landing-node--large"><?= icon($iconName, 16) ?> <?= e($title) ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="landing-center reveal">
        <a class="landing-trainsbee-link" href="https://trainsbee.com" target="_blank" rel="noopener">Discover the Trainsbee platform <?= icon('arrow-up-right', 14) ?></a>
      </div>
    </div>
  </section>

  <!-- ================================ DEVICES ================================ -->
  <section class="landing-section landing-section--alt" id="devices">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">Devices</span>
        <h2>One app.<br><span class="landing-muted">On every screen.</span></h2>
      </div>
      <div class="landing-devices">
        <?php foreach ([
            ['smartphone', 'iPhone',  'Share → Add to Home Screen'],
            ['tablet',     'iPad',    'Share → Add to Home Screen'],
            ['smartphone', 'Android', 'One tap on Install'],
            ['monitor',    'Windows', 'Install from Edge or Chrome'],
            ['monitor',    'Mac',     'Safari: File → Add to Dock'],
        ] as [$iconName, $device, $howTo]): ?>
          <article class="landing-device reveal">
            <span class="landing-card-icon"><?= icon($iconName, 18) ?></span>
            <h3><?= e($device) ?></h3>
            <p><?= e($howTo) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ================================ SECURITY ================================ -->
  <section class="landing-section" id="security">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">Security</span>
        <h2>Your data,<br><span class="landing-muted">right where it belongs.</span></h2>
      </div>
      <ul class="landing-chips reveal">
        <?php foreach ([
            ['key', 'Role-based access'], ['file-text', 'Full audit trail'], ['activity', 'Live user monitoring'],
            ['git-commit', 'Complete case history'], ['lock', 'Encrypted connection'], ['smartphone', 'Nothing stored on the device'],
        ] as [$iconName, $title]): ?>
          <li class="landing-node landing-node--large"><?= icon($iconName, 16) ?> <?= e($title) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- ============================== CLOSING CTA ============================== -->
  <section class="landing-section landing-section--alt landing-final">
    <div class="landing-container landing-center reveal">
      <span class="landing-app-icon"><?= icon('logo', 30) ?></span>
      <h2>Umbrella, <span class="landing-gradient">always with you.</span></h2>
      <div class="landing-actions">
        <button type="button" class="btn btn--primary btn--large" data-landing-install><?= icon('download', 16) ?> Install app</button>
      </div>
    </div>
  </section>
</main>

<footer class="landing-footer">
  <div class="landing-container">
    <div class="landing-trainsbee reveal">
      <span class="landing-eyebrow">Backed by</span>
      <p class="landing-trainsbee-title">Built with technology by <a href="https://trainsbee.com" target="_blank" rel="noopener">Trainsbee</a></p>
      <p class="landing-trainsbee-text">Custom, private infrastructure for companies, operations and individuals at scale.</p>
      <a class="link-subtle" href="https://trainsbee.com" target="_blank" rel="noopener">trainsbee.com <?= icon('arrow-up-right', 12) ?></a>
    </div>
    <div class="landing-footer-row">
      <a class="landing-brand" href="./"><?= icon('logo', 18) ?><span>Umbrella</span></a>
      <span>&copy; <?= date('Y') ?> Umbrella · v<?= e(APP_VERSION) ?></span>
    </div>
  </div>
</footer>

<!-- Sticky install bar: appears once the hero button scrolls out of view -->
<div class="landing-sticky" data-landing-sticky aria-hidden="true">
  <div class="landing-sticky-brand"><span class="landing-sticky-icon"><?= icon('logo', 18) ?></span><span><strong>UmbrellaApp</strong><small>Your operation, in real time</small></span></div>
  <button type="button" class="btn btn--primary" data-landing-install tabindex="-1"><?= icon('download', 14) ?> Install app</button>
</div>

<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
