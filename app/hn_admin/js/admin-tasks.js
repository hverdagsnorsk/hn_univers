// ../js/admin-tasks.js
(() => {
  const checkAll = document.getElementById("check-all");
  const rowChecks = () => Array.from(document.querySelectorAll(".row-check"));
  const bulkForm = document.getElementById("bulkForm");
  const bulkSelect = document.getElementById("bulk_action");

  if (checkAll) {
    checkAll.addEventListener("change", () => {
      rowChecks().forEach(cb => (cb.checked = checkAll.checked));
    });
  }

  if (bulkForm) {
    bulkForm.addEventListener("submit", (e) => {
      const action = bulkSelect ? bulkSelect.value : "";
      const checked = rowChecks().filter(cb => cb.checked).length;

      if (!action) {
        e.preventDefault();
        alert("Velg en handling først.");
        return;
      }
      if (checked === 0) {
        e.preventDefault();
        alert("Du må velge minst én oppgave.");
        return;
      }
      if (action === "delete") {
        if (!confirm("Er du sikker på at du vil slette valgte oppgaver?")) {
          e.preventDefault();
        }
      }
    });
  }
})();