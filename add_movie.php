<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
session_start();
if (empty($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $genre = sanitize($_POST['genre'] ?? '');
    $duration = (int)($_POST['duration_min'] ?? 0);
    $rating = sanitize($_POST['rating'] ?? '');
    $poster = sanitize($_POST['poster_url'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $release_date = $_POST['release_date'] ?? null;

    if (!$title) { $error = "Title is required."; }
    else {
        $stmt = $conn->prepare("INSERT INTO movies (title, genre, duration_min, rating, description, poster_url, release_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $genre, $duration, $rating, $description, $poster ?: null, $release_date ?: null]);
        $_SESSION['flash'] = "Movie added.";
        header("Location: manage_movies.php");
        exit;
    }
}

include __DIR__ . '/admin_header.php';
?>

<section class="form">
  <h2>Add Movie</h2>
  <?php if ($error): ?><div class="alert error"><?php echo sanitize($error); ?></div><?php endif; ?>
  <form method="post">
    <label>Title</label><input name="title" required />
    <label>Genre</label><input name="genre" />
    <label>Duration (min)</label><input name="duration_min" type="number" min="1" />
    <label>Rating</label><input name="rating" />
    <label>Poster URL</label><input name="poster_url" />
    <label>Release Date</label><input name="release_date" type="date" />
    <label>Description</label><textarea name="description" rows="4"></textarea>
    <button class="btn btn-primary" type="submit">Add Movie</button>
    <a class="btn ghost" href="manage_movies.php">Cancel</a>
  </form>
</section>

<?php include __DIR__ . '/footer.php'; ?>
