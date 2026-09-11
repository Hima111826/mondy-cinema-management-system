<?php
require_once 'db.php';
require_once 'helpers.php';
require_once 'auth.php';
requireLogin();

$userId = (int)($_SESSION['user_id'] ?? 0);


function isBookingPaid(PDO $conn, int $bookingId): bool {
    $q = $conn->prepare("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'paid'");
    $q->execute([$bookingId]);
    return (int)$q->fetchColumn() > 0;
}



function fetchBooking(PDO $conn, int $bookingId, int $userId): ?array {
    $sql = "
      SELECT b.id, b.user_id, b.showtime_id, b.qty, b.status, b.total_price,
             s.ticket_price, s.date_time, s.screen,
             m.title
      FROM bookings b
      JOIN showtimes s ON s.id = b.showtime_id
      JOIN movies m    ON m.id = s.movie_id
      WHERE b.id = ? AND b.user_id = ?
      LIMIT 1
    ";
    $q = $conn->prepare($sql);
    $q->execute([$bookingId, $userId]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    $row['computed_total'] = (float)$row['ticket_price'] * (int)$row['qty'];
    return $row;
}

function getUserBasics(PDO $conn, int $userId): array {
    $q = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1");
    $q->execute([$userId]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'User', 'email' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    
    $card_name = trim($_POST['card_name'] ?? '');
    $card_no   = preg_replace('/\D+/', '', $_POST['card_no'] ?? '');
    $exp       = trim($_POST['exp'] ?? '');
    $cvv       = preg_replace('/\D+/', '', $_POST['cvv'] ?? '');

    if (!$card_name || strlen($card_no) < 12 || !$exp || strlen($cvv) < 3) {
        $_SESSION['flash'] = ['msg' => 'Please enter valid card details.', 'type' => 'error'];
        header("Location: /mondycinema/payment.php?booking_id={$bookingId}");
        exit;
    }

    $bk = fetchBooking($conn, $bookingId, $userId);
    if (!$bk) {
        $_SESSION['flash'] = ['msg' => 'Booking not found.', 'type' => 'error'];
        header("Location: /mondycinema/my_bookings.php"); exit;
    }
    if ($bk['status'] !== 'confirmed') {
        $_SESSION['flash'] = ['msg' => 'This booking is not eligible for payment.', 'type' => 'error'];
        header("Location: /mondycinema/my_bookings.php"); exit;
    }
    if (isBookingPaid($conn, $bookingId)) {
        $_SESSION['flash'] = ['msg' => 'This booking is already paid.', 'type' => 'info'];
        header("Location: /mondycinema/my_bookings.php"); exit;
    }

    $amount = $bk['computed_total'];

    $conn->beginTransaction();
    try {
        
        $paymentId = null;
        try {
            $ins = $conn->prepare("
              INSERT INTO payments (booking_id, amount, method, status, paid_at)
              VALUES (?, ?, 'card', 'paid', NOW())
            ");
            $ins->execute([$bookingId, $amount]);
            $paymentId = (int)$conn->lastInsertId();
        } catch (PDOException $e) {
           
            $ins = $conn->prepare("
              INSERT INTO payments (booking_id, amount, method, status)
              VALUES (?, ?, 'card', 'paid')
            ");
            $ins->execute([$bookingId, $amount]);
            $paymentId = (int)$conn->lastInsertId();
        }

       
        $up = $conn->prepare("UPDATE bookings SET total_price = ? WHERE id = ?");
        $up->execute([$amount, $bookingId]);

        
        try {
            $u = getUserBasics($conn, $userId);
            $subj = "Payment received: #{$paymentId} for booking #{$bookingId}";
            $body = "User {$u['full_name']} ({$u['email']}) paid Rs. " . number_format($amount,2)
                  . " for booking #{$bookingId} (movie: {$bk['title']}, showtime: " . formatDateTime($bk['date_time']) . ").";
            $msgIns = $conn->prepare("
              INSERT INTO contact_messages (user_id, name, email, subject, message, is_read)
              VALUES (?, ?, ?, ?, ?, 0)
            ");
            $msgIns->execute([$userId, $u['full_name'], $u['email'], $subj, $body]);
        } catch (Throwable $ign) {
            
        }

        $conn->commit();

        
        $_SESSION['payment_success'] = [
            'payment_id' => $paymentId,
            'booking_id' => $bookingId,
            'amount'     => $amount,
            'title'      => $bk['title'],
            'date_time'  => $bk['date_time'],
            'qty'        => (int)$bk['qty'],
            'ticket_price' => (float)$bk['ticket_price'],
        ];
        header("Location: /mondycinema/payment.php?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['flash'] = ['msg' => 'Payment failed. Please try again.', 'type' => 'error'];
        header("Location: /mondycinema/payment.php?booking_id={$bookingId}");
        exit;
    }
}


$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
include 'header.php';


if ($success && !empty($_SESSION['payment_success'])):
  $p = $_SESSION['payment_success'];
 
  $prefillSubject = rawurlencode("Payment ID #{$p['payment_id']} for Booking #{$p['booking_id']}");
  $prefillMessage = rawurlencode(
      "Dear Admin,\n\nI have completed my payment.\n\n"
    . "Payment ID: #{$p['payment_id']}\n"
    . "Booking ID: #{$p['booking_id']}\n"
    . "Movie: {$p['title']}\n"
    . "Showtime: " . formatDateTime($p['date_time']) . "\n"
    . "Seats (qty): {$p['qty']}\n"
    . "Amount: Rs. " . number_format($p['amount'],2) . "\n\n"
    . "Please verify. Thank you!"
  );
  $notifyUrl = "/mondycinema/contact.php?subject={$prefillSubject}&message={$prefillMessage}";
  ?>
 

  <section class="form" style="max-width:640px">
    <h2>Payment Successful 🎉</h2>
    <div class="card">
      <div class="card-body">
        <p><strong>Payment ID:</strong> #<?php echo (int)$p['payment_id']; ?></p>
        <p><strong>Booking ID:</strong> #<?php echo (int)$p['booking_id']; ?></p>
        <p><strong>Movie:</strong> <?php echo sanitize($p['title']); ?></p>
        <p><strong>Showtime:</strong> <?php echo formatDateTime($p['date_time']); ?></p>
        <p><strong>Seats:</strong> <?php echo (int)$p['qty']; ?></p>
        <p><strong>Total Paid:</strong> Rs. <?php echo number_format($p['amount'],2); ?></p>

        <div style="margin-top:14px">
          <a class="btn" href="<?php echo $notifyUrl; ?>">Notify Admin (with Payment ID)</a>
          <a class="btn ghost" href="/mondycinema/my_bookings.php">View My Bookings</a>
        </div>
      </div>
    </div>
  </section>
  <?php
  unset($_SESSION['payment_success']);
  include 'footer.php'; exit;
endif;

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($bookingId > 0) {
    $bk = fetchBooking($conn, $bookingId, $userId);
    if (!$bk) {
        echo '<section class="form"><h2>Payment</h2><p>Booking not found.</p></section>';
        include 'footer.php'; exit;
    }
    if ($bk['status'] !== 'confirmed') {
        echo '<section class="form"><h2>Payment</h2><p>This booking is not eligible for payment.</p></section>';
        include 'footer.php'; exit;
    }
    if (isBookingPaid($conn, $bookingId)) {
        echo '<section class="form"><h2>Payment</h2><p>This booking is already paid.</p></section>';
        include 'footer.php'; exit;
    }
    ?>
    <section class="form" style="max-width:560px">
      <h2>Pay for Booking #<?php echo (int)$bk['id']; ?></h2>
      <p><strong>Movie:</strong> <?php echo sanitize($bk['title']); ?><br>
         <strong>Showtime:</strong> <?php echo formatDateTime($bk['date_time']); ?><br>
         <strong>Seats:</strong> <?php echo (int)$bk['qty']; ?><br>
         <strong>Price per seat:</strong> Rs. <?php echo number_format($bk['ticket_price'],2); ?><br>
         <strong>Total:</strong> Rs. <?php echo number_format($bk['computed_total'],2); ?></p>

      <form method="post" autocomplete="off">
        <input type="hidden" name="booking_id" value="<?php echo (int)$bk['id']; ?>">
        <label>Name on Card</label>
        <input name="card_name" required placeholder="e.g. A B PERERA">

        <label>Card Number</label>
        <input name="card_no" inputmode="numeric" minlength="12" maxlength="19" required placeholder="4242 4242 4242 4242">

        <div class="row">
          <div class="col">
            <label>Expiry (MM/YY)</label>
            <input name="exp" required placeholder="08/27">
          </div>
          <div class="col">
            <label>CVV</label>
            <input name="cvv" inputmode="numeric" minlength="3" maxlength="4" required placeholder="123">
          </div>
        </div>
        <button class="btn" type="submit">Pay Rs. <?php echo number_format($bk['computed_total'],2); ?></button>
        <a class="btn ghost" href="/mondycinema/my_bookings.php">Back</a>
      </form>
    </section>
    <?php
    include 'footer.php'; exit;
}


$q = $conn->prepare("
  SELECT b.id, b.qty, b.status, s.ticket_price, s.date_time, m.title
  FROM bookings b
  JOIN showtimes s ON s.id = b.showtime_id
  JOIN movies m ON m.id = s.movie_id
  WHERE b.user_id = ? AND b.status = 'confirmed'
  ORDER BY b.created_at DESC
");
$q->execute([$userId]);
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<section class="list">
  <h2>Payments</h2>
  <p>Note: You can only make a payment after the admin confirms your booking.</p>
  <table>
    <thead>
      <tr><th>#</th><th>Movie</th><th>Showtime</th><th>Qty</th><th>Total</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php
      $any = false;
      foreach ($rows as $r):
          if (isBookingPaid($conn, (int)$r['id'])) continue; 
          $any = true;
          $total = (float)$r['ticket_price'] * (int)$r['qty'];
      ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo sanitize($r['title']); ?></td>
          <td><?php echo formatDateTime($r['date_time']); ?></td>
          <td><?php echo (int)$r['qty']; ?></td>
          <td>Rs. <?php echo number_format($total,2); ?></td>
          <td><a class="btn" href="/mondycinema/payment.php?booking_id=<?php echo (int)$r['id']; ?>">Pay Now</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$any): ?>
        <tr><td colspan="6">No bookings need payment right now.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<?php include 'footer.php'; ?>
