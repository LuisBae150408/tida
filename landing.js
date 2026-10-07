/* ============================================================
   DATOS — categorías y destacados ya se renderizan en el HTML
   desde PHP (ver index.php). Lo único que sigue viviendo en JS es
   el pool de productos de la vitrina: llega como `PRODUCT_POOL`
   (inyectado por PHP en un <script> antes de este archivo, a
   partir de includes/data.php) porque el armado de la vitrina
   -elegir 8 al azar, dibujar los círculos, el arrastre- es
   inherentemente interactivo/aleatorio y necesita ejecutarse en
   el cliente.
   ============================================================ */

function shuffle(arr) {
  const a = arr.slice();
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

/* ============================================================
   VITRINA — 4 columnas fijas en el DOM (cada una con ítem arriba
   y abajo). En vez de roles discretos (frente/derecha/atrás/
   izquierda) cada columna tiene una posición CONTINUA (0-4) que se
   interpola entre esos 4 "estados" clave. Así el giro puede seguir
   al dedo/mouse en tiempo real durante el arrastre, y solo al
   soltar se anima el salto ("snap") hacia la cara más cercana.
   ============================================================ */

// Valores clave por estado: [frente, derecha, atrás, izquierda, frente]
// (el último repite el primero para que la interpolación cierre el ciclo).
const KEY_X       = [0, 82, 0, -82, 0];
const KEY_SCALE   = [1, 0.62, 0.42, 0.62, 1];
const KEY_OPACITY = [1, 0.5, 0.22, 0.5, 1];
const KEY_Z       = [5, 3, 1, 3, 5];

let rotation = 0; // posición continua de la vitrina (puede ser fraccional)
let columnsData = []; // [{top, bottom}, ...] x4

function buildShowcaseData() {
  const pool = Array.isArray(window.PRODUCT_POOL) ? window.PRODUCT_POOL : [];
  const picked = shuffle(pool).slice(0, 8);
  columnsData = [0, 1, 2, 3].map((i) => ({
    top: picked[i],
    bottom: picked[i + 4],
  }));
}

function blobHtml(product, pos) {
  if (!product) return '<div class="shelf-item"></div>';
  return `
    <div class="shelf-item">
      <div class="blob-particles" aria-hidden="true">
        <span class="particle p1"></span><span class="particle p2"></span><span class="particle p3"></span>
        <span class="particle p4"></span><span class="particle p5"></span><span class="particle p6"></span>
      </div>
      <a class="blob-frame blob-${pos}" href="menu/index.php#${product.categoryId}" data-cat="${product.categoryId}">
        ${product.icon}
        <span class="blob-label">${product.name}</span>
      </a>
      <div class="column-shelf"></div>
    </div>`;
}

function renderShowcase() {
  const ring = document.getElementById('showcaseRing');
  ring.innerHTML = columnsData
    .map(
      (col, i) => `
    <div class="showcase-column" data-col="${i}">
      ${blobHtml(col.top, 'top')}
      ${blobHtml(col.bottom, 'bottom')}
    </div>`
    )
    .join('');
}

// Interpola linealmente un arreglo de 5 valores clave en una posición
// continua p (se normaliza siempre a [0, 4)).
function sampleKey(keys, p) {
  const pos = ((p % 4) + 4) % 4;
  const i0 = Math.floor(pos);
  const t = pos - i0;
  return keys[i0] + (keys[i0 + 1] - keys[i0]) * t;
}

// Aplica la posición visual de cada columna según `rotation`.
// animated=true -> anima el cambio (usado al soltar o con las flechas).
// animated=false -> aplica sin transición (usado mientras se arrastra).
function applyRoles(animated) {
  const cols = document.querySelectorAll('.showcase-column');
  cols.forEach((el) => {
    const i = Number(el.dataset.col);
    const pos = i - rotation;
    const x = sampleKey(KEY_X, pos);
    const scale = sampleKey(KEY_SCALE, pos);
    const opacity = sampleKey(KEY_OPACITY, pos);
    const z = Math.round(sampleKey(KEY_Z, pos));
    const normPos = ((pos % 4) + 4) % 4;
    const isFront = normPos < 0.28 || normPos > 3.72;

    el.style.transition = animated
      ? 'transform 0.5s var(--ease), opacity 0.5s var(--ease)'
      : 'none';
    el.style.transform = `translate(-50%, -50%) translateX(${x}%) scale(${scale})`;
    el.style.opacity = opacity;
    el.style.zIndex = z;
    el.style.pointerEvents = scale < 0.48 ? 'none' : '';
    el.classList.toggle('is-front', isFront);
  });
}

function rotateShowcase(dir) {
  rotation = Math.round(rotation) + dir;
  applyRoles(true);
}

/* ============================================================
   HOVER / ENFOQUE — en computadora (con mouse), pasar el cursor
   sobre un producto lo agranda, le da un poco de brillo y saca
   unas partículas simples detrás. En celular no hay "hover", así
   que el primer toque hace lo mismo (lo "enfoca") y evita navegar;
   recién el segundo toque sobre el mismo producto abre el menú.
   ============================================================ */
const supportsHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

function clearArmedBlobs() {
  document.querySelectorAll('.blob-frame.is-armed').forEach((b) => {
    b.classList.remove('is-armed', 'is-focused');
    const col = b.closest('.showcase-column');
    if (col && col.dataset.prevZ !== undefined) {
      col.style.zIndex = col.dataset.prevZ;
      delete col.dataset.prevZ;
    }
  });
}

function bumpColumnZ(column) {
  if (!column || column.dataset.prevZ !== undefined) return;
  column.dataset.prevZ = column.style.zIndex || '';
  column.style.zIndex = 50;
}
function restoreColumnZ(column) {
  if (!column || column.dataset.prevZ === undefined) return;
  column.style.zIndex = column.dataset.prevZ;
  delete column.dataset.prevZ;
}

function setupBlobInteractions() {
  document.querySelectorAll('.blob-frame').forEach((blob) => {
    const column = blob.closest('.showcase-column');

    if (supportsHover) {
      blob.addEventListener('mouseenter', () => {
        blob.classList.add('is-focused');
        bumpColumnZ(column);
      });
      blob.addEventListener('mouseleave', () => {
        blob.classList.remove('is-focused');
        restoreColumnZ(column);
      });
    } else {
      blob.addEventListener('click', (e) => {
        if (!blob.classList.contains('is-armed')) {
          e.preventDefault();
          clearArmedBlobs();
          blob.classList.add('is-armed', 'is-focused');
          bumpColumnZ(column);
        }
        // si ya estaba "armado" (segundo toque), se deja navegar al menú
      });
    }
  });
}

/* ============================================================
   DRAG / SWIPE — sigue al dedo/mouse en tiempo real; solo al
   soltar la vitrina hace "snap" hacia la cara más cercana.
   ============================================================ */
function initDrag() {
  const stage = document.querySelector('.showcase-stage');
  let startX = 0;
  let startRotation = 0;
  let dragging = false;
  let moved = false;
  let sensitivity = 240;

  const onDown = (x) => {
    dragging = true;
    moved = false;
    startX = x;
    startRotation = rotation;
    sensitivity = Math.max(160, stage.getBoundingClientRect().width * 0.55);
  };
  const onMove = (x) => {
    if (!dragging) return;
    const deltaX = x - startX;
    if (Math.abs(deltaX) > 6) {
      if (!moved) clearArmedBlobs(); // un arrastre real sí cancela el "enfoque"
      moved = true;
    }
    rotation = startRotation - deltaX / sensitivity;
    applyRoles(false);
  };
  const onUp = () => {
    if (!dragging) return;
    dragging = false;
    rotation = Math.round(rotation);
    applyRoles(true);
  };

  stage.addEventListener('pointerdown', (e) => {
    // evita que el navegador de escritorio interprete el arrastre como
    // selección de texto o "drag" nativo de una imagen/svg interno
    e.preventDefault();
    onDown(e.clientX);
    stage.setPointerCapture(e.pointerId);
  });
  stage.addEventListener('pointermove', (e) => onMove(e.clientX));
  stage.addEventListener('pointerup', onUp);
  stage.addEventListener('pointercancel', () => { dragging = false; });

  // refuerzo extra: bloquea el "drag" nativo del navegador sobre
  // cualquier imagen/svg dentro de la vitrina
  stage.addEventListener('dragstart', (e) => e.preventDefault());

  // evita que un arrastre se interprete como click en el producto
  stage.addEventListener('click', (e) => {
    if (moved) { e.preventDefault(); e.stopPropagation(); }
  }, true);
}

/* ============================================================
   FONDO DEL HERO — crossfade entre las imágenes configuradas desde
   /admin (si hay una sola, o ninguna, simplemente no hace nada).
   ============================================================ */
function initHeroBg() {
  const imgs = [...document.querySelectorAll('.hero-bg-img')];
  if (imgs.length < 2) return;
  let i = 0;
  setInterval(() => {
    imgs[i].classList.remove('is-active');
    i = (i + 1) % imgs.length;
    imgs[i].classList.add('is-active');
  }, 6500);
}

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  buildShowcaseData();
  renderShowcase();
  applyRoles(false);
  initDrag();
  setupBlobInteractions();
  initHeroBg();

  document.getElementById('arrowLeft').addEventListener('click', () => rotateShowcase(-1));
  document.getElementById('arrowRight').addEventListener('click', () => rotateShowcase(1));
  document.getElementById('scrollHint').addEventListener('click', () => {
    document.querySelector('.categories-section').scrollIntoView({ behavior: 'smooth' });
  });
  document.getElementById('footerYear').textContent = new Date().getFullYear();
});
