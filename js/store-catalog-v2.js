// Progressive, client-side filtering of products currently loaded on this page.
(() => {
  const container = document.getElementById('catalog-products');
  const search = document.getElementById('catalog-search');
  const sort = document.getElementById('catalog-sort');
  const count = document.getElementById('catalog-count');
  const noResults = document.getElementById('catalog-no-results');
  if (!container || !search || !sort || !count || !noResults) return;
  const items = Array.from(container.querySelectorAll('.catalog-item'));
  function update() {
    const term = search.value.trim().toLocaleLowerCase();
    const order = sort.value;
    const sorted = items.slice().sort((a,b) => {
      if (order === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
      if (order === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
      return Number(b.dataset.id) - Number(a.dataset.id);
    });
    let visible = 0;
    sorted.forEach(el => {
      const show = (el.dataset.search || '').toLocaleLowerCase().includes(term);
      el.hidden = !show;
      el.style.display = show ? '' : 'none';
      if (show) visible++;
      container.appendChild(el);
    });
    count.textContent = String(visible) + ' / ' + String(items.length);
    noResults.hidden = visible !== 0 || items.length === 0;
  }
  search.addEventListener('input', update);
  sort.addEventListener('change', update);
  update();
})();
