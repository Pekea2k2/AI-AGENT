<?php
$notesFile = __DIR__ . '/notes.json';
$notes = [];
if (file_exists($notesFile)) {
    $data = file_get_contents($notesFile);
    $notes = json_decode($data, true);
    if (!is_array($notes)) {
        $notes = [];
    }
}

function save_notes($file, $notes)
{
    file_put_contents($file, json_encode($notes, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $id = uniqid();
        $notes[$id] = [
            'id' => $id,
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
            'created_at' => time(),
            'updated_at' => time(),
            'pinned' => !empty($_POST['pinned']),
            'color' => $_POST['color'] ?? '#ffffff',
            'category' => trim($_POST['category'] ?? ''),
            'priority' => (int)($_POST['priority'] ?? 1),
            'due' => $_POST['due'] ?? '',
            'done' => !empty($_POST['done']),
            'archived' => !empty($_POST['archived']),
            'attachment' => ''
        ];
        if (!empty($_FILES['attachment']['tmp_name'])) {
            $notes[$id]['attachment'] = 'data:' . mime_content_type($_FILES['attachment']['tmp_name']) . ';base64,' . base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        if (isset($notes[$id])) {
            $notes[$id]['title'] = trim($_POST['title'] ?? '');
            $notes[$id]['content'] = trim($_POST['content'] ?? '');
            $notes[$id]['tags'] = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
            $notes[$id]['pinned'] = !empty($_POST['pinned']);
            $notes[$id]['color'] = $_POST['color'] ?? '#ffffff';
            $notes[$id]['category'] = trim($_POST['category'] ?? '');
            $notes[$id]['priority'] = (int)($_POST['priority'] ?? 1);
            $notes[$id]['due'] = $_POST['due'] ?? '';
            $notes[$id]['done'] = !empty($_POST['done']);
            $notes[$id]['archived'] = !empty($_POST['archived']);
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $notes[$id]['attachment'] = 'data:' . mime_content_type($_FILES['attachment']['tmp_name']) . ';base64,' . base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
            }
            $notes[$id]['updated_at'] = time();
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        unset($notes[$_POST['id']]);
    } elseif ($action === 'delete_all') {
        $notes = [];
    } elseif ($action === 'pin' && isset($_POST['id'])) {
        $id = $_POST['id'];
        if (isset($notes[$id])) {
            $notes[$id]['pinned'] = !$notes[$id]['pinned'];
            $notes[$id]['updated_at'] = time();
        }
    } elseif ($action === 'duplicate' && isset($_POST['id'])) {
        $orig = $notes[$_POST['id']] ?? null;
        if ($orig) {
            $newId = uniqid();
            $orig['id'] = $newId;
            $orig['created_at'] = time();
            $orig['updated_at'] = time();
            $notes[$newId] = $orig;
        }
    } elseif ($action === 'toggle_done' && isset($_POST['id'])) {
        $id = $_POST['id'];
        if (isset($notes[$id])) {
            $notes[$id]['done'] = !$notes[$id]['done'];
            $notes[$id]['updated_at'] = time();
        }
    } elseif ($action === 'archive' && isset($_POST['id'])) {
        $id = $_POST['id'];
        if (isset($notes[$id])) {
            $notes[$id]['archived'] = !$notes[$id]['archived'];
            $notes[$id]['updated_at'] = time();
        }
    } elseif ($action === 'import' && isset($_FILES['import_file'])) {
        $content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import = json_decode($content, true);
        if (is_array($import)) {
            foreach ($import as $n) {
                $id = $n['id'] ?? uniqid();
                $notes[$id] = $n;
            }
        }
    }
    save_notes($notesFile, $notes);
    header('Location: index.php');
    exit;
}

if (isset($_GET['export'])) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="notes.json"');
    echo json_encode($notes, JSON_PRETTY_PRINT);
    exit;
}

uasort($notes, function ($a, $b) {
    if ($a['pinned'] === $b['pinned']) {
        return $b['created_at'] <=> $a['created_at'];
    }
    return $b['pinned'] <=> $a['pinned'];
});
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $id = uniqid();
        $notes[$id] = ['title' => $title, 'content' => $content];
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $notes[$id] = ['title' => $title, 'content' => $content];
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        unset($notes[$id]);
    }
    file_put_contents($notesFile, json_encode($notes, JSON_PRETTY_PRINT));
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notion Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Notion Lite <span id="count"></span></h1>
    <div class="controls">
        <button id="new-note">New Note</button>
        <button id="delete-all">Delete All</button>
        <a href="?export=1" class="btn">Export</a>
        <form method="post" enctype="multipart/form-data" id="import-form">
            <input type="hidden" name="action" value="import">
            <input type="file" name="import_file" accept="application/json">
            <button type="submit">Import</button>
        </form>
        <input type="text" id="search" placeholder="Search...">
        <select id="tag-filter"></select>
        <select id="category-filter"></select>
        <select id="status-filter">
            <option value="">All status</option>
            <option value="active">Active</option>
            <option value="done">Done</option>
            <option value="archived">Archived</option>
        </select>
        <select id="sort">
            <option value="date">Sort by date</option>
            <option value="title">Sort by title</option>
            <option value="priority">Sort by priority</option>
            <option value="due">Sort by due</option>
        </select>
        <button id="layout-toggle">Grid</button>
        <button id="dark-toggle">Dark Mode</button>
    </div>
    <div class="notes">
        <?php foreach ($notes as $id => $note): ?>
        <div class="note" data-id="<?= htmlspecialchars($id) ?>" style="background: <?= htmlspecialchars($note['color'] ?? '#fff') ?>">
            <div class="note-header">
                <h2><?= htmlspecialchars($note['title']) ?></h2>
                <button class="pin" data-id="<?= htmlspecialchars($id) ?>"><?= !empty($note['pinned']) ? 'Unpin' : 'Pin' ?></button>
            </div>
            <div class="dates">C: <?= date('Y-m-d H:i', $note['created_at']) ?> | U: <?= date('Y-m-d H:i', $note['updated_at']) ?></div>
            <div class="tags">Tags: <?= htmlspecialchars(implode(', ', $note['tags'])) ?></div>
            <div>Category: <?= htmlspecialchars($note['category'] ?? '') ?></div>
            <div>Priority: <?= htmlspecialchars($note['priority'] ?? 1) ?></div>
            <?php if (!empty($note['due'])): ?>
            <div>Due: <?= htmlspecialchars($note['due']) ?></div>
            <?php endif; ?>
            <div class="status">
                <?= !empty($note['done']) ? 'Done' : 'Active' ?><?= !empty($note['archived']) ? ' (Archived)' : '' ?>
            </div>
            <?php if (!empty($note['attachment'])): ?>
            <div class="attach"><img src="<?= $note['attachment'] ?>" class="max-w-xs"/></div>
            <?php endif; ?>
            <div class="preview" data-content="<?= htmlspecialchars($note['content']) ?>"></div>
            <div class="note-actions">
                <button class="edit" data-id="<?= htmlspecialchars($id) ?>">Edit</button>
                <button class="duplicate" data-id="<?= htmlspecialchars($id) ?>">Duplicate</button>
                <button class="print" data-id="<?= htmlspecialchars($id) ?>">Print</button>
                <button class="share" data-id="<?= htmlspecialchars($id) ?>">Share</button>
                <button class="done" data-id="<?= htmlspecialchars($id) ?>"><?= !empty($note['done']) ? 'Undo' : 'Done' ?></button>
                <button class="archive" data-id="<?= htmlspecialchars($id) ?>"><?= !empty($note['archived']) ? 'Unarchive' : 'Archive' ?></button>
                <form method="post" class="delete-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                    <button type="submit">Delete</button>
                </form>
            </div>
=======
    <h1>Notion Lite</h1>
    <button id="new-note">New Note</button>
    <div class="notes">
        <?php foreach ($notes as $id => $note): ?>
        <div class="note" data-id="<?= htmlspecialchars($id) ?>">
            <h2><?= htmlspecialchars($note['title']) ?></h2>
            <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
            <button class="edit" data-id="<?= htmlspecialchars($id) ?>">Edit</button>
            <form method="post" class="delete-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <button type="submit">Delete</button>
            </form>

        </div>
        <?php endforeach; ?>
    </div>

    <div id="form-container" style="display:none;">
        <h2 id="form-title">New Note</h2>
        <form method="post" id="note-form">
            <input type="hidden" name="action" value="create" id="form-action">
            <input type="hidden" name="id" id="note-id">
            <label>Title:<br>
                <input type="text" name="title" id="note-title" required>
            </label><br>
            <label>Tags (comma separated):<br>
                <input type="text" name="tags" id="note-tags">
            </label><br>
            <label>Color:<br>
                <input type="color" name="color" id="note-color" value="#ffffff">
            </label>
            <label>Category:<br>
                <input type="text" name="category" id="note-category">
            </label><br>
            <label>Priority:<br>
                <select name="priority" id="note-priority">
                    <option value="1">High</option>
                    <option value="2">Medium</option>
                    <option value="3">Low</option>
                </select>
            </label><br>
            <label>Due date:<br>
                <input type="datetime-local" name="due" id="note-due">
            </label><br>
            <label><input type="checkbox" name="pinned" id="note-pinned"> Pinned</label><br>
            <label><input type="checkbox" name="done" id="note-done"> Done</label><br>
            <label><input type="checkbox" name="archived" id="note-archived"> Archived</label><br>
            <label>Attachment:<br>
                <input type="file" name="attachment" id="note-attachment" accept="image/*">
            </label><br>
            <label>Content:<br>
                <textarea name="content" id="note-content" rows="8" cols="50" required></textarea>
            </label><br>
            <span id="char-count">0</span> characters<br>

            <label>Content:<br>
                <textarea name="content" id="note-content" rows="8" cols="50" required></textarea>
            </label><br>

            <button type="submit">Save</button>
            <button type="button" id="cancel">Cancel</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
var notesData = <?php echo json_encode($notes); ?>;
</script>

<script src="script.js"></script>
</body>
</html>
