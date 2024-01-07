<?php
// Constants
define('POSTS_PER_PAGE', 30);
define('MAX_POST_LENGTH', 600000);

// Database Connection
try {
    $db = new PDO('sqlite:posts.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, post TEXT NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS replies (id INTEGER PRIMARY KEY AUTOINCREMENT, postId INTEGER NOT NULL, name TEXT NOT NULL, reply TEXT NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP)');
} catch (Exception $e) {
    exit('Error establishing database connection: ' . htmlspecialchars($e->getMessage()));
}

// CSRF Token Generation and Validation (for forms)
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Functions
function validateInput($input, $type = 'string') {
    if ($input === null) {
        return '';
    }
    return trim(htmlspecialchars($input));
}

// Input Handling
$mode = validateInput(filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$postId = filter_input(INPUT_GET, 'postId', FILTER_VALIDATE_INT);
$error = '';

// POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'])) {
        exit('Invalid CSRF token');
    }

    $name = validateInput($_POST['name'] ?: 'Anonymous');
    $post = validateInput($_POST['post']);
    $post_length = mb_strlen($post, 'UTF-8');

    // Debugging: Log the length of the post
    error_log("Length of the post: " . $post_length);

    if (!empty($post) && $post_length <= MAX_POST_LENGTH) {
        try {
            if ($mode === 'reply' && $postId) {
                $stmt = $db->prepare('INSERT INTO replies (postId, name, reply) VALUES (:postId, :name, :reply)');
                $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
                $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt->bindValue(':reply', $post, PDO::PARAM_STR);
            } else {
                $stmt = $db->prepare('INSERT INTO posts (name, post) VALUES (:name, :post)');
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':post', $post);
            }
            $stmt->execute();

            header('Location: ' . $_SERVER['PHP_SELF'] . ($mode === 'reply' && $postId ? "?mode=reply&postId=$postId" : ''));
            exit;
        } catch (Exception $e) {
            $error = 'Error processing your request: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = empty($post) ? 'Post content cannot be empty.' : 'Post exceeds the maximum allowed length of ' . MAX_POST_LENGTH . ' characters. Actual length: ' . $post_length;
    }
}

// Fetch Posts or Replies
if (!$mode) {
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $offset = ($page - 1) * POSTS_PER_PAGE;
    $postsStmt = $db->prepare('SELECT * FROM posts ORDER BY date DESC LIMIT :limit OFFSET :offset');
    $postsStmt->bindValue(':limit', POSTS_PER_PAGE, PDO::PARAM_INT);
    $postsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $postsStmt->execute();
}

if ($mode === 'reply' && $postId) {
    $postStmt = $db->prepare('SELECT * FROM posts WHERE id = :id');
    $postStmt->bindParam(':id', $postId, PDO::PARAM_INT);
    $postStmt->execute();
    $post = $postStmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $error = "The requested post does not exist.";
    } else {
        $repliesStmt = $db->prepare('SELECT * FROM replies WHERE postId = :postId ORDER BY date ASC');
        $repliesStmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $repliesStmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Posting System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($mode === 'reply' && $postId && $post): ?>
        <!-- Reply Mode -->
        <h1>Reply Mode</h1>
        <a href='index.php'>Back to Main Board</a>

        <!-- Reply Submission Form -->
        <div id='postBox' class='infoBox'>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <table>
                    <tr>
                        <td>Name:</td>
                        <td><input type="text" name="name" value="Anonymous"><input type="submit" value="Post"></td>
                    </tr>
                    <tr>
                        <td>Post:</td>
                        <td><textarea name="post" required></textarea></td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Display the Original Post -->
        <div class='reply'>
            <span class='postName'><?= htmlspecialchars($post ? $post['name'] : '') ?></span>
            <p class='postText'><?= nl2br(htmlspecialchars($post ? $post['post'] : '')) ?></p>
        </div>

        <!-- Display Replies to the Post -->
        <div id='replies'>
            <?php while ($reply = $repliesStmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class='reply'>
                    <span class='postName'><?= htmlspecialchars($reply['name']) ?></span>
                    <p class='postText'><?= nl2br(htmlspecialchars($reply['reply'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php elseif ($mode === 'reply' && $postId): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php else: ?>
        <!-- Main Board -->
        <div id='postBox' class='infoBox'>
            <!-- New Post Submission Form -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <table>
                    <tr>
                        <td>Name:</td>
                        <td><input type="text" name="name" value="Anonymous"><input type="submit" value="Post"></td>
                    </tr>
                    <tr>
                        <td>Post:</td>
                        <td><textarea name="post" required></textarea></td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Display Posts and Replies Link -->
        <div id='posts'>
            <?php while ($post = $postsStmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class='reply'>
                    <span class='postName'>
                        <?= htmlspecialchars($post['name']) ?>
                        <a href='index.php?mode=reply&postId=<?= $post['id'] ?>'>Reply</a>
                        (<?= $db->query("SELECT COUNT(*) FROM replies WHERE postId = {$post['id']}")->fetchColumn() ?>)
                    </span>
                    <p class='postText'><?= nl2br(htmlspecialchars($post['post'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class='pagination'>
            <?php
            $totalPosts = $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
            $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
            for ($i = 1; $i <= $totalPages; $i++):
            ?>
                <a href="?page=<?= $i ?>" class="<?= ($i === $page ? 'active' : '') ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</body>
</html>
