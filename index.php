<?php
declare(strict_types=1);

/**
 * Landing publica de UmbrellaApp: invita a instalar la app y explica lo
 * que ofrece. La app instalada abre directo en login.php (start_url del
 * manifest), y si alguien llega aqui desde la app, se le redirige.
 */

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Tu operación, en tu bolsillo';
$pageCss = ['assets/css/landing.css'];
$bodyClass = 'landing';
$bodyAttrs = ['data-install-auto' => 'off'];
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
      <a href="#ofrece">Qué ofrece</a>
      <a href="#dispositivos">Dispositivos</a>
      <a href="#seguridad">Seguridad</a>
    </nav>
    <a class="btn" href="login.php">Iniciar sesión</a>
  </div>
</header>

<main>
  <!-- Primera vista: instalar -->
  <section class="landing-heroe">
    <div class="landing-resplandor" aria-hidden="true"></div>
    <div class="landing-contenedor landing-heroe-texto">
      <span class="landing-icono-app revelar"><?= icon('logo', 34) ?></span>
      <span class="landing-etiqueta revelar">Ecosistema Umbrella</span>
      <h1 class="revelar">Tu operación,<br><span class="landing-degradado">en tu bolsillo.</span></h1>
      <p class="landing-bajada revelar">Ventas, oportunidades y reportes en una app que se instala en segundos, abre al instante y se siente nativa en cualquier dispositivo.</p>
      <div class="landing-acciones revelar">
        <button type="button" class="btn btn--primario btn--grande" data-landing-install id="instalarHeroe">
          <?= icon('download', 16) ?> Instalar app
        </button>
        <p class="landing-nota" data-landing-nota hidden></p>
        <a class="enlace-suave" href="login.php">o continúa en el navegador</a>
      </div>
    </div>

    <a class="landing-descubre" href="#ofrece" aria-label="Descubre lo que ofrece">
      <span>Descubre más</span>
      <?= icon('chevron-down', 18) ?>
    </a>
  </section>

  <!-- Que ofrece -->
  <section class="landing-seccion" id="ofrece">
    <div class="landing-contenedor">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Qué ofrece</span>
        <h2>Todo lo que tu equipo necesita.<br><span class="landing-tenue">Nada que estorbe.</span></h2>
      </div>

      <div class="landing-bento">
        <article class="landing-tarjeta landing-tarjeta--grande revelar">
          <span class="landing-tarjeta-icono"><?= icon('trending-up', 18) ?></span>
          <h3>Ventas al momento</h3>
          <p>El pulso del negocio en una sola mirada: lo que se cerró hoy, lo que avanza y lo que necesita atención.</p>
          <div class="landing-barras" aria-hidden="true"><?= str_repeat('<i></i>', 12) ?></div>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('users', 18) ?></span>
          <h3>Oportunidades</h3>
          <p>Cada prospecto con su historia y su siguiente paso, para que ninguno se quede en el camino.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('bar-chart-2', 18) ?></span>
          <h3>Reportes de cierre</h3>
          <p>Resultados claros al final de cada jornada, listos para decidir sin abrir una hoja de cálculo.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('zap', 18) ?></span>
          <h3>Abre al instante</h3>
          <p>Desde tu pantalla de inicio, sin tiendas de apps ni descargas pesadas.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('refresh-cw', 18) ?></span>
          <h3>Siempre al día</h3>
          <p>Cada mejora llega sola. Nunca verás datos ni pantallas de una versión anterior.</p>
        </article>
        <article class="landing-tarjeta revelar">
          <span class="landing-tarjeta-icono"><?= icon('moon', 18) ?></span>
          <h3>Claro u oscuro</h3>
          <p>Elige el tema que mejor te acomode; la app lo recuerda.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- Vista previa -->
  <section class="landing-seccion landing-seccion--alterna">
    <div class="landing-contenedor landing-dos-columnas">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Diseñada para el día a día</span>
        <h2>Simple por fuera.<br><span class="landing-tenue">Completa por dentro.</span></h2>
        <p class="landing-bajada">Una navegación pensada para el pulgar, un menú que lo tiene todo a un toque y una interfaz que se adapta a tu pantalla, del teléfono al escritorio.</p>
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

  <!-- Dispositivos -->
  <section class="landing-seccion" id="dispositivos">
    <div class="landing-contenedor">
      <div class="landing-encabezado landing-centrado revelar">
        <span class="landing-sobretitulo">Dispositivos</span>
        <h2>Una sola app.<br><span class="landing-tenue">En todas tus pantallas.</span></h2>
      </div>
      <div class="landing-dispositivos">
        <?php foreach ([
            ['smartphone', 'iPhone', 'Compartir → Agregar a inicio'],
            ['tablet',     'iPad',   'Compartir → Agregar a inicio'],
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

  <!-- Seguridad -->
  <section class="landing-seccion landing-seccion--alterna" id="seguridad">
    <div class="landing-contenedor landing-dos-columnas">
      <div class="landing-encabezado revelar">
        <span class="landing-sobretitulo">Seguridad</span>
        <h2>Tus datos,<br><span class="landing-tenue">donde deben estar.</span></h2>
        <p class="landing-bajada">La información vive en los servidores de Umbrella, no en el teléfono. Si pierdes el dispositivo, tus datos no se van con él.</p>
      </div>
      <ul class="landing-lista">
        <?php foreach ([
            ['Tu cuenta Umbrella', 'El mismo usuario y contraseña de siempre, sin cuentas nuevas.'],
            ['Nada guardado en el dispositivo', 'Los datos se consultan en línea; sin conexión, la app no muestra información.'],
            ['Conexión cifrada', 'Toda la comunicación viaja protegida con HTTPS.'],
            ['Acceso por rol', 'Cada persona ve solo lo que le corresponde.'],
        ] as [$titulo, $texto]): ?>
          <li class="revelar">
            <span class="landing-check"><?= icon('check', 14) ?></span>
            <div><strong><?= e($titulo) ?></strong><p><?= e($texto) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- Cierre -->
  <section class="landing-seccion landing-final">
    <div class="landing-contenedor landing-centrado revelar">
      <span class="landing-icono-app"><?= icon('logo', 30) ?></span>
      <h2>Lleva Umbrella contigo.</h2>
      <p class="landing-bajada">Instálala una vez y tenla siempre a un toque.</p>
      <div class="landing-acciones">
        <button type="button" class="btn btn--primario btn--grande" data-landing-install><?= icon('download', 16) ?> Instalar app</button>
      </div>
    </div>
  </section>
</main>

<footer class="landing-pie">
  <div class="landing-contenedor landing-pie-fila">
    <a class="landing-marca" href="./"><?= icon('logo', 18) ?><span>Umbrella</span></a>
    <span>&copy; <?= date('Y') ?> Umbrella · v<?= e(APP_VERSION) ?></span>
  </div>
</footer>

<!-- Boton fijo: aparece al bajar, cuando el boton principal sale de la pantalla -->
<div class="landing-fijo" data-landing-fijo aria-hidden="true">
  <div class="landing-fijo-marca"><span class="landing-fijo-icono"><?= icon('logo', 18) ?></span><span><strong>UmbrellaApp</strong><small>Instálala gratis</small></span></div>
  <button type="button" class="btn btn--primario" data-landing-install tabindex="-1"><?= icon('download', 14) ?> Instalar app</button>
</div>

<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
