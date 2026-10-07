/* ============================================================
   MENÚ INTERACTIVO — solo interacción.
   Las categorías y productos ya vienen renderizados en el HTML
   por menu/index.php (a partir de includes/data.php), así que
   este archivo no guarda ninguna copia de esos datos: lee lo que
   necesita directamente de los atributos data-* de cada botón
   ".row-add" (id, nombre, precio, categoría). Cuando se conecte
   la base de datos, esto sigue funcionando igual sin cambios,
   porque PHP seguirá generando los mismos atributos.
   ============================================================ */

/* ============================================================
   Estado del carrito
   ============================================================ */
const cart = new Map(); // id -> { item, categoryLabel, qty }

const fmt = (n) => `$${n.toFixed(2).replace(/\.00$/, '')}`;

// Lee nombre/precio/categoría directamente del botón "+" ya
// renderizado en el DOM (data-name, data-price, data-category).
function findItem(id) {
  const btn = document.querySelector(`.row-add[data-id="${id}"]`);
  if (!btn || btn.disabled) return null;
  return {
    item: { id, name: btn.dataset.name, price: parseFloat(btn.dataset.price) },
    categoryLabel: btn.dataset.category,
  };
}

function addToCart(id, comment) {
  const found = findItem(id);
  if (!found || Number.isNaN(found.item.price)) return;
  const existing = cart.get(id);
  if (existing) {
    existing.qty += 1;
    if (typeof comment === 'string' && comment !== '') existing.comment = comment;
  } else {
    cart.set(id, { item: found.item, categoryLabel: found.categoryLabel, qty: 1, comment: comment || '' });
  }
  renderCart();
  bumpCartIcon();
}

// Escapa texto para insertarlo de forma segura dentro de un atributo
// HTML (comillas incluidas) — se usa para el comentario, que lo escribe
// el cliente.
function escapeAttr(str) {
  return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function changeQty(id, delta) {
  const entry = cart.get(id);
  if (!entry) return;
  entry.qty += delta;
  if (entry.qty <= 0) cart.delete(id);
  renderCart();
}

function removeFromCart(id) {
  cart.delete(id);
  renderCart();
}

function cartCount() {
  let total = 0;
  cart.forEach((e) => (total += e.qty));
  return total;
}

function cartTotal() {
  let total = 0;
  cart.forEach((e) => (total += e.qty * e.item.price));
  return total;
}

/* ============================================================
   Interacción: nav de categorías + botones "agregar"
   ============================================================ */
function initNav() {
  document.querySelectorAll('.nav-pill').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

function initAddButtons() {
  document.querySelectorAll('.row-add').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
      const id = btn.dataset.id;
      // Si ya está en el carrito, el "+" solo suma cantidad (rápido, sin
      // volver a interrumpir con el recuadro). Para editar el comentario
      // ya agregado, se hace desde el carrito.
      if (cart.has(id)) {
        addToCart(id);
        pulseButton(btn);
      } else {
        openCommentBox(id);
      }
    });
  });

  initCommentBoxes();
}

/* ============================================================
   Recuadro de comentario: se despliega debajo de la fila la
   primera vez que se agrega un producto, para poder anotar algo
   como "sin queso" antes de confirmar.
   ============================================================ */
function openCommentBox(id) {
  // Solo un recuadro abierto a la vez.
  document.querySelectorAll('.row-comment-box.is-open').forEach((box) => {
    if (box.dataset.id !== id) closeCommentBox(box.dataset.id);
  });
  const box = document.getElementById('comment-' + id);
  if (!box) {
    // Producto sin precio (deshabilitado) no tiene recuadro; no debería
    // llegar aquí porque el botón ya está disabled, pero por si acaso.
    return;
  }
  box.classList.add('is-open');
  const input = box.querySelector('.row-comment-input');
  input.value = '';
  input.focus();
}

function closeCommentBox(id) {
  const box = document.getElementById('comment-' + id);
  if (box) box.classList.remove('is-open');
}

function closeAllCommentBoxes() {
  document.querySelectorAll('.row-comment-box.is-open').forEach((box) => box.classList.remove('is-open'));
}

function initCommentBoxes() {
  document.querySelectorAll('.row-comment-box').forEach((box) => {
    const id = box.dataset.id;
    const input = box.querySelector('.row-comment-input');
    const confirmBtn = box.querySelector('.row-comment-confirm');
    const cancelBtn = box.querySelector('.row-comment-cancel');

    const confirmAdd = () => {
      addToCart(id, input.value.trim());
      const btn = document.querySelector(`.row-add[data-id="${id}"]`);
      if (btn) pulseButton(btn);
      closeCommentBox(id);
    };

    confirmBtn.addEventListener('click', confirmAdd);
    cancelBtn.addEventListener('click', () => closeCommentBox(id));
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        confirmAdd();
      } else if (e.key === 'Escape') {
        closeCommentBox(id);
      }
    });
  });
}

// Resalta la categoría activa en el nav al hacer scroll.
function initScrollSpy() {
  const sections = [...document.querySelectorAll('.category')];
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          document.querySelectorAll('.nav-pill').forEach((p) => p.classList.remove('is-active'));
          document.querySelectorAll('.nav-drawer-item').forEach((p) => p.classList.remove('is-active'));

          const pill = document.querySelector(`.nav-pill[data-target="${entry.target.id}"]`);
          if (pill) {
            pill.classList.add('is-active');
            centerPillInNav(pill);
          }
          const drawerItem = document.querySelector(`.nav-drawer-item[data-target="${entry.target.id}"]`);
          if (drawerItem) drawerItem.classList.add('is-active');
        }
      });
    },
    { rootMargin: '-45% 0px -50% 0px', threshold: 0 }
  );
  sections.forEach((s) => observer.observe(s));
}

// Desplaza solo el nav de categorías (horizontal) para centrar la pastilla
// activa. No usamos scrollIntoView aquí porque, al ser el nav "sticky",
// algunos navegadores lo interpretan mal y terminan haciendo scroll
// vertical de toda la página de vuelta hacia arriba.
function centerPillInNav(pill) {
  const nav = document.getElementById('category-nav');
  if (!nav) return;
  const target = pill.offsetLeft - nav.clientWidth / 2 + pill.offsetWidth / 2;
  nav.scrollTo({ left: target, behavior: 'smooth' });
}

function initials(name) {
  return name
    .split(' ')
    .filter((w) => w.length > 2)
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase() || name[0];
}

function pulseButton(btn) {
  const icon = btn.querySelector('.add-btn-icon');
  btn.classList.remove('is-added');
  void btn.offsetWidth; // forzar reflow para reiniciar la animación
  btn.classList.add('is-added');
  icon.textContent = '✓';
  setTimeout(() => {
    icon.textContent = '+';
    btn.classList.remove('is-added');
  }, 750);
}

/* ============================================================
   Render: carrito
   ============================================================ */
function renderCart() {
  const list = document.getElementById('cart-items');
  const emptyState = document.getElementById('cart-empty');
  const footer = document.getElementById('cart-footer');
  const badge = document.getElementById('cart-badge');
  const count = cartCount();

  badge.textContent = count;
  badge.classList.toggle('is-hidden', count === 0);
  saveCartToStorage();

  if (cart.size === 0) {
    list.innerHTML = '';
    emptyState.classList.remove('is-hidden');
    footer.classList.add('is-hidden');
    return;
  }

  emptyState.classList.add('is-hidden');
  footer.classList.remove('is-hidden');

  list.innerHTML = [...cart.entries()]
    .map(
      ([id, entry]) => `
      <li class="cart-item" data-id="${id}">
        <div class="cart-item-media" aria-hidden="true">${initials(entry.item.name)}</div>
        <div class="cart-item-info">
          <p class="cart-item-cat">${entry.categoryLabel}</p>
          <p class="cart-item-name">${entry.item.name}</p>
          <input type="text" class="cart-item-comment" data-id="${id}" maxlength="140" placeholder="Agregar comentario (opcional)" value="${escapeAttr(entry.comment || '')}">
          <div class="cart-item-controls">
            <button class="qty-btn" data-action="minus" data-id="${id}" aria-label="Disminuir cantidad">−</button>
            <span class="qty-value">${entry.qty}</span>
            <button class="qty-btn" data-action="plus" data-id="${id}" aria-label="Aumentar cantidad">+</button>
            <button class="remove-btn" data-id="${id}" aria-label="Eliminar producto">Eliminar</button>
          </div>
        </div>
        <div class="cart-item-price">${fmt(entry.item.price * entry.qty)}</div>
      </li>`
    )
    .join('');

  document.getElementById('cart-total').textContent = fmt(cartTotal());

  list.querySelectorAll('.qty-btn').forEach((btn) => {
    btn.addEventListener('click', () => changeQty(btn.dataset.id, btn.dataset.action === 'plus' ? 1 : -1));
  });
  list.querySelectorAll('.remove-btn').forEach((btn) => {
    btn.addEventListener('click', () => removeFromCart(btn.dataset.id));
  });
  list.querySelectorAll('.cart-item-comment').forEach((input) => {
    input.addEventListener('input', () => {
      const entry = cart.get(input.dataset.id);
      if (!entry) return;
      entry.comment = input.value;
      // Guarda directo sin volver a pintar la lista completa: si
      // llamáramos renderCart() en cada tecla, el input perdería el
      // foco mientras la persona está escribiendo.
      saveCartToStorage();
    });
  });
}
function bumpCartIcon() {
  const icon = document.getElementById('cart-toggle');
  icon.classList.remove('is-bumping');
  void icon.offsetWidth;
  icon.classList.add('is-bumping');
}

/* ============================================================
   Paneles laterales: carrito y categorías comparten un mismo
   overlay, y solo uno puede estar abierto a la vez.
   ============================================================ */
function isAnyDrawerOpen() {
  return document.getElementById('cart-drawer').classList.contains('is-open')
    || document.getElementById('nav-drawer').classList.contains('is-open');
}

function syncScrimAndScroll() {
  const open = isAnyDrawerOpen();
  document.getElementById('drawer-scrim').classList.toggle('is-open', open);
  document.body.classList.toggle('no-scroll', open);
}

function openCart() {
  closeNavDrawer();
  closeAllCommentBoxes();
  document.getElementById('cart-drawer').classList.add('is-open');
  document.getElementById('cart-toggle').setAttribute('aria-expanded', 'true');
  syncScrimAndScroll();
}
function closeCart() {
  document.getElementById('cart-drawer').classList.remove('is-open');
  document.getElementById('cart-toggle').setAttribute('aria-expanded', 'false');
  syncScrimAndScroll();
}

function openNavDrawer() {
  closeCart();
  closeAllCommentBoxes();
  document.getElementById('nav-drawer').classList.add('is-open');
  document.getElementById('nav-toggle').setAttribute('aria-expanded', 'true');
  syncScrimAndScroll();
}
function closeNavDrawer() {
  document.getElementById('nav-drawer').classList.remove('is-open');
  document.getElementById('nav-toggle').setAttribute('aria-expanded', 'false');
  syncScrimAndScroll();
}

function initNavDrawer() {
  document.querySelectorAll('.nav-drawer-item').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      closeNavDrawer();
      // Pequeño respiro para que el panel empiece a cerrarse antes del
      // scroll; se siente más fluido que saltar ambas cosas a la vez.
      if (target) setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
    });
  });
}

/* ============================================================
   Carrito: guardarlo en localStorage para que no se pierda si el
   cliente recarga la página o vuelve más tarde (solo cantidades;
   nombre/precio/categoría siempre se releen del menú actual).
   ============================================================ */
const CART_STORAGE_KEY = 'tida-cart-v1';

function saveCartToStorage() {
  try {
    const data = [...cart.entries()].map(([id, entry]) => [id, entry.qty, entry.comment || '']);
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(data));
  } catch (e) {
    // localStorage puede fallar (modo privado, cuota llena, etc.); no es crítico.
  }
}

function restoreCartFromStorage() {
  try {
    const raw = localStorage.getItem(CART_STORAGE_KEY);
    if (!raw) return;
    JSON.parse(raw).forEach(([id, qty, comment]) => {
      const found = findItem(id);
      if (!found || Number.isNaN(found.item.price) || !(qty > 0)) return; // producto ya no existe / cambió
      cart.set(id, { item: found.item, categoryLabel: found.categoryLabel, qty, comment: comment || '' });
    });
  } catch (e) {
    // Carrito guardado corrupto o de una versión anterior: se ignora.
  }
}

/* ============================================================
   Init
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initNavDrawer();
  initAddButtons();
  initScrollSpy();
  restoreCartFromStorage();
  renderCart();

  document.getElementById('cart-toggle').addEventListener('click', openCart);
  document.getElementById('cart-close').addEventListener('click', closeCart);
  document.getElementById('nav-toggle').addEventListener('click', openNavDrawer);
  document.getElementById('nav-drawer-close').addEventListener('click', closeNavDrawer);
  document.getElementById('drawer-scrim').addEventListener('click', () => {
    closeCart();
    closeNavDrawer();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeCart();
      closeNavDrawer();
    }
  });

  document.getElementById('cart-checkout').addEventListener('click', () => {
    alert('Este es un borrador de frontend. El checkout se conectará cuando integremos la base de datos.');
  });
});
