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

$pageTitle = 'Toda tu operación, en la palma de tu mano';
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
    ['user-plus',    'lead',       'Nuevo lead',            'Andrea M. · Formulario web',    'Asignar'],
    ['edit-3',       'signing',    'Cliente firmando',      'Contrato #4821 · en proceso',   'Seguir'],
    ['check-circle', 'signed',     'Cliente firmó',         'Contrato #4817 · completado',   'Ver'],
    ['shield',       'compliance', 'Compliance completado', 'Verificación aprobada · #4809', 'Revisar'],
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
    <nav class="landing-menu" aria-label="Secciones">
      <a href="#real-time">Tiempo real</a>
      <a href="#notifications">Notificaciones</a>
      <a href="#reporting">Reportería</a>
      <a href="#technology">Tecnología</a>
    </nav>
    <a class="btn" href="login.php">Iniciar sesión</a>
  </div>
</header>

<main>
  <!-- ================================ HERO ================================ -->
  <section class="landing-hero">
    <div class="landing-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="landing-grid" aria-hidden="true"></div>
    <div class="landing-container landing-hero-content">
      <span class="landing-app-icon reveal"><?= icon('logo', 34) ?></span>
      <span class="landing-badge reveal"><i class="landing-live-dot"></i> En tiempo real<span class="landing-hide-small">· Ecosistema Umbrella</span></span>
      <h1 class="reveal">Toda tu operación,<br><span class="landing-gradient">en la palma de tu mano.</span></h1>
      <p class="landing-subtitle reveal">Controla tu operación desde cualquier lugar. Gestiona leads, firmas, compliance y reportería en una sola app, con notificaciones en tiempo real y herramientas diseñadas para actuar al instante.</p>
      <p class="landing-subtitle landing-subtitle--small reveal">Construida con tecnologías modernas, frameworks de alto rendimiento y una arquitectura orientada a eventos, Umbrella une automatización, seguridad y escalabilidad para que el control de tu operación vaya siempre contigo.</p>
      <div class="landing-actions reveal">
        <button type="button" class="btn btn--primary btn--large" data-landing-install id="heroInstall">
          <?= icon('download', 16) ?> Instalar app
        </button>
        <p class="landing-note" data-landing-note hidden></p>
        <a class="link-subtle" href="login.php">o continúa en el navegador</a>
      </div>
      <a class="landing-backed reveal" href="https://trainsbee.com" target="_blank" rel="noopener">
        <?= icon('shield', 13) ?> Infraestructura respaldada por <strong>Trainsbee</strong>
      </a>
    </div>

    <a class="landing-discover" href="#real-time" aria-label="Descubre lo que ofrece">
      <span>Descubre más</span>
      <?= icon('chevron-down', 18) ?>
    </a>
  </section>

  <!-- ============================== REAL TIME ============================== -->
  <section class="landing-section" id="real-time">
    <div class="landing-container">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">En tiempo real</span>
        <h2>Lo que pasa, lo sabes.<br><span class="landing-muted">Lo que importa, lo resuelves.</span></h2>
        <p class="landing-subtitle">Cada evento de tu operación llega en el momento exacto a la persona correcta, con la acción lista para ejecutarse: asignar un lead, acompañar una firma, cerrar una verificación. Sin cambiar de pantalla.</p>
      </div>

      <div class="landing-live reveal" aria-hidden="true">
        <div class="landing-live-bar">
          <span class="landing-live-title">Actividad de hoy</span>
          <span class="landing-live-pill"><i class="landing-live-dot"></i> En vivo</span>
        </div>
        <div class="landing-live-body">
          <div class="landing-live-chart">
            <div class="landing-legend">
              <span class="series-leads">Leads</span>
              <span class="series-signatures">Firmas</span>
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
            <div class="landing-axis"><span>8:00</span><span>11:00</span><span>14:00</span><span>17:00</span><span>Ahora</span></div>
          </div>

          <ul class="landing-feed" data-landing-feed>
            <?php foreach ($feedEvents as [$iconName, $type, $title, $details, $action]): ?>
              <li class="landing-event event-<?= $type ?>">
                <span class="landing-event-icon"><?= icon($iconName, 15) ?></span>
                <span class="landing-event-text"><strong><?= e($title) ?></strong><small><?= e($details) ?></small></span>
                <span class="landing-event-side"><span class="landing-event-time">ahora</span><span class="landing-event-action"><?= e($action) ?></span></span>
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
        <span class="landing-eyebrow">Notificaciones push</span>
        <h2>Una notificación.<br><span class="landing-muted">Una decisión.</span></h2>
        <p class="landing-subtitle">Avisos push nativos en el teléfono y el escritorio, incluso con la app cerrada. Cada persona recibe solo lo que le corresponde y actúa desde el mismo aviso.</p>
      </div>
      <div class="landing-events">
        <?php foreach ([
            ['user-plus',    'lead',       'Nuevo lead',            'El prospecto llega y el asesor indicado lo tiene al segundo, con todo listo para el primer contacto.'],
            ['edit-3',       'signing',    'Cliente firmando',      'Acompaña la firma mientras sucede. Si algo se detiene, lo sabes antes de que se enfríe.'],
            ['check-circle', 'signed',     'Cliente firmado',       'Contrato completado y confirmado para todo el equipo involucrado, sin correos de ida y vuelta.'],
            ['shield',       'compliance', 'Compliance completado', 'Cada verificación aprobada queda registrada y notificada. Nadie tiene que preguntar.'],
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
        <span class="landing-eyebrow">Reportería avanzada</span>
        <h2>Claridad para decidir.<br><span class="landing-muted">Con datos, no con intuición.</span></h2>
      </div>

      <div class="landing-bento">
        <article class="landing-card landing-card--large reveal">
          <span class="landing-card-icon"><?= icon('activity', 18) ?></span>
          <h3>Ventas cerradas y no cerradas</h3>
          <p>Lo que se ganó, lo que se perdió y por qué. Corrige el rumbo mientras todavía importa.</p>
          <div class="landing-mini-chart" aria-hidden="true">
            <div class="landing-legend">
              <span class="series-closed">Cerradas</span>
              <span class="series-lost">No cerradas</span>
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
          <h3>Reportes de QA</h3>
          <p>Evaluaciones de calidad por asesor y por llamada, con criterios claros y seguimiento en el tiempo.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('check-square', 18) ?></span>
          <h3>Verificaciones</h3>
          <p>Qué pasó compliance, qué está pendiente y qué necesita atención, en una sola vista.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('filter', 18) ?></span>
          <h3>Embudo completo</h3>
          <p>Del lead a la firma: dónde avanza cada oportunidad y dónde se detiene.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('users', 18) ?></span>
          <h3>Desempeño por equipo</h3>
          <p>Resultados por asesor, supervisor y equipo, comparables día a día.</p>
        </article>
        <article class="landing-card reveal">
          <span class="landing-card-icon"><?= icon('clock', 18) ?></span>
          <h3>Cierre del día</h3>
          <p>El resumen que necesitas al terminar la jornada, listo sin abrir una hoja de cálculo.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ========================= TRAINSBEE TECHNOLOGY ========================= -->
  <section class="landing-section landing-section--alt landing-technology" id="technology">
    <div class="landing-container">
      <div class="landing-heading landing-center reveal">
        <span class="landing-eyebrow">Tecnología Trainsbee</span>
        <h2>Infraestructura de nivel empresarial.<br><span class="landing-muted">Lista para operar a gran escala.</span></h2>
        <p class="landing-subtitle">Umbrella corre sobre la plataforma de Trainsbee: infraestructura privada y a la medida, construida con frameworks y librerías de primer nivel, automatización con bots y una arquitectura orientada a eventos que procesa cada acción en tiempo real.</p>
      </div>
      <div class="landing-pillars">
        <?php foreach ([
            ['server',     'Infraestructura privada',     'Servidores dedicados y aislados para cada operación. Tus datos no se comparten con nadie.'],
            ['zap',        'Arquitectura en tiempo real', 'Orientada a eventos: cada lead, firma o verificación se procesa y se entrega en el momento en que ocurre.'],
            ['cpu',        'Automatización con bots',     'Bots que trabajan en segundo plano a gran escala: asignan, verifican, notifican y mantienen todo en orden.'],
            ['code',       'Frameworks de primer nivel',  'Construida con las herramientas, frameworks y librerías que usan los equipos de ingeniería más exigentes.'],
            ['layers',     'Escala sin límites',          'De un equipo de diez a una operación de miles, sin cambiar de herramienta ni de forma de trabajar.'],
            ['refresh-cw', 'Siempre actualizada',         'Cada mejora llega sola a todos los dispositivos. Sin descargas, sin versiones viejas.'],
        ] as [$iconName, $title, $text]): ?>
          <article class="landing-card reveal">
            <span class="landing-card-icon"><?= icon($iconName, 18) ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($text) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="landing-center reveal">
        <a class="landing-trainsbee-link" href="https://trainsbee.com" target="_blank" rel="noopener">Conoce la plataforma Trainsbee <?= icon('arrow-up-right', 14) ?></a>
      </div>
    </div>
  </section>

  <!-- ============================== DAY TO DAY ============================== -->
  <section class="landing-section">
    <div class="landing-container landing-two-columns">
      <div class="landing-heading reveal">
        <span class="landing-eyebrow">Diseñada para el día a día</span>
        <h2>Simple por fuera.<br><span class="landing-muted">Completa por dentro.</span></h2>
        <p class="landing-subtitle">Pensada para el pulgar y para el escritorio. Todo a un toque, nada que aprender. Se abre al instante desde tu pantalla de inicio, sin pasar por una tienda de apps.</p>
      </div>

      <div class="landing-phone reveal" aria-hidden="true">
        <div class="landing-phone-island"></div>
        <div class="landing-phone-bar"><span class="landing-mini-circle"></span><span class="landing-mini-title">Inicio</span><span></span></div>
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
        <span class="landing-eyebrow">Dispositivos</span>
        <h2>Una sola app.<br><span class="landing-muted">En todas tus pantallas.</span></h2>
      </div>
      <div class="landing-devices">
        <?php foreach ([
            ['smartphone', 'iPhone',  'Compartir → Agregar a inicio'],
            ['tablet',     'iPad',    'Compartir → Agregar a inicio'],
            ['smartphone', 'Android', 'Un toque en Instalar'],
            ['monitor',    'Windows', 'Instalar desde Edge o Chrome'],
            ['monitor',    'Mac',     'Safari: Archivo → Agregar al Dock'],
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
        <span class="landing-eyebrow">Seguridad</span>
        <h2>Tus datos,<br><span class="landing-muted">donde deben estar.</span></h2>
        <p class="landing-subtitle">La información vive en servidores privados, nunca en el teléfono. Si pierdes el dispositivo, tus datos se quedan donde deben estar.</p>
      </div>
      <ul class="landing-list">
        <?php foreach ([
            ['Tu cuenta Umbrella', 'El mismo usuario y contraseña de siempre, sin cuentas nuevas.'],
            ['Nada guardado en el dispositivo', 'Los datos se consultan en línea; sin conexión, la app no muestra información.'],
            ['Conexión cifrada', 'Toda la comunicación viaja protegida con HTTPS.'],
            ['Acceso por rol', 'Cada persona ve y recibe solo lo que le corresponde.'],
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
      <h2>Umbrella, <span class="landing-gradient">siempre contigo.</span></h2>
      <p class="landing-subtitle">Instálala en segundos. Entérate de todo y actúa al instante, estés donde estés.</p>
      <div class="landing-actions">
        <button type="button" class="btn btn--primary btn--large" data-landing-install><?= icon('download', 16) ?> Instalar app</button>
      </div>
    </div>
  </section>
</main>

<footer class="landing-footer">
  <div class="landing-container">
    <div class="landing-trainsbee reveal">
      <span class="landing-eyebrow">Respaldado por</span>
      <p class="landing-trainsbee-title">Desarrollado con tecnología de <a href="https://trainsbee.com" target="_blank" rel="noopener">Trainsbee</a></p>
      <p class="landing-trainsbee-text">Infraestructura personalizada y privada para empresas, operaciones y particulares a gran escala.</p>
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
  <div class="landing-sticky-brand"><span class="landing-sticky-icon"><?= icon('logo', 18) ?></span><span><strong>UmbrellaApp</strong><small>Tu operación, en tiempo real</small></span></div>
  <button type="button" class="btn btn--primary" data-landing-install tabindex="-1"><?= icon('download', 14) ?> Instalar app</button>
</div>

<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
