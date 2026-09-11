<?php
// edit_movie.php  (admin page)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// protect admin page
if (empty($_SESSION['admin_id'])) {
    redirect('admin_login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['flash'] = 'Invalid movie id.';
    redirect('manage_movies.php');
}

$stmt = $conn->prepare("SELECT * FROM movies WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    $_SESSION['flash'] = 'Movie not found.';
    redirect('manage_movies.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title        = $_POST['title']         ?? '';
    $genre        = $_POST['genre']         ?? '';
    $duration_min = $_POST['duration_min']  ?? '';
    $rating       = $_POST['rating']        ?? '';
    $poster_url   = $_POST['poster_url']    ?? '';
    $description  = $_POST['description']   ?? '';
    $release_date = $_POST['release_date']  ?? '';

    
    $title = trim((string)$title);
    $duration = (int)$duration_min;

    
    $genre        = ($genre        === '') ? null : trim((string)$genre);
    $rating       = ($rating       === '') ? null : trim((string)$rating);
    $poster_val   = ($poster_url   === '') ? null : trim((string)$poster_url);
    $desc_val     = ($description  === '') ? null : trim((string)$description);
    $release_val  = ($release_date === '') ? null : $release_date; 

    if ($title === '') {
        $error = 'Title cannot be empty.';
    } else {
        $upd = $conn->prepare("
            UPDATE movies
               SET title = ?,
                   genre = ?,
                   duration_min = ?,
                   rating = ?,
                   description = ?,
                   poster_url = ?,
                   release_date = ?
             WHERE id = ?
        ");
        $upd->execute([
            $title,
            $genre,
            $duration,
            $rating,
            $desc_val,
            $poster_val,
            $release_val,
            $id
        ]);

        $_SESSION['flash'] = 'Movie updated.';
        redirect('manage_movies.php');
    }
}

include __DIR__ . '/admin_header.php';
?>

<section class="form">
  <h2>Edit Movie</h2>
  <?php if (!empty($error)): ?>
    <div class="alert error"><?php echo sanitize($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Title</label>
    <input name="title" required value="<?php echo sanitize($movie['title']); ?>"/>

    <label>Genre</label>
    <input name="genre" value="<?php echo sanitize($movie['genre']); ?>"/>

    <label>Duration (min)</label>
    <input name="duration_min" type="number" min="1" value="<?php echo (int)$movie['duration_min']; ?>"/>

    <label>Rating</label>
    <input name="rating" value="<?php echo sanitize($movie['rating']); ?>"/>

    <label>Poster URL</label>
    <input name="poster_url" value="<?php echo sanitize($movie['poster_url']); ?>"/>

    <label>Release Date</label>
    <input name="release_date" type="date" value="<?php echo sanitize($movie['release_date']); ?>"/>

    <label>Description</label>
    <textarea name="description" rows="4"><?php echo sanitize($movie['description']); ?></textarea>

    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn ghost" href="manage_movies.php">Cancel</a>
  </form>
</section>
<?php include __DIR__ . '/footer.php'; ?>
