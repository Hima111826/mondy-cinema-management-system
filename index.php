<?php
require_once 'db.php';
require_once 'helpers.php';
include 'header.php';

$stmt = $conn->query("SELECT id, title, genre, duration_min, poster_url FROM movies ORDER BY id DESC LIMIT 6");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('index');
  });
</script>
<section class="mc-hero">
<div class="welcome-card">
  <h2>Welcome to MondyCinema 🍿</h2>
  <p>Discover new releases, book seats instantly, and enjoy the show!</p>
  <a href="movie_list.php" class="btn">Browse Movies</a>
  <a href="register.php" class="btn ghost">Create Account</a>
  <a href="contact.php" class="btn ghost">Contact Us</a>
</div>
  </div>
<div class="hero-card">
  <h2 style="margin:0 0 10px">Now Showing</h2>
  <div class="cards">
    <?php
    
    $lockToTitles = ['Kandy Twist', 'The Galactic Voyage'];

    
    $overridePosters = [
      'Kandy Twist'        => 'https://assets-in.bmscdn.com/discovery-catalog/events/et00112802-rstvbzygna-landscape.jpg',       // put file here or use a direct https image URL
      'The Galactic Voyage' => 'https://i.ytimg.com/vi/h57aF12VceY/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLAHUuIIt_7TNjD3f-ifQQAddqDL8A',
    ];
    $placeholder = '/mondycinema/assets/img/gallery/placeholder.jpg';

    
    $byTitle = [];
    foreach ($movies as $m) {
      $byTitle[$m['title']] = $m;
    }

   
    $showMovies = [];
    foreach ($lockToTitles as $t) {
      if (isset($byTitle[$t])) {
        $showMovies[] = $byTitle[$t];
      }
    }

    
    if (count($showMovies) < 2) {
      foreach ($movies as $m) {
       
        if (!in_array($m['title'], array_column($showMovies, 'title'), true)) {
          $showMovies[] = $m;
          if (count($showMovies) === 2) break;
        }
      }
    }

    
    foreach ($showMovies as $m):
     
      $poster = $overridePosters[$m['title']] ?? trim($m['poster_url'] ?? '');
      if ($poster === '') $poster = $placeholder;
    ?>
      <div class="card">
        <img src="<?php echo htmlspecialchars($poster); ?>" alt="poster"
             onerror="this.src='<?php echo $placeholder; ?>'">
        <div class="card-body">
          <h3><?php echo sanitize($m['title']); ?></h3>
          <div>
            <span class="badge"><?php echo sanitize($m['genre'] ?: 'Genre'); ?></span>
            <span class="badge"><?php echo (int)$m['duration_min']; ?> min</span>
          </div>
          <div style="margin-top:10px">
            <a class="btn" href="movie_list.php?focus=<?php echo (int)$m['id']; ?>">View Showtimes</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</section>

<section>
  <h2>Why MondyCinema?</h2>
  <div class="grid-3">
    <div class="card"><div class="card-body"><h3>Seat Selection</h3><p>Choose your exact seats in real-time.</p></div></div>
    <div class="card"><div class="card-body"><h3>Fast Booking</h3><p>Book tickets in a few clicks with instant confirmation.</p></div></div>
    <div class="card"><div class="card-body"><h3>Exclusive Offers</h3><p>Registered users get early access and discounts.</p></div></div>
  </div>
</section>

<?php include 'footer.php'; ?>