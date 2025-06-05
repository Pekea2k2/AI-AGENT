document.getElementById('new-note').addEventListener('click', () => {
    showForm('create');
});

document.getElementById('cancel').addEventListener('click', () => {
    hideForm();
});

function showForm(action, note = {}) {
    document.getElementById('form-container').style.display = 'block';
    document.getElementById('form-action').value = action;
    document.getElementById('note-id').value = note.id || '';
    document.getElementById('note-title').value = note.title || '';
    document.getElementById('note-content').value = note.content || '';
    document.getElementById('form-title').textContent = action === 'create' ? 'New Note' : 'Edit Note';
}

function hideForm() {
    document.getElementById('form-container').style.display = 'none';
}

document.querySelectorAll('.edit').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const noteDiv = document.querySelector(`div[data-id="${id}"]`);
        const title = noteDiv.querySelector('h2').textContent;
        const content = noteDiv.querySelector('p').textContent;
        showForm('update', {id, title, content});
    });
});
