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
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
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
            <label>Content:<br>
                <textarea name="content" id="note-content" rows="8" cols="50" required></textarea>
            </label><br>
            <button type="submit">Save</button>
            <button type="button" id="cancel">Cancel</button>
        </form>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>
