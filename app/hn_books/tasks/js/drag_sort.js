document.addEventListener('DOMContentLoaded', () => {
    const words   = document.querySelectorAll('.drag-word');
    const columns = document.querySelectorAll('.drag-column');

    words.forEach(w => {
        w.addEventListener('dragstart', e => {
            e.dataTransfer.setData('id', w.id);
        });
    });

    columns.forEach(col => {
        col.addEventListener('dragover', e => {
            e.preventDefault();
            col.classList.add('drag-over');
        });

        col.addEventListener('dragleave', () => {
            col.classList.remove('drag-over');
        });

        col.addEventListener('drop', e => {
            e.preventDefault();
            col.classList.remove('drag-over');

            const id = e.dataTransfer.getData('id');
            const el = document.getElementById(id);
            if (el) col.appendChild(el);
        });
    });
});
