<?php
require_once 'config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil booking user
$stmt = $pdo->prepare("SELECT b.*, t.title as tour_title, t.image as tour_image, t.location as tour_location
                       FROM bookings b 
                       JOIN tours t ON b.tour_id = t.id 
                       WHERE b.user_id = ? 
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Tour Bandung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .booking-card:hover {
            transform: translateY(-2px);
        }
        .status-pending { color: #ffc107; }
        .status-confirmed { color: #28a745; }
        .status-cancelled { color: #dc3545; }
        .tour-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain"></i> Tour Bandung
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="my_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-calendar-check text-primary"></i> 
                    My Bookings
                </h2>
                <p class="text-muted">Kelola dan pantau status pemesanan tour Anda</p>
            </div>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <i class="fas fa-inbox fa-5x text-muted mb-4"></i>
                            <h4>Belum Ada Pemesanan</h4>
                            <p class="text-muted mb-4">Anda belum melakukan pemesanan tour apapun</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Tour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card booking-card h-100">
                            <img src="<?= $booking['tour_image'] ? 'uploads/' . $booking['tour_image'] : 'https://via.placeholder.com/300x150?text=No+Image' ?>" 
                                 class="card-img-top tour-img" alt="<?= htmlspecialchars($booking['tour_title']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($booking['tour_title']) ?></h5>
                                <p class="card-text text-muted small">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['tour_location']) ?>
                                </p>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <strong class="text-primary"><?= format_rupiah($booking['total_price']) ?></strong>
                                            <br><small class="text-muted">Total Harga</small>
                                        </div>
                                        <div class="col-6">
                                            <strong class="text-info"><?= $booking['participants'] ?> orang</strong>
                                            <br><small class="text-muted">Peserta</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> 
                                        <strong>Tanggal Tour:</strong> <?= date('d F Y', strtotime($booking['booking_date'])) ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> 
                                        <strong>Dipesan:</strong> <?= date('d F Y H:i', strtotime($booking['created_at'])) ?>
                                    </small>
                                </div>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'pending' ? 'warning' : 'danger') ?> fs-6">
                                            <i class="fas fa-<?= $booking['status'] == 'confirmed' ? 'check-circle' : ($booking['status'] == 'pending' ? 'clock' : 'times-circle') ?>"></i>
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                        <small class="text-muted">ID: #<?= $booking['id'] ?></small>
                                    </div>
                                    
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> Admin akan menghubungi Anda untuk konfirmasi
                                        </small>
                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                        <small class="text-success d-block mt-2">
                                            <i class="fas fa-check-circle"></i> Pemesanan dikonfirmasi. Siap berangkat!
                                        </small>
                                    <?php else: ?>
                                        <small class="text-danger d-block mt-2">
                                            <i class="fas fa-times-circle"></i> Pemesanan dibatalkan
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 Tour Bandung. Jelajahi keindahan Indonesia bersama kami.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>