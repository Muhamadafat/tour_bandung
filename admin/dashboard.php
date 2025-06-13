<?php
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Statistik dashboard
$stats = [];

// Total tours
$stmt = $pdo->query("SELECT COUNT(*) FROM tours");
$stats['total_tours'] = $stmt->fetchColumn();

// Total categories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// Total bookings
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
$stats['total_bookings'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetchColumn();

// Recent bookings
$stmt = $pdo->query("SELECT b.*, t.title as tour_title, u.full_name as user_name 
                     FROM bookings b 
                     JOIN tours t ON b.tour_id = t.id 
                     JOIN users u ON b.user_id = u.id 
                     ORDER BY b.created_at DESC 
                     LIMIT 5");
$recent_bookings = $stmt->fetchAll();

// Popular tours
$stmt = $pdo->query("SELECT t.title, COUNT(b.id) as booking_count 
                     FROM tours t 
                     LEFT JOIN bookings b ON t.id = b.tour_id 
                     GROUP BY t.id 
                     ORDER BY booking_count DESC 
                     LIMIT 5");
$popular_tours = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Tour Bandung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .content-wrapper {
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <h4 class="text-white text-center mb-4">
                    <i class="fas fa-tachometer-alt"></i> Admin Panel
                </h4>
                <nav>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="tours.php">
                        <i class="fas fa-map-marked-alt"></i> Kelola Tour
                    </a>
                    <a href="categories.php">
                        <i class="fas fa-tags"></i> Kategori
                    </a>
                    <a href="bookings.php">
                        <i class="fas fa-calendar-check"></i> Pemesanan
                    </a>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                    <hr class="text-white">
                    <a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Lihat Website
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 content-wrapper">
            <div class="container-fluid py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <span class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                                <h3><?= $stats['total_tours'] ?></h3>
                                <p class="mb-0">Total Tour</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-tags fa-3x mb-3"></i>
                                <h3><?= $stats['total_categories'] ?></h3>
                                <p class="mb-0">Kategori</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                <h3><?= $stats['total_bookings'] ?></h3>
                                <p class="mb-0">Pemesanan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <h3><?= $stats['total_users'] ?></h3>
                                <p class="mb-0">Pengguna</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Bookings -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-check"></i> Pemesanan Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_bookings)): ?>
                                    <p class="text-muted">Belum ada pemesanan</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama</th>
                                                    <th>Tour</th>
                                                    <th>Status</th>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_bookings as $booking): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                                        <td><?= htmlspecialchars($booking['tour_title']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                                <?= ucfirst($booking['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d/m/Y', strtotime($booking['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <div class="text-end">
                                    <a href="bookings.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Tours -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-star"></i> Tour Populer</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($popular_tours)): ?>
                                    <p class="text-muted">Belum ada data</p>
                                <?php else: ?>
                                    <?php foreach ($popular_tours as $tour): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span><?= htmlspecialchars($tour['title']) ?></span>
                                            <span class="badge bg-primary"><?= $tour['booking_count'] ?> booking</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-end">
                                    <a href="tours.php" class="btn btn-primary btn-sm">Kelola Tour</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>