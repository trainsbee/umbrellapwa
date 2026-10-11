<?php
declare(strict_types=1);

/**
 * Public landing page: invites visitors to install the app and explains
 * what it offers. The installed app opens straight into login.php (manifest
 * start_url), and anyone reaching this page from the app is redirected.
 * Names and figures in the live panel are illustrative, not real data.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/charts.php';

$pageTitle = 'Your entire operation, in the palm of your hand';
$pageCss = ['assets/css/landing.css'];
$bodyClass = 'landing';
$bodyAttrs = ['data-install-auto' => 'off'];

// Live panel: today's activity by hour (illustrative)
$liveWidth = 600;
$liveHeight = 220;
$liveSeries = [
    'leads'      => [18, 26, 22, 38, 34, 52, 47, 61, 58, 72, 69, 84],
    'signatures' => [8, 12, 15, 14, 24, 22, 31, 36, 33, 45, 52, 58],
    'compliance' => [4, 6, 9, 12, 11, 17, 19, 24, 28, 27, 34, 41],
];

// Reporting card: closed vs. lost sales (illustrative)
$reportWidth = 520;
$reportHeight = 150;
$reportSeries = [
    'closed' => [30, 34, 31, 42, 46, 44, 55, 61, 58, 70],
    'lost'   => [48, 44, 46, 40, 37, 39, 33, 29, 31, 24],
];

// Every event arrives with its action ready to run: [icon, type, title, details, action]
$feedEvents = [
    ['user-plus',    'lead',       'New lead',             'Andrea M. · Web form',            'Assign'],
    ['edit-3',       'signing',    'Client signing',       'Contract #4821 · in progress',    'Follow'],
    ['check-circle', 'signed',     'Client signed',        'Contract #4817 · completed',      'View'],
    ['shield',       'compliance', 'Compliance completed', 'Verification approved · #4809',   'Review'],
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
      <a href="#real-time">Real time</a>
      <a href="#notifications">Notifications</a>
      <a href="#reporting">Reporting</a>
      <a href="#technology">Technology</a>
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
      <p class="landing-subtitle reveal">Run your operation from anywhere. Manage leads, signatures, compliance and reporting in one app, with real-time notifications and tools built to act instantly.</p>
      <p class="landing-subtitle landing-subtitle--small reveal">Built on modern technologies, high-performance frameworks and an event-driven architecture, Umbrella brings together automation, security and scale so control of your operation goes wherever you go.</p>
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

    <a class="landing-discover" href="#real-time" aria-label="Discover what Umbrella offers">
      <span>Discover more</span>
      <?= icon('chevron-down', 18) ?>
    </a>
  </section>

  <!-- ============================== REAL TIME ============================== -->
  <section class="landing-section" id="real-time">
    <div class="landing-container">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Real time</span>
        <h2>Know what happens.<br><span class="landing-muted">Act on what matters.</span></h2>
        <p class="landing-subtitle">Every event in your operation reaches the right person at the exact moment, with the action ready to run: assign a lead, follow a signature, close a verification. Without switching screens.</p>
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
    </div>
  </section>

  <!-- ============================ NOTIFICATIONS ============================ -->
  <section class="landing-section landing-section--alt" id="notifications">
    <div class="landing-container">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Push notifications</span>
        <h2>One notification.<br><span class="landing-muted">One decision.</span></h2>
        <p class="landing-subtitle">Native push alerts on your phone and desktop, even when the app is closed. Everyone receives only what concerns them and acts right from the alert.</p>
      </div>
      <div class="landing-events">
        <?php foreach ([
            ['user-plus',    'lead',       'New lead',             'A prospect comes in and the right advisor has it within seconds, ready for first contact.'],
            ['edit-3',       'signing',    'Client signing',       'Follow the signature as it happens. If something stalls, you know before it goes cold.'],
            ['check-circle', 'signed',     'Client signed',        'Contract completed and confirmed for everyone involved, with no back-and-forth emails.'],
            ['shield',       'compliance', 'Compliance completed', 'Every approved verification is recorded and announced. Nobody has to ask.'],
        ] as [$iconName, $type, $title, $text]): ?>
          <article class="landing-card event-<?= $type ?> reveal">
            <span class="landing-card-icon"><?= icon($iconName, 18) ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($text) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============================== REPORTING ============================== -->
  <section class="landing-section" id="reporting">
    <div class="landing-container">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Advanced reporting</span>
        <h2>Clarity to decide.<br><span class="landing-muted">Driven by data, not intuition.</span></h2>
      </div>

      <div class="landing-bento">
        <article class="landing-card landing-card--large reveal">
          <span class="landing-card-icon"><?= icon('activity', 18) ?></span>
          <h3>Closed and lost sales</h3>
          <p>What was won, what was lost and why. Course-correct while it still matters.</p>
          <div class="landing-mini-chart" aria-hidden="true">
            <div class="landing-legend">
              <span class="series-closed">Closed</span>
              <span class="series-lost">Lost</span>
            </div>
            <svg class="landing-chart" viewBox="0 0 <?= $reportWidth ?> <?= $reportHeight ?>" preserveAspectRatio="none">
              <?php foreach ([0.33, 0.66] as $guide): ?>
                <line class="landing-chart-guide" x1="0" x2="<?= $reportWidth ?>" y1="<?= $reportHeight * $guide ?>" y2="<?= $reportHeight * $guide ?>"/>
              <?php endforeach; ?>
              <?php foreach ($reportSeries as $key => $values): $chart = line_chart_paths($values, $reportWidth, $reportHeight); ?>
                <path class="landing-line series-<?= $key ?>" d="<?= $chart['line'] ?>" pathLength="1000"/>
                <path class="landing-pulse series-<?= $key ?>" d="<?= $chart['line'] ?>" pathLength="1000"/>
              <?php endforeach; ?>
            </svg>
          </div>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('award', 18) ?></span>
          <h3>QA reports</h3>
          <p>Quality evaluations by advisor and by call, with clear criteria and tracking over time.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('check-square', 18) ?></span>
          <h3>Verifications</h3>
          <p>What passed compliance, what is pending and what needs attention, in a single view.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('filter', 18) ?></span>
          <h3>Full funnel</h3>
          <p>From lead to signature: where each opportunity moves forward and where it stalls.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('users', 18) ?></span>
          <h3>Team performance</h3>
          <p>Results by advisor, supervisor and team, comparable day by day.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('clock', 18) ?></span>
          <h3>End-of-day close</h3>
          <p>The summary you need when the day ends, ready without opening a spreadsheet.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ========================= TRAINSBEE TECHNOLOGY ========================= -->
  <section class="landing-section landing-section--alt landing-technology" id="technology">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">Trainsbee technology</span>
        <h2>Enterprise-grade infrastructure.<br><span class="landing-muted">Ready to operate at scale.</span></h2>
        <p class="landing-subtitle">Umbrella runs on the Trainsbee platform: private, tailor-made infrastructure built with first-class frameworks and libraries, bot-driven automation and an event-driven architecture that processes every action in real time.</p>
      </div>
      <div class="landing-pillars">
        <?php foreach ([
            ['server',     'Private infrastructure',  'Dedicated, isolated servers for every operation. Your data is never shared with anyone.'],
            ['zap',        'Real-time architecture',  'Event-driven: every lead, signature or verification is processed and delivered the moment it happens.'],
            ['cpu',        'Bot automation',          'Bots working in the background at scale: assigning, verifying, notifying and keeping everything in order.'],
            ['code',       'First-class frameworks',  'Built with the tools, frameworks and libraries trusted by the most demanding engineering teams.'],
            ['layers',     'Limitless scale',         'From a team of ten to an operation of thousands, without changing tools or the way you work.'],
            ['refresh-cw', 'Always up to date',       'Every improvement reaches every device automatically. No downloads, no old versions.'],
        ] as [$iconName, $title, $text]): ?>
          <article class="landing-card reveal">
            <span class="landing-card-icon"><?= icon($iconName, 18) ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($text) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="landing-center reveal">
        <a class="landing-trainsbee-link" href="https://trainsbee.com" target="_blank" rel="noopener">Discover the Trainsbee platform <?= icon('arrow-up-right', 14) ?></a>
      </div>
    </div>
  </section>

  <!-- ============================== DAY TO DAY ============================== -->
  <section class="landing-section">
    <div class="landing-container landing-two-columns">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Designed for every day</span>
        <h2>Simple on the outside.<br><span class="landing-muted">Complete on the inside.</span></h2>
        <p class="landing-subtitle">Designed for your thumb and your desktop. Everything one tap away, nothing to learn. It opens instantly from your home screen, no app store required.</p>
      </div>

      <div class="landing-phone reveal" aria-hidden="true">
        <div class="landing-phone-island"></div>
        <div class="landing-phone-bar"><span class="landing-mini-circle"></span><span class="landing-mini-title">Home</span><span></span></div>
        <div class="landing-phone-body">
          <div class="landing-mini-card"><span class="landing-mini-avatar"></span><span class="landing-mini-lines"><i></i><i></i></span></div>
          <div class="landing-mini-group"><?= str_repeat('<div class="landing-mini-row"><span class="landing-mini-icon"></span><i></i></div>', 4) ?></div>
          <div class="landing-mini-group"><?= str_repeat('<div class="landing-mini-row"><span class="landing-mini-icon"></span><i></i></div>', 3) ?></div>
        </div>
        <div class="landing-phone-nav"><i class="active"></i><i></i><i></i><i></i><i></i></div>
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
    <div class="landing-container landing-two-columns">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Security</span>
        <h2>Your data,<br><span class="landing-muted">right where it belongs.</span></h2>
        <p class="landing-subtitle">Information lives on private servers, never on the phone. If you lose your device, your data stays right where it belongs.</p>
      </div>
      <ul class="landing-list">
        <?php foreach ([
            ['Your Umbrella account', 'The same username and password as always, no new accounts.'],
            ['Nothing stored on the device', 'Data is fetched online; offline, the app shows no information.'],
            ['Encrypted connection', 'All communication travels protected over HTTPS.'],
            ['Role-based access', 'Everyone sees and receives only what concerns them.'],
        ] as [$title, $text]): ?>
          <li class="reveal">
            <span class="landing-check"><?= icon('check', 14) ?></span>
            <div><strong><?= e($title) ?></strong><p><?= e($text) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- ============================== CLOSING CTA ============================== -->
  <section class="landing-section landing-section--alt landing-final">
    <div class="landing-container landing-center reveal">
      <span class="landing-app-icon"><?= icon('logo', 30) ?></span>
      <h2>Umbrella, <span class="landing-gradient">always with you.</span></h2>
      <p class="landing-subtitle">Install it in seconds. Know everything and act instantly, wherever you are.</p>
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
