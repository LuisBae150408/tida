document.addEventListener('DOMContentLoaded', () => {
  /* ---------------------------------------------------------------
     Barra editable: aparece justo debajo de la fila al tocar
     "Editar"; volver a tocar el mismo botón la cierra (pidiendo
     confirmación si hay cambios sin guardar).
     --------------------------------------------------------------- */
  function closeBar(bar, btn, skipConfirm) {
    if (!bar) return true;
    if (!skipConfirm && bar.dataset.dirty === '1') {
      if (!confirm('Tienes cambios sin guardar en este formulario. ¿Descartarlos?')) return false;
    }
    bar.classList.remove('is-open');
    bar.dataset.dirty = '0';
    if (btn) {
      btn.classList.remove('is-active');
      btn.textContent = 'Editar';
    }
    if (bar.tagName === 'FORM') bar.reset();
    return true;
  }

  document.querySelectorAll('[data-edit-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const bar = document.getElementById(btn.dataset.editToggle);
      if (!bar) return;

      if (bar.classList.contains('is-open')) {
        closeBar(bar, btn);
        return;
      }

      // Cierra cualquier otra barra que haya quedado abierta.
      document.querySelectorAll('.edit-bar.is-open').forEach((openBar) => {
        if (openBar === bar) return;
        const openBtn = document.querySelector('[data-edit-toggle="' + openBar.id + '"]');
        closeBar(openBar, openBtn, true);
      });

      bar.classList.add('is-open');
      btn.classList.add('is-active');
      btn.textContent = 'Cerrar';
      bar.dataset.dirty = '0';
    });
  });

  document.querySelectorAll('.edit-bar').forEach((bar) => {
    bar.addEventListener('input', () => { bar.dataset.dirty = '1'; });
    bar.addEventListener('change', () => { bar.dataset.dirty = '1'; });
  });

  document.querySelectorAll('.btn-discard').forEach((btn) => {
    btn.addEventListener('click', () => {
      const bar = btn.closest('.edit-bar');
      const toggleBtn = document.querySelector('[data-edit-toggle="' + bar.id + '"]');
      closeBar(bar, toggleBtn, true);
    });
  });

  /* ---------------------------------------------------------------
     Confirmación nativa en cualquier botón "Eliminar"
     --------------------------------------------------------------- */
  document.querySelectorAll('[data-confirm]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

  /* ---------------------------------------------------------------
     Vista previa al elegir una foto (antes de subirla)
     --------------------------------------------------------------- */
  document.querySelectorAll('.field-file input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      const wrap = input.closest('.field-file');
      const preview = wrap ? wrap.querySelector('img.preview') : null;
      if (!file || !preview) return;
      const reader = new FileReader();
      reader.onload = (e) => { preview.src = e.target.result; };
      reader.readAsDataURL(file);
    });
  });

  /* ---------------------------------------------------------------
     Campo condicional: muestra un grupo de campos u otro según el
     valor de un <select data-aplica-a-select> (usado en Descuentos
     para producto/categoría, y en Configuración para imagen/video).
     --------------------------------------------------------------- */
  document.querySelectorAll('[data-aplica-a-select]').forEach((select) => {
    const scope = select.closest('form') || select.closest('.edit-bar');
    if (!scope) return;
    const sync = () => {
      scope.querySelectorAll('[data-shows-for]').forEach((el) => {
        el.style.display = el.dataset.showsFor === select.value ? '' : 'none';
      });
    };
    select.addEventListener('change', sync);
    sync();
  });

  /* ---------------------------------------------------------------
     Auto-ocultar el mensaje flash después de unos segundos.
     --------------------------------------------------------------- */
  const flash = document.querySelector('.admin-flash');
  if (flash) {
    setTimeout(() => { flash.style.transition = 'opacity 0.4s'; flash.style.opacity = '0'; }, 4000);
  }
});
