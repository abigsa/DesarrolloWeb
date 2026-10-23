// Agrega y quita filas sin dependencias externas. Misma lógica en alta y edición.
(function () {
  const max = 30;
  const maxYear = new Date().getFullYear() + 5;
  const templates = {
    curso: `<div class="academic-row course-row"><div class="field"><label>Año</label><input name="cursos[anio][]" type="number" min="1900" max="${maxYear}" required></div><div class="field"><label>Nombre del curso</label><input name="cursos[nombre][]" type="text" maxlength="50" required></div><button type="button" class="btn btn-secondary remove-row">Quitar</button></div>`,
    educacion: `<div class="academic-row education-row"><div class="field"><label>Año</label><input name="educacion[anio][]" type="number" min="1900" max="${maxYear}" required></div><div class="field"><label>Institución</label><input name="educacion[institucion][]" type="text" maxlength="120" required></div><div class="field"><label>Grado o título</label><input name="educacion[grado][]" type="text" maxlength="120" required></div><button type="button" class="btn btn-secondary remove-row">Quitar</button></div>`
  };
  document.addEventListener('click', function (event) {
    const add = event.target.closest('[data-add]');
    if (add) {
      const type = add.dataset.add;
      const container = document.getElementById(type === 'curso' ? 'lista-cursos' : 'lista-educacion');
      if (!container || container.children.length >= max) { alert('Máximo 30 registros por sección.'); return; }
      container.insertAdjacentHTML('beforeend', templates[type]);
      container.lastElementChild.querySelector('input').focus();
    }
    const remove = event.target.closest('.remove-row');
    if (remove) remove.closest('.academic-row').remove();
  });
})();
