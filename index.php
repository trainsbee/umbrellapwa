<?php
declare(strict_types=1);

/**
 * Landing publica de UmbrellaApp: invita a instalar la app y explica lo
 * que ofrece. La app instalada abre directo en login.php (start_url del
 * manifest), y si alguien llega aqui desde la app, se le redirige.
 * Los nombres y cifras del panel en vivo son ilustrativos, no datos reales.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/charts.php';

$pageTitle = 'Tu operación, en tiempo real';
$pageCss = ['assets/css/landing.css'];
$bodyClass = 'landing';
$bodyAttrs = ['data-install-auto' => 'off'];

// Panel en vivo: actividad de hoy por hora (ilustrativa)
$liveW = 600;
$liveH = 220;
$liveSeries = [
    'leads'      => [18, 26, 22, 38, 34, 52, 47, 61, 58, 72, 69, 84],
    'firmas'     => [8, 12, 15, 14, 24, 22, 31, 36, 33, 45, 52, 58],
    'compliance' => [4, 6, 9, 12, 11, 17, 19, 24, 28, 27, 34, 41],
];

// Tarjeta de reporteria: ventas cerradas vs. no cerradas (ilustrativa)
$repW = 520;
$repH = 150;
$repSeries = [
    'cerradas'    => [30, 34, 31, 42, 46, 44, 55, 61, 58, 70],
    'no-cerradas' => [48, 44, 46, 40, 37, 39, 33, 29, 31, 24],
];

$feed = [
    ['user-plus',    'lead',       'Nuevo lead',            'Andrea M. · Formulario web'],
    ['edit-3',       'firmando',   'Cliente firmando',      'Contrato #4821 · en proceso'],
    ['check-circle', 'firmado',    'Cliente firmó',         'Contrato #4817 · completado'],
    ['shield',       'compliance', 'Compliance completado', 'Verificación aprobada · #4809'],
];

require __DIR__ . '/includes/head.php';
?>
<script>
  // Desde la app instalada no se muestra la landing.
  if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) {
    location.replace('login.php');
  }
</script>

<header class="landing-cabecera">
  <div class="landing-contenedor landing-cabecera-fila">
    <a class="landing-marca" href="./"><?= icon('logo', 22) ?><span>Umbrella</span></a>
    <nav class="landing-menu" aria-label="Secciones">
      <a href="#tiempo-real">Tiempo real</a>
      <a href="#notificaciones">Notificaciones</a>
      <a href="#reporteria">Reportería</a>
      <a href="#seguridad">Seguridad</a>
    </nav>
    <a class="btn" href="login.php">Iniciar sesión</a>
  </div>
</header>

<main>
  <!-- ============================ PRIMERA VISTA ============================ -->
  <section class="landing-heroe">
    <div class="landing-resplandor" aria-hidden="true"></div>
    <div class="landing-contenedor landing-heroe-texto">
      <span class="landing-icono-app revelar"><?= icon('logo', 34) ?></span>
      <span class="landing-etiqueta revelar"><i class="landing-punto-vivo"></i> En tiempo real<span class="landing-ocultar-chico">· Ecosistema Umbrella</span></span>
      <h1 class="revelar">Cada lead. Cada firma.<br><span class="landing-degradado">En el instante.</span></h1>
      <p class="landing-bajada revelar">Leads, contratos, compliance y reportería avanzada en una sola app. Te avisa en el momento exacto en que algo sucede, en cualquier dispositivo, a cualquier escala.</p>
      <div class="landing-acciones revelar">
        <button type="button" class="btn btn--primario btn--grande" data-landing-install id="instalarHeroe">
          <?= icon('download', 16) ?> Instalar app
        </button>
        <p class="landing-nota" data-landing-nota hidden></p>
        <a class="enlace-suave" href="login.php">o continúa en el navegador</a>
      </div>
    </div>

    <a class="landing-descubre" href="#tiempo-real" aria-label="Descubre lo que ofrece">
      <span>Descubre más</span>
      <?= icon('chevron-down', 18) ?>
    </a>
  </section>

  <!-- ============================ EN TIEMPO REAL ============================ -->
  <section class="landing-seccion" id="tiempo-real">
    <div class="landing-contenedor">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">En tiempo real</span>
        <h2>Lo sabes en el momento en que pasa.<br><span class="landing-tenue">No al final del día.</span></h2>
        <p class="landing-bajada">Cada evento de tu operación viaja al instante a las personas correctas: el lead que acaba de entrar, el cliente que está firmando, la verificación que se aprobó.</p>
      </div>

      <div class="landing-vivo revelar" aria-hidden="true">
        <div class="landing-vivo-barra">
          <span class="landing-vivo-titulo">Actividad de hoy</span>
          <span class="landing-pildora-vivo"><i class="landing-punto-vivo"></i> En vivo</span>
        </div>
        <div class="landing-vivo-cuerpo">
          <div class="landing-vivo-grafica">
            <div class="landing-leyenda">
              <span class="serie-leads">Leads</span>
              <span class="serie-firmas">Firmas</span>
              <span class="serie-compliance">Compliance</span>
            </div>
            <svg class="landing-grafica" viewBox="0 0 <?= $liveW ?> <?= $liveH ?>" preserveAspectRatio="none">
              <defs>
                <?php foreach ($liveSeries as $key => $_): ?>
                  <linearGradient id="area-<?= $key ?>" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" class="stop-<?= $key ?>" stop-opacity=".28"/>
                    <stop offset="100%" class="stop-<?= $key ?>" stop-opacity="0"/>
                  </linearGradient>
                <?php endforeach; ?>
              </defs>
              <?php foreach ([0.25, 0.5, 0.75] as $g): ?>
                <line class="landing-grafica-guia" x1="0" x2="<?= $liveW ?>" y1="<?= $liveH * $g ?>" y2="<?= $liveH * $g ?>"/>
              <?php endforeach; ?>
              <?php foreach ($liveSeries as $key => $values): $c = line_chart_paths($values, $liveW, $liveH); ?>
                <path class="landing-area" d="<?= $c['area'] ?>" fill="url(#area-<?= $key ?>)"/>
                <path class="landing-linea serie-<?= $key ?>" d="<?= $c['line'] ?>" pathLength="1000"/>
                <path class="landing-pulso serie-<?= $key ?>" d="<?= $c['line'] ?>" pathLength="1000"/>
                <circle class="landing-punto serie-<?= $key ?>" cx="<?= $c['end'][0] - 4 ?>" cy="<?= $c['end'][1] ?>" r="4"/>
              <?php endforeach; ?>
            </svg>
            <div class="landing-eje"><span>8:00</span><span>11:00</span><span>14:00</span><span>17:00</span><span>Ahora</span></div>
          </div>

          <ul class="landing-feed" data-landing-feed>
            <?php foreach ($feed as [$ic, $tipo, $titulo, $detalle]): ?>
              <li class="landing-evento evento-<?= $tipo ?>">
                <span class="landing-evento-icono"><?= icon($ic, 15) ?></span>
                <span class="landing-evento-texto"><strong><?= e($titulo) ?></strong><small><?= e($detalle) ?></small></span>
                <span class="landing-evento-hora">ahora</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================ NOTIFICACIONES ============================ -->
  <section class="landing-seccion landing-seccion--alterna" id="notificaciones">
    <div class="landing-contenedor">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Notificaciones push</span>
        <h2>Tu operación te habla.<br><span class="landing-tenue">Aunque la app esté cerrada.</span></h2>
        <p class="landing-bajada">Notificaciones nativas en el teléfono y el escritorio para los momentos que mueven el negocio. Cada persona recibe solo lo que le corresponde.</p>
      </div>
      <div class="landing-eventos">
        <?php foreach ([
            ['user-plus',    'lead',       'Nuevo lead',            'Entra un prospecto y el asesor indicado lo sabe al segundo, con sus datos listos para el primer contacto.'],
            ['edit-3',       'firmando',   'Cliente firmando',      'Sigue la firma mientras sucede y acompaña al cliente en el momento justo.'],
            ['check-circle', 'firmado',    'Cliente firmado',       'El contrato se completa y todo el equipo involucrado recibe la confirmación.'],
            ['shield',       'compliance', 'Compliance completado', 'Cada verificación aprobada queda registrada y notificada, sin perseguir a nadie.'],
        ] as [$ic, $tipo, $titulo, $texto]): ?>
          <article class="landing-tarjeta evento-<?= $tipo ?> revelar">
            <span class="landing-tarjeta-icono"><?= icon($ic, 18) ?></span>
            <h3><?= e($titulo) ?></h3>
            <p><?= e($texto) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============================== REPORTERIA ============================== -->
  <section class="landing-seccion" id="reporteria">
    <div class="landing-contenedor">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Reportería avanzada</span>
        <h2>Decisiones con datos.<br><span class="landing-tenue">No con intuición.</span></h2>
      </div>

      <div class="landing-bento">
        <article class="landing-tarjeta landing-tarjeta--grande revelar">
          <span class="landing-tarjeta-icono"><?= icon('activity', 18) ?></span>
          <h3>Ventas cerradas y no cerradas</h3>
          <p>Compara lo que se ganó con lo que se perdió, entiende por qué y corrige el rumbo mientras todavía importa.</p>
          <div class="landing-mini-grafica" aria-hidden="true">
            <div class="landing-leyenda">
              <span class="serie-cerradas">Cerradas</span>
              <span class="serie-no-cerradas">No cerradas</span>
            </div>
            <svg class="landing-grafica" viewBox="0 0 <?= $repW ?> <?= $repH ?>" preserveAspectRatio="none">
              <?php foreach ([0.33, 0.66] as $g): ?>
                <line class="landing-grafica-guia" x1="0" x2="<?= $repW ?>" y1="<?= $repH * $g ?>" y2="<?= $repH * $g ?>"/>
              <?php endforeach; ?>
              <?php foreach ($repSeries as $key => $values): $c = line_chart_paths($values, $repW, $repH); ?>
                <path class="landing-linea serie-<?= $key ?>" d="<?= $c['line'] ?>" pathLength="1000"/>
                <path class="landing-pulso serie-<?= $key ?>" d="<?= $c['line'] ?>" pathLength="1000"/>
              <?php endforeach; ?>
            </svg>
          </div>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('award', 18) ?></span>
          <h3>Reportes de QA</h3>
          <p>Evaluaciones de calidad por asesor y por llamada, con criterios claros y seguimiento en el tiempo.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('check-square', 18) ?></span>
          <h3>Verificaciones</h3>
          <p>Qué pasó compliance, qué está pendiente y qué necesita atención, en una sola vista.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('filter', 18) ?></span>
          <h3>Embudo completo</h3>
          <p>Del lead a la firma: dónde avanza cada oportunidad y dónde se detiene.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('users', 18) ?></span>
          <h3>Desempeño por equipo</h3>
          <p>Resultados por asesor, supervisor y equipo, comparables día a día.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('clock', 18) ?></span>
          <h3>Cierre del día</h3>
          <p>El resumen que necesitas al terminar la jornada, listo sin abrir una hoja de cálculo.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ================================ ESCALA ================================ -->
  <section class="landing-seccion landing-seccion--alterna" id="escala">
    <div class="landing-contenedor">
      <div class="landing-encabezado landing-centrado revelar">
        <span class="landing-sobretitulo">Infraestructura</span>
        <h2>Construida para escalar.<br><span class="landing-tenue">En tiempo real, sin pausas.</span></h2>
        <p class="landing-bajada">Una arquitectura orientada a eventos sobre tecnología de punta: lo que ocurre en tu operación se procesa y se entrega al instante, sea un equipo de diez personas o una operación de miles.</p>
      </div>
      <div class="landing-pilares">
        <?php foreach ([
            ['zap',        'Eventos al instante',     'Cada lead, firma o verificación se propaga a los dispositivos autorizados en el momento en que ocurre.'],
            ['layers',     'Escala horizontal',       'La plataforma crece con tu operación: más usuarios, más equipos y más volumen sin cambiar de herramienta.'],
            ['server',     'Infraestructura privada', 'Servidores dedicados y aislados para tu empresa. Tus datos no se comparten con nadie.'],
            ['refresh-cw', 'Siempre actualizada',     'Cada mejora llega sola a todos los dispositivos, sin descargas ni versiones viejas.'],
        ] as [$ic, $titulo, $texto]): ?>
          <article class="landing-tarjeta revelar">
            <span class="landing-tarjeta-icono"><?= icon($ic, 18) ?></span>
            <h3><?= e($titulo) ?></h3>
            <p><?= e($texto) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============================== DIA A DIA ============================== -->
  <section class="landing-seccion">
    <div class="landing-contenedor landing-dos-columnas">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Diseñada para el día a día</span>
        <h2>Simple por fuera.<br><span class="landing-tenue">Completa por dentro.</span></h2>
        <p class="landing-bajada">Una navegación pensada para el pulgar, un menú que lo tiene todo a un toque y una interfaz que se adapta a tu pantalla, del teléfono al escritorio. Abre al instante desde tu pantalla de inicio, sin tiendas de apps.</p>
      </div>

      <div class="landing-telefono revelar" aria-hidden="true">
        <div class="landing-telefono-isla"></div>
        <div class="landing-telefono-barra"><span class="landing-mini-circulo"></span><span class="landing-mini-titulo">Inicio</span><span></span></div>
        <div class="landing-telefono-cuerpo">
          <div class="landing-mini-tarjeta"><span class="landing-mini-avatar"></span><span class="landing-mini-lineas"><i></i><i></i></span></div>
          <div class="landing-mini-grupo"><?= str_repeat('<div class="landing-mini-fila"><span class="landing-mini-icono"></span><i></i></div>', 4) ?></div>
          <div class="landing-mini-grupo"><?= str_repeat('<div class="landing-mini-fila"><span class="landing-mini-icono"></span><i></i></div>', 3) ?></div>
        </div>
        <div class="landing-telefono-nav"><i class="activa"></i><i></i><i></i><i></i><i></i></div>
      </div>
    </div>
  </section>

  <!-- ============================= DISPOSITIVOS ============================= -->
  <section class="landing-seccion landing-seccion--alterna" id="dispositivos">
    <div class="landing-contenedor">
      <div class="landing-encabezado landing-centrado revelar">
        <span class="landing-sobretitulo">Dispositivos</span>
        <h2>Una sola app.<br><span class="landing-tenue">En todas tus pantallas.</span></h2>
      </div>
      <div class="landing-dispositivos">
        <?php foreach ([
            ['smartphone', 'iPhone',  'Compartir → Agregar a inicio'],
            ['tablet',     'iPad',    'Compartir → Agregar a inicio'],
            ['smartphone', 'Android', 'Un toque en Instalar'],
            ['monitor',    'Windows', 'Instalar desde Edge o Chrome'],
            ['monitor',    'Mac',     'Safari: Archivo → Agregar al Dock'],
        ] as [$ic, $nombre, $como]): ?>
          <article class="landing-dispositivo revelar">
            <span class="landing-tarjeta-icono"><?= icon($ic, 18) ?></span>
            <h3><?= e($nombre) ?></h3>
            <p><?= e($como) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============================== SEGURIDAD ============================== -->
  <section class="landing-seccion" id="seguridad">
    <div class="landing-contenedor landing-dos-columnas">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Seguridad</span>
        <h2>Tus datos,<br><span class="landing-tenue">donde deben estar.</span></h2>
        <p class="landing-bajada">La información vive en servidores privados, no en el teléfono. Si pierdes el dispositivo, tus datos no se van con él.</p>
      </div>
      <ul class="landing-lista">
        <?php foreach ([
            ['Tu cuenta Umbrella', 'El mismo usuario y contraseña de siempre, sin cuentas nuevas.'],
            ['Nada guardado en el dispositivo', 'Los datos se consultan en línea; sin conexión, la app no muestra información.'],
            ['Conexión cifrada', 'Toda la comunicación viaja protegida con HTTPS.'],
            ['Acceso por rol', 'Cada persona ve y recibe solo lo que le corresponde.'],
        ] as [$titulo, $texto]): ?>
          <li class="revelar">
            <span class="landing-check"><?= icon('check', 14) ?></span>
            <div><strong><?= e($titulo) ?></strong><p><?= e($texto) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- ================================ CIERRE ================================ -->
  <section class="landing-seccion landing-seccion--alterna landing-final">
    <div class="landing-contenedor landing-centrado revelar">
      <span class="landing-icono-app"><?= icon('logo', 30) ?></span>
      <h2>Tu operación, <span class="landing-degradado">en tiempo real.</span></h2>
      <p class="landing-bajada">Instálala una vez y entérate de todo en el momento en que sucede.</p>
      <div class="landing-acciones">
        <button type="button" class="btn btn--primario btn--grande" data-landing-install><?= icon('download', 16) ?> Instalar app</button>
      </div>
    </div>
  </section>
</main>

<footer class="landing-pie">
  <div class="landing-contenedor">
    <div class="landing-trainsbee revelar">
      <span class="landing-sobretitulo">Tecnología</span>
      <p class="landing-trainsbee-titulo">Desarrollado con tecnología de <a href="https://trainsbee.com" target="_blank" rel="noopener">Trainsbee</a></p>
      <p class="landing-trainsbee-texto">Infraestructura personalizada y privada para empresas, operaciones y particulares a gran escala.</p>
      <a class="enlace-suave" href="https://trainsbee.com" target="_blank" rel="noopener">trainsbee.com <?= icon('arrow-up-right', 12) ?></a>
    </div>
    <div class="landing-pie-fila">
      <a class="landing-marca" href="./"><?= icon('logo', 18) ?><span>Umbrella</span></a>
      <span>&copy; <?= date('Y') ?> Umbrella · v<?= e(APP_VERSION) ?></span>
    </div>
  </div>
</footer>

<!-- Boton fijo: aparece al bajar, cuando el boton principal sale de la pantalla -->
<div class="landing-fijo" data-landing-fijo aria-hidden="true">
  <div class="landing-fijo-marca"><span class="landing-fijo-icono"><?= icon('logo', 18) ?></span><span><strong>UmbrellaApp</strong><small>Tu operación en tiempo real</small></span></div>
  <button type="button" class="btn btn--primario" data-landing-install tabindex="-1"><?= icon('download', 14) ?> Instalar app</button>
</div>

<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
