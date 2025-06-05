const formContainer = document.getElementById('form-container');
const form = document.getElementById('note-form');
const noteContent = document.getElementById('note-content');
const charCount = document.getElementById('char-count');

document.getElementById('new-note').addEventListener('click', () => showForm('create'));
document.getElementById('cancel').addEventListener('click', hideForm);
document.getElementById('delete-all').addEventListener('click', () => {
    if (confirm('Delete all notes?')) {
        const f = document.createElement('form');
        f.method = 'post';
        f.innerHTML = '<input name="action" value="delete_all">';
        document.body.appendChild(f); f.submit();
    }
});

function showForm(action, note = {}) {
    formContainer.style.display = 'block';

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
    document.getElementById('note-tags').value = (note.tags || []).join(', ');
    document.getElementById('note-color').value = note.color || '#ffffff';
    document.getElementById('note-pinned').checked = note.pinned || false;
    document.getElementById('note-category').value = note.category || '';
    document.getElementById('note-priority').value = note.priority || 1;
    document.getElementById('note-due').value = note.due || '';
    document.getElementById('note-done').checked = note.done || false;
    document.getElementById('note-archived').checked = note.archived || false;
    document.getElementById('form-title').textContent = action === 'create' ? 'New Note' : 'Edit Note';
    updateCharCount();
}

function hideForm() {
    formContainer.style.display = 'none';
    localStorage.removeItem('noteDraft');
    document.getElementById('form-title').textContent = action === 'create' ? 'New Note' : 'Edit Note';
}

function hideForm() {
    document.getElementById('form-container').style.display = 'none';
}

document.querySelectorAll('.edit').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const note = notesData[id];
        showForm('update', note);
    });
});

document.querySelectorAll('.pin').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.createElement('form');
        f.method = 'post';
        f.innerHTML = '<input name="action" value="pin"><input name="id" value="'+btn.dataset.id+'">';
        document.body.appendChild(f); f.submit();
    });
});

document.querySelectorAll('.duplicate').forEach(btn => {
    btn.addEventListener('click', () => {
        const f = document.createElement('form');
        f.method = 'post';
        f.innerHTML = '<input name="action" value="duplicate"><input name="id" value="'+btn.dataset.id+'">';
        document.body.appendChild(f); f.submit();
    });
});

document.querySelectorAll('.print').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const div = document.querySelector('div[data-id="'+id+'"]');
        const win = window.open('','Print');
        win.document.write(div.innerHTML);
        win.document.close();
        win.focus();
        win.print();
        win.close();
    });
});

function updateCharCount() {
    charCount.textContent = noteContent.value.length;
}
noteContent.addEventListener('input', () => {
    updateCharCount();
    localStorage.setItem('noteDraft', JSON.stringify({
        action: document.getElementById('form-action').value,
        id: document.getElementById('note-id').value,
        title: document.getElementById('note-title').value,
        content: noteContent.value,
        tags: document.getElementById('note-tags').value,
        color: document.getElementById('note-color').value,
        pinned: document.getElementById('note-pinned').checked,
        category: document.getElementById('note-category').value,
        priority: document.getElementById('note-priority').value,
        due: document.getElementById('note-due').value,
        done: document.getElementById('note-done').checked,
        archived: document.getElementById('note-archived').checked
    }));
});

window.addEventListener('load', () => {
    const draft = localStorage.getItem('noteDraft');
    if (draft) {
        const d = JSON.parse(draft);
        showForm(d.action, d);
    }

    const tagSelect = document.getElementById('tag-filter');
    const catSelect = document.getElementById('category-filter');
    const tags = new Set();
    const cats = new Set();
    Object.values(notesData).forEach(n => {
        n.tags.forEach(t => tags.add(t));
        if (n.category) cats.add(n.category);
    });
    tagSelect.innerHTML = '<option value="">All tags</option>' + Array.from(tags).map(t => `<option value="${t}">${t}</option>`).join('');
    catSelect.innerHTML = '<option value="">All categories</option>' + Array.from(cats).map(c => `<option value="${c}">${c}</option>`).join('');
    filterAndRender();
});

document.getElementById('search').addEventListener('input', filterAndRender);
document.getElementById('tag-filter').addEventListener('change', filterAndRender);
document.getElementById('category-filter').addEventListener('change', filterAndRender);
document.getElementById('status-filter').addEventListener('change', filterAndRender);
document.getElementById('sort').addEventListener('change', filterAndRender);
document.getElementById('dark-toggle').addEventListener('click', () => {
    document.body.classList.toggle('dark');
});
document.getElementById('layout-toggle').addEventListener('click', () => {
    document.querySelector('.notes').classList.toggle('list');
});

function filterAndRender() {
    const search = document.getElementById('search').value.toLowerCase();
    const tag = document.getElementById('tag-filter').value;
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    const sort = document.getElementById('sort').value;
    const notes = Object.values(notesData).filter(n => {
        const text = (n.title + ' ' + n.content).toLowerCase();
        const matchesSearch = text.includes(search);
        const matchesTag = !tag || n.tags.includes(tag);
        const matchesCategory = !category || n.category === category;
        const matchesStatus = !status ||
            (status === 'done' && n.done) ||
            (status === 'active' && !n.done && !n.archived) ||
            (status === 'archived' && n.archived);
        return matchesSearch && matchesTag && matchesCategory && matchesStatus;
    });
    notes.sort((a,b) => {
        if (a.pinned !== b.pinned) return b.pinned - a.pinned;
        if (sort === 'title') return a.title.localeCompare(b.title);
        if (sort === 'priority') return a.priority - b.priority;
        if (sort === 'due') return (a.due||'').localeCompare(b.due||'');
        return b.created_at - a.created_at;
    });

    const container = document.querySelector('.notes');
    container.innerHTML = '';
    notes.forEach(n => {
        const div = document.createElement('div');
        div.className = 'note' + (n.pinned ? ' pinned' : '');
        div.dataset.id = n.id;
        div.style.background = n.color || '#fff';
        if(n.due && new Date(n.due) < new Date(Date.now() + 86400000)) div.classList.add('due');
        div.innerHTML =
            `<div class="note-header"><h2>${n.title}</h2><button class="pin" data-id="${n.id}">${n.pinned?'Unpin':'Pin'}</button></div>`+
            `<div class="dates">C: ${new Date(n.created_at*1000).toLocaleString()} | U: ${new Date(n.updated_at*1000).toLocaleString()}</div>`+
            `<div class="tags">${n.tags.join(', ')}</div>`+
            `<div>Category: ${n.category||''}</div>`+
            `<div>Priority: ${n.priority}</div>`+
            `${n.due?`<div>Due: ${n.due}</div>`:''}`+
            `<div class="status">${n.done?'Done':'Active'}${n.archived?' (Archived)':''}</div>`+
            `${n.attachment?`<div class="attach"><img src="${n.attachment}" class="max-w-xs"/></div>`:''}`+
            `<div class="preview" data-content="${n.content}"></div>`+
            `<div class="note-actions"><button class="edit" data-id="${n.id}">Edit</button>`+
            `<button class="duplicate" data-id="${n.id}">Duplicate</button>`+
            `<button class="print" data-id="${n.id}">Print</button>`+
            `<button class="share" data-id="${n.id}">Share</button>`+
            `<button class="done" data-id="${n.id}">${n.done?'Undo':'Done'}</button>`+
            `<button class="archive" data-id="${n.id}">${n.archived?'Unarchive':'Archive'}</button>`+
            `<form method="post" class="delete-form"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${n.id}"><button type="submit">Delete</button></form></div>`;
        container.appendChild(div);
    });

    document.querySelectorAll('.preview').forEach(p => {
        const html = marked.parse(p.dataset.content);
        const snippet = html.slice(0, 200);
        p.innerHTML = snippet + (html.length>200 ? '...' : '');
        p.addEventListener('click', () => {
            p.innerHTML = p.innerHTML === snippet + '...' ? html : snippet + '...';
        });
    });

    container.querySelectorAll('.edit').forEach(btn => {
        btn.addEventListener('click', () => {
            showForm('update', notesData[btn.dataset.id]);
        });
    });
    container.querySelectorAll('.duplicate').forEach(btn => {
        btn.addEventListener('click', () => {
            const f=document.createElement('form');f.method='post';f.innerHTML='<input name="action" value="duplicate"><input name="id" value="'+btn.dataset.id+'">';document.body.appendChild(f);f.submit();
        });
    });
    container.querySelectorAll('.pin').forEach(btn => {
        btn.addEventListener('click', () => {
            const f=document.createElement('form');f.method='post';f.innerHTML='<input name="action" value="pin"><input name="id" value="'+btn.dataset.id+'">';document.body.appendChild(f);f.submit();
        });
    });
    container.querySelectorAll('.print').forEach(btn => {
        btn.addEventListener('click', () => {
            const id=btn.dataset.id;const div=document.querySelector('div[data-id="'+id+'"]');const win=window.open('','Print');win.document.write(div.innerHTML);win.document.close();win.focus();win.print();win.close();
        });
    });
    container.querySelectorAll('.share').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = location.href.split('?')[0] + '#'+btn.dataset.id;
            navigator.clipboard.writeText(url);
            alert('Link copied');
        });
    });
    container.querySelectorAll('.done').forEach(btn => {
        btn.addEventListener('click', () => {
            const f=document.createElement('form');f.method='post';f.innerHTML='<input name="action" value="toggle_done"><input name="id" value="'+btn.dataset.id+'">';document.body.appendChild(f);f.submit();
        });
    });
    container.querySelectorAll('.archive').forEach(btn => {
        btn.addEventListener('click', () => {
            const f=document.createElement('form');f.method='post';f.innerHTML='<input name="action" value="archive"><input name="id" value="'+btn.dataset.id+'">';document.body.appendChild(f);f.submit();
        });
    });

    document.getElementById('count').textContent = `(${notes.length})`;
}

        const id = btn.getAttribute('data-id');
        const noteDiv = document.querySelector(`div[data-id="${id}"]`);
        const title = noteDiv.querySelector('h2').textContent;
        const content = noteDiv.querySelector('p').textContent;
        showForm('update', {id, title, content});
    });
});
