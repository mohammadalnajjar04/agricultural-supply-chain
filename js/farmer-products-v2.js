(() => {
  const search = document.getElementById('fpmSearch');
  const filter = document.getElementById('fpmFilter');
  const grid = document.getElementById('fpmGrid');
  const empty = document.getElementById('fpmEmpty');
  if (!search || !filter || !grid || !empty) return;
  const cards = [...grid.querySelectorAll('.fpm-card')];
  function update() {
    const text = search.value.trim().toLocaleLowerCase();
    let visible = 0;
    for (const card of cards) {
      const matches = card.dataset.name.toLocaleLowerCase().includes(text)
        && (filter.value === 'all' || filter.value === card.dataset.status);
      card.hidden = !matches;
      if (matches) visible++;
    }
    empty.hidden = visible !== 0;
  }
  search.addEventListener('input', update);
  filter.addEventListener('change', update);
  update();
})();
