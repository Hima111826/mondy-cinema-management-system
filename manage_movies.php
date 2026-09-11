<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
session_start();
if (empty($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_movie_id'])) {
    $id = (int)$_POST['delete_movie_id'];
    $conn->prepare("DELETE FROM movies WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = "Movie deleted.";
    header("Location: manage_movies.php");
    exit;
}

$movies = $conn->query("SELECT * FROM movies ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/admin_header.php';
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>

<section>
  <h2>Manage Movies</h2>
  <p><a class="btn btn-primary" href="add_movie.php">Add New Movie</a></p>
  <div class="list">
    <table>
      <thead><tr><th>ID</th><th>Title</th><th>Genre</th><th>Duration</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($movies as $m): ?>
        <tr>
          <td><?php echo (int)$m['id']; ?></td>
          <td><?php echo sanitize($m['title']); ?></td>
          <td><?php echo sanitize($m['genre']); ?></td>
          <td><?php echo (int)$m['duration_min']; ?> min</td>
          <td>
            <a class="btn" href="edit_movie.php?id=<?php echo (int)$m['id']; ?>">Edit</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this movie?');">
              <input type="hidden" name="delete_movie_id" value="<?php echo (int)$m['id']; ?>">
              <button class="btn btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
      </form>
  
  </div>
    <form method="post" action="admin_dashboard.php" style="margin-top:10px">
        <button class="btn" type="submit">Go Back</button>
</section>

<?php include __DIR__ . '/footer.php'; ?>
