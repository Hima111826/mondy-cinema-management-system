<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
include __DIR__ . '/header.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$params = [];
$sql = "SELECT m.id, m.title, m.genre, m.duration_min, m.rating, m.poster_url, m.description
        FROM movies m";
if ($search !== '') {
    $sql .= " WHERE m.title LIKE :q OR m.genre LIKE :q";
    $params[':q'] = "%$search%";
}
$sql .= " ORDER BY m.title ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);


$movieIds = array_column($movies, 'id');
$showtimesByMovie = [];
if (!empty($movieIds)) {
    $in = implode(',', array_fill(0, count($movieIds), '?'));
    $st = $conn->prepare("
        SELECT s.id, s.movie_id, s.date_time, s.screen, s.ticket_price, s.total_seats
        FROM showtimes s
        WHERE s.movie_id IN ($in) AND s.date_time >= NOW()
        ORDER BY s.date_time ASC
    ");
    $st->execute($movieIds);
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $showtimesByMovie[$row['movie_id']][] = $row;
    }
}

$focus = isset($_GET['focus']) ? (int)$_GET['focus'] : 0;
?>
<section class="form" style="max-width:800px">
  <h2>Browse Movies</h2>
  <form method="get" action="movie_list.php">
    <label for="q">Search by title or genre</label>
    <input type="text" id="q" name="q" placeholder="e.g. Sci-Fi, Drama, Avatar" value="<?php echo sanitize($search); ?>">
    <br>
    <button class="btn" type="submit">Search</button>
    <a class="btn ghost" href="movie_list.php">Reset</a>
  </form>
</section>

<?php



$overridePosters = [
  'Kandy Twist'        => 'https://assets-in.bmscdn.com/discovery-catalog/events/et00112802-rstvbzygna-landscape.jpg',
  'Maargan'             => 'https://static.toiimg.com/thumb/imgsize-23456,msid-122093236,width-600,resizemode-4/122093236.jpg',      // change key to match your DB (e.g. "Maragn", "Maaragn")
  'The Galactic Voyage' => 'https://i.ytimg.com/vi/h57aF12VceY/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLAHUuIIt_7TNjD3f-ifQQAddqDL8A',
  'Until Down'          => 'https://images.squarespace-cdn.com/content/v1/511eea22e4b06642027a9a99/64602b1b-573f-42d6-a7ed-5177a6422cb8/Until+Dawn.jpg',    // change to "Until Dawn" if that’s your exact title
];


$placeholderPoster = '/mondycinema/assets/img/gallery/placeholder.jpg';
?>
<script>

  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>

<section class="cards">
  <?php foreach ($movies as $m): ?>
    <?php
      
      $poster = trim($m['poster_url'] ?? '');
      if (isset($overridePosters[$m['title']]) && $overridePosters[$m['title']] !== '') {
          $poster = $overridePosters[$m['title']];
      }
      if ($poster === '') {
          $poster = $placeholderPoster;
      }
    ?>
    <div class="card" id="movie-<?php echo (int)$m['id']; ?>" <?php if($focus===$m['id']) echo 'style="outline:2px solid var(--accent)"'; ?>>
      <img
        src="<?php echo htmlspecialchars($poster); ?>"
        alt="poster"
        onerror="this.src='<?php echo $placeholderPoster; ?>'"
      >
      <div class="card-body">
        <h3><?php echo sanitize($m['title']); ?></h3>
        <div>
          <span class="badge"><?php echo sanitize($m['genre'] ?: ''); ?></span>
          <?php if (!empty($m['rating'])): ?><span class="badge"><?php echo sanitize($m['rating']); ?></span><?php endif; ?>
          <?php if (!empty($m['duration_min'])): ?><span class="badge"><?php echo (int)$m['duration_min']; ?> min</span><?php endif; ?>
        </div>
        <p style="color:#cdd3ec;min-height:54px"><?php echo sanitize($m['description'] ?: ''); ?></p>
        <div class="list">
          <table>
            <thead><tr><th>Showtime</th><th>Screen</th><th>Price</th></tr></thead>
            <tbody>
            <?php if (!empty($showtimesByMovie[$m['id']])): ?>
              <?php foreach ($showtimesByMovie[$m['id']] as $s): ?>
                <tr>
                  <td><?php echo date("M d, Y H:i", strtotime($s['date_time'])); ?></td>
                  <td><?php echo sanitize($s['screen']); ?></td>
                  <td>Rs. <?php echo number_format($s['ticket_price'], 2); ?></td>
                </tr>
                <tr>
                  <td colspan="3" style="text-align:center">
                    <a class="btns" href="book.php?showtime_id=<?php echo (int)$s['id']; ?>">Book</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="3" style="color:#9aa0b4">No upcoming showtimes</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($movies)): ?>
    <p>No movies found.</p>
  <?php endif; ?>
</section>

<script>
// Auto-scroll to focus movie if provided
(function(){
  const el = document.getElementById("movie-<?php echo $focus; ?>");
  if (el){ el.scrollIntoView({behavior:"smooth", block:"center"}); }
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
