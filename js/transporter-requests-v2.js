document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('transport-search');
  const grid = document.getElementById('transport-request-grid');
  const count = document.getElementById('transport-count');
  if (input && grid) {
    const cards = [...grid.querySelectorAll('.transport-item')];
    const update = () => {
      const query = input.value.trim().toLocaleLowerCase();
      let visible = 0;
      cards.forEach(card => {
        const match = card.textContent.toLocaleLowerCase().includes(query);
        card.hidden = !match;
        if (match) visible++;
      });
      if (count) count.textContent = `${visible} / ${cards.length}`;
    };
    input.addEventListener('input', update);
    update();
  }
  document.querySelectorAll('.transport-action-form').forEach(form => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
    });
  });
});
