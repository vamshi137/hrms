<?php
require_once __DIR__ . "/../middleware/manager_only.php";
include __DIR__ . "/../includes/head.php";
?>

<div class="container py-5">
  <h3 class="fw-bold">🧑‍💼 Manager Dashboard</h3>
  <p class="text-muted">Welcome, <?= e($_SESSION['full_name']) ?></p>

  <div class="card p-4 rounded-4">
    <h5 class="fw-bold">Manager Activities</h5>
    <ul class="mt-3">
      <li>✅ Approve Leave Requests</li>
      <li>✅ Team Attendance View</li>
      <li>✅ Performance Reviews</li>
    </ul>

    <a class="btn btn-primary mt-3" href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
  </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
