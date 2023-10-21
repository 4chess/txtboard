<?php
// Define a constant for the number of posts per page
define('POSTS_PER_PAGE', 30);

// Set up the database connection and create tables if they don't exist
try {
    $db = new PDO('sqlite:posts.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, post TEXT NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS replies (id INTEGER PRIMARY KEY AUTOINCREMENT, postId INTEGER NOT NULL, name TEXT NOT NULL, reply TEXT NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP)');
} catch (Exception $e) {
    die('Error establishing database connection: ' . $e->getMessage());
}

// Initialize variables
$mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$postId = filter_input(INPUT_GET, 'postId', FILTER_VALIDATE_INT);

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Anonymous';
    $post = filter_input(INPUT_POST, 'post', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($mode === 'reply' && $postId) {
        // Insert reply into database
        $stmt = $db->prepare('INSERT INTO replies (postId, name, reply) VALUES (:postId, :name, :reply)');
        $stmt->bindParam(':postId', $postId);
        $stmt->bindParam(':reply', $post);
    } else {
        // Insert new post into database
        $stmt = $db->prepare('INSERT INTO posts (name, post) VALUES (:name, :post)');
        $stmt->bindParam(':post', $post);
    }

    $stmt->bindParam(':name', $name);
    $stmt->execute();

    // Redirect to prevent resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . ($mode === 'reply' && $postId ? "?mode=reply&postId=$postId" : ''));
    exit;
}

// Fetch posts for the main board
if (!$mode) {
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $offset = ($page - 1) * POSTS_PER_PAGE;
    $postsStmt = $db->prepare('SELECT * FROM posts ORDER BY date DESC LIMIT :limit OFFSET :offset');
    $postsStmt->bindValue(':limit', POSTS_PER_PAGE, PDO::PARAM_INT);
    $postsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $postsStmt->execute();
}

// Fetch replies if in reply mode
if ($mode === 'reply' && $postId) {
    $postStmt = $db->prepare('SELECT * FROM posts WHERE id = :id');
    $postStmt->bindParam(':id', $postId, PDO::PARAM_INT);
    $postStmt->execute();
    $post = $postStmt->fetch();

    $repliesStmt = $db->prepare('SELECT * FROM replies WHERE postId = :postId ORDER BY date ASC');
    $repliesStmt->bindParam(':postId', $postId, PDO::PARAM_INT);
    $repliesStmt->execute();
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
    <?php if ($mode === 'reply' && $postId): ?>
        <!-- Reply Mode -->
        <h1>Reply Mode</h1>
        <a href='index.php'>Back to Main Board</a>

        <!-- Reply Submission Form -->
        <div id='postBox' class='infoBox'>
            <form method="post">
                <table>
                    <tr>
                        <td>Name:</td>
                        <td><input type="text" name="name" value="Anonymous"><input type="submit" value="Post"></td>
                    </tr>
                    <tr>
                        <td>Post:</td>
                        <td><textarea name="post"></textarea></td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Display the Original Post -->
        <div class='reply'>
            <span class='postName'><?= htmlspecialchars($post['name']) ?></span>
            <p class='postText'><?= nl2br(htmlspecialchars($post['post'])) ?></p>
        </div>

        <!-- Display Replies to the Post -->
        <div id='replies'>
            <?php while ($reply = $repliesStmt->fetch()): ?>
                <div class='reply'>
                    <span class='postName'><?= htmlspecialchars($reply['name']) ?></span>
                    <p class='postText'><?= nl2br(htmlspecialchars($reply['reply'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Main Board -->
        <div id='postBox' class='infoBox'>
            <!-- New Post Submission Form -->
            <form method="post">
                <table>
                    <tr>
                        <td>Name:</td>
                        <td><input type="text" name="name" value="Anonymous"><input type="submit" value="Post"></td>
                    </tr>
                    <tr>
                        <td>Post:</td>
                        <td><textarea name="post"></textarea></td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- Display Posts and Replies Link -->
        <div id='posts'>
            <?php while ($post = $postsStmt->fetch()): ?>
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
