document.addEventListener("DOMContentLoaded", () => {

  const searchInput = document.getElementById("textSearch");
  const filterSelect = document.getElementById("statusFilter");
  const table = document.getElementById("textTable");

  if (!searchInput || !filterSelect || !table) return;

  const tbody = table.querySelector("tbody");
  let rows = Array.from(tbody.querySelectorAll("tr"));
  const visibleCount = document.getElementById("visibleCount");

  function updateVisibleCount() {
    if (!visibleCount) return;
    let n = 0;
    rows.forEach(r => { if (r.style.display !== "none") n++; });
    visibleCount.textContent = String(n);
  }

  function updateTable() {
    const q = searchInput.value.trim().toLowerCase();
    const filter = filterSelect.value;

    rows.forEach(row => {

      const title = row.dataset.title || "";
      const id = row.dataset.id || "";
      const tasks = parseInt(row.dataset.tasks || "0", 10);
      const status = row.dataset.status || "";

      let ok = true;

      if (q && !title.includes(q) && !id.includes(q)) ok = false;
      if (filter === "active" && status !== "active") ok = false;
      if (filter === "noTasks" && tasks !== 0) ok = false;

      row.style.display = ok ? "" : "none";
    });

    updateVisibleCount();
  }

  searchInput.addEventListener("input", updateTable);
  filterSelect.addEventListener("change", updateTable);

  /* SORTERING */
  table.querySelectorAll("th[data-sort]").forEach(th => {
    th.addEventListener("click", () => {

      const key = th.dataset.sort;
      const sorted = rows.slice().sort((a, b) => {

        if (key === "title") return (a.dataset.title || "").localeCompare(b.dataset.title || "");
        if (key === "status") return (a.dataset.status || "").localeCompare(b.dataset.status || "");

        return parseInt(a.dataset[key] || "0", 10) - parseInt(b.dataset[key] || "0", 10);
      });

      sorted.forEach(r => tbody.appendChild(r));
      rows = Array.from(tbody.querySelectorAll("tr"));
    });
  });

  updateTable();
});