document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-admin-search]').forEach((input) => {
    const scope = input.closest('.cardx');
    if (!scope) return;
    const rows = [...scope.querySelectorAll('tbody tr')];
    input.addEventListener('input', () => {
      const term = input.value.trim().toLocaleLowerCase();
      rows.forEach((row) => {
        row.hidden = !row.textContent.toLocaleLowerCase().includes(term);
      });
    });
  });
  document.querySelectorAll('[data-admin-approval-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
    });
  });
});
