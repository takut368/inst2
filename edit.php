<?php
// edit.php

// Function to sanitize paths
function sanitize_path($path) {
    $path = realpath($path);
    if ($path === false) return false;
    $base = realpath(__DIR__);
    if (strpos($path, $base) !== 0) return false;
    return $path;
}

// Function to list directory contents
function list_dir($dir) {
    $items = scandir($dir);
    $result = [];
    foreach ($items as $item) {
        if ($item === '.') continue;
        if ($item === '..') continue;
        $path = $dir . '/' . $item;
        $result[] = [
            'name' => $item,
            'path' => $path,
            'is_dir' => is_dir($path)
        ];
    }
    return $result;
}

// Get current path
$current_path = isset($_GET['path']) ? $_GET['path'] : '';
$current_path = urldecode($current_path);
$full_path = sanitize_path($current_path);

// If path is invalid, set to root
if ($full_path === false || !is_dir($full_path)) {
    $full_path = realpath(__DIR__);
    $current_path = '';
}

// Handle file editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target = isset($_POST['target']) ? $_POST['target'] : '';
    $target_full = sanitize_path($target);
    if ($target_full === false) {
        $error = "無効なファイルパスです。";
    } else {
        if ($action === 'edit') {
            $content = isset($_POST['content']) ? $_POST['content'] : '';
            file_put_contents($target_full, $content);
            $message = "ファイルが正常に更新されました。";
        } elseif ($action === 'create_file') {
            $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
            if ($filename === '') {
                $error = "ファイル名を入力してください。";
            } else {
                $new_file = $full_path . '/' . basename($filename);
                if (file_exists($new_file)) {
                    $error = "ファイルが既に存在します。";
                } else {
                    file_put_contents($new_file, '');
                    $message = "ファイルが正常に作成されました。";
                }
            }
        } elseif ($action === 'create_dir') {
            $dirname = isset($_POST['dirname']) ? $_POST['dirname'] : '';
            if ($dirname === '') {
                $error = "ディレクトリ名を入力してください。";
            } else {
                $new_dir = $full_path . '/' . basename($dirname);
                if (file_exists($new_dir)) {
                    $error = "ディレクトリが既に存在します。";
                } else {
                    mkdir($new_dir, 0755, true);
                    $message = "ディレクトリが正常に作成されました。";
                }
            }
        } elseif ($action === 'delete') {
            if (is_dir($target_full)) {
                // ディレクトリの削除
                rmdir($target_full);
                $message = "ディレクトリが正常に削除されました。";
            } else {
                // ファイルの削除
                unlink($target_full);
                $message = "ファイルが正常に削除されました。";
            }
        } elseif ($action === 'rename') {
            $new_name = isset($_POST['new_name']) ? $_POST['new_name'] : '';
            if ($new_name === '') {
                $error = "新しい名前を入力してください。";
            } else {
                $new_path = dirname($target_full) . '/' . basename($new_name);
                if (file_exists($new_path)) {
                    $error = "新しい名前のファイルまたはディレクトリが既に存在します。";
                } else {
                    rename($target_full, $new_path);
                    $message = "名前が正常に変更されました。";
                }
            }
        }
    }
    // リダイレクトしてメッセージを表示
    header("Location: edit.php?path=" . urlencode($current_path) . (isset($message) ? "&message=" . urlencode($message) : "") . (isset($error) ? "&error=" . urlencode($error) : ""));
    exit;
}

// Get messages
$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ソースコード管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 全体レイアウト */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        a {
            color: #0078D7;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        /* メッセージ */
        .message {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        /* セクション */
        .section {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* ファイルリスト */
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-list li {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-list li:last-child {
            border-bottom: none;
        }
        .file-actions button {
            margin-left: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #fff;
            font-size: 12px;
        }
        .btn-edit {
            background-color: #0078D7;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-rename {
            background-color: #ffc107;
            color: #000;
        }
        /* フォーム */
        form {
            display: flex;
            flex-direction: column;
            max-width: 400px;
            margin: 0 auto;
        }
        label {
            margin-bottom: 10px;
            color: #555;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        button.submit-btn {
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 16px;
        }
        button.submit-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h1>ソースコード管理</h1>
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo esc($message); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo esc($error); ?></p>
    <?php endif; ?>

    <div class="section">
        <h2>現在のディレクトリ: <?php echo esc($current_path === '' ? '/' : $current_path); ?></h2>
        <ul class="file-list">
            <?php
            $files = list_dir($full_path);
            // 親ディレクトリへのリンク
            if ($current_path !== '') {
                $parent = dirname($current_path);
                echo '<li><a href="edit.php?path=' . urlencode($parent) . '">.. (親ディレクトリ)</a></li>';
            }
            foreach ($files as $file) {
                echo '<li>';
                if ($file['is_dir']) {
                    echo '<a href="edit.php?path=' . urlencode($file['path']) . '">' . esc($file['name']) . '/</a>';
                } else {
                    echo esc($file['name']);
                }
                echo '<div class="file-actions">';
                if (!$file['is_dir']) {
                    echo '<button class="btn-edit" onclick="editFile(\'' . esc($file['path']) . '\')">編集</button>';
                }
                echo '<button class="btn-delete" onclick="deleteFile(\'' . esc($file['path']) . '\')">削除</button>';
                echo '<button class="btn-rename" onclick="renameFile(\'' . esc($file['path']) . '\')">名前変更</button>';
                echo '</div>';
                echo '</li>';
            }
            ?>
        </ul>
    </div>

    <div class="section">
        <h2>新規ファイル作成</h2>
        <form method="post" action="edit.php?path=<?php echo urlencode($current_path); ?>">
            <label>ファイル名：
                <input type="text" name="filename" placeholder="例: newfile.php" required>
            </label>
            <input type="hidden" name="action" value="create_file">
            <button type="submit" class="submit-btn">ファイル作成</button>
        </form>
    </div>

    <div class="section">
        <h2>新規ディレクトリ作成</h2>
        <form method="post" action="edit.php?path=<?php echo urlencode($current_path); ?>">
            <label>ディレクトリ名：
                <input type="text" name="dirname" placeholder="例: new_directory" required>
            </label>
            <input type="hidden" name="action" value="create_dir">
            <button type="submit" class="submit-btn">ディレクトリ作成</button>
        </form>
    </div>

    <!-- 編集モーダル -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>ファイル編集</h2>
            <form id="editForm" method="post" action="edit.php?path=<?php echo urlencode($current_path); ?>">
                <label>内容：
                    <textarea name="content" id="fileContent" rows="20" style="width: 100%;" required></textarea>
                </label>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="target" id="editTarget" value="">
                <button type="submit" class="submit-btn">保存</button>
            </form>
        </div>
    </div>

    <!-- リネームモーダル -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRenameModal()">&times;</span>
            <h2>名前変更</h2>
            <form id="renameForm" method="post" action="edit.php?path=<?php echo urlencode($current_path); ?>">
                <label>新しい名前：
                    <input type="text" name="new_name" id="newName" required>
                </label>
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="target" id="renameTarget" value="">
                <button type="submit" class="submit-btn">名前変更</button>
            </form>
        </div>
    </div>

    <script>
        // モーダル制御
        const editModal = document.getElementById('editModal');
        const renameModal = document.getElementById('renameModal');

        function editFile(path) {
            fetch(path)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('fileContent').value = data;
                    document.getElementById('editTarget').value = path;
                    editModal.style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('ファイルの読み込みに失敗しました。');
                });
        }

        function closeEditModal() {
            editModal.style.display = 'none';
        }

        function renameFile(path) {
            document.getElementById('renameTarget').value = path;
            renameModal.style.display = 'flex';
        }

        function closeRenameModal() {
            renameModal.style.display = 'none';
        }

        function deleteFile(path) {
            if (!confirm('本当に削除してよろしいですか？')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('target', path);

            fetch('edit.php?path=<?php echo urlencode($current_path); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // リロードして変更を反映
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('削除に失敗しました。');
            });
        }
    </script>
</body>
</html>
