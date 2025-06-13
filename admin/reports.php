<?php
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Generate PDF Report
if (isset($_GET['generate_pdf'])) {
    // Simple PDF generation using TCPDF alternative or basic HTML to PDF
    $report_type = $_GET['type'] ?? 'tours';
    
    if ($report_type == 'tours') {
        $stmt = $pdo->query("SELECT t.*, c.name as category_name 
                            FROM tours t 
                            LEFT JOIN categories c ON t.category_id = c.id 
                            ORDER BY t.created_at DESC");
        $data = $stmt->fetchAll();
        $title = 'Laporan Data Tour';
    } elseif ($report_type == 'bookings') {
        $stmt = $pdo->query("SELECT b.*, t.title as tour_title, u.full_name as user_name 
                            FROM bookings b 
                            JOIN tours t ON b.tour_id = t.id 
                            JOIN users u ON b.user_id = u.id 
                            ORDER BY b.created_at DESC");
        $data = $stmt->fetchAll();
        $title = 'Laporan Data Pemesanan';
    } else {
        $stmt = $pdo->query("SELECT c.*, COUNT(t.id) as tour_count 
                            FROM categories c 
                            LEFT JOIN tours t ON c.id = t.category_id 
                            GROUP BY c.id 
                            ORDER BY c.name");
        $data = $stmt->fetchAll();
        $title = 'Laporan Data Kategori';
    }
    
    // Set headers for PDF download
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?= $title ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Tour Bandung</h1>
            <h2><?= $title ?></h2>
            <p>Tanggal: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <table>
            <?php if ($report_type == 'tours'): ?>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Tour</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Harga</th>
                        <th>Max Peserta</th>
                        <th>Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $tour): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($tour['title']) ?></td>
                            <td><?= htmlspecialchars($tour['category_name']) ?></td>
                            <td><?= htmlspecialchars($tour['location']) ?></td>
                            <td class="text-right"><?= format_rupiah($tour['price']) ?></td>
                            <td class="text-center"><?= $tour['max_participants'] ?></td>
                            <td><?= htmlspecialchars($tour['duration']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php elseif ($report_type == 'bookings'): ?>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pemesan</th>
                        <th>Tour</th>
                        <th>Tanggal Booking</th>
                        <th>Peserta</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $booking): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($booking['user_name']) ?></td>
                            <td><?= htmlspecialchars($booking['tour_title']) ?></td>
                            <td><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></td>
                            <td class="text-center"><?= $booking['participants'] ?></td>
                            <td class="text-right"><?= format_rupiah($booking['total_price']) ?></td>
                            <td class="text-center"><?= ucfirst($booking['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php else: ?>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Tour</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $category): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['description']) ?></td>
                            <td class="text-center"><?= $category['tour_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
        </table>
        
        <div style="margin-top: 50px; text-align: right;">
            <p>Bandung, <?= date('d F Y') ?></p>
            <br><br>
            <p>Administrator</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Statistik untuk dashboard laporan
$stats = [];

// Tours stats
$stmt = $pdo->query("SELECT COUNT(*) as total, AVG(price) as avg_price FROM tours");
$tour_stats = $stmt->fetch();
$stats['tours'] = $tour_stats;

// Bookings stats
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(total_price) as total_revenue FROM bookings WHERE status = 'confirmed'");
$booking_stats = $stmt->fetch();
$stats['bookings'] = $booking_stats;

// Monthly bookings
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                     FROM bookings 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                     ORDER BY month DESC");
$monthly_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin</title>
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
        .content-wrapper {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .report-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
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
                    <a href="dashboard.php">
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
                    <a href="reports.php" class="active">
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
                <h2 class="mb-4">Laporan & Statistik</h2>

                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                                <h4><?= $stats['tours']['total'] ?> Tour</h4>
                                <p class="mb-0">Rata-rata harga: <?= format_rupiah($stats['tours']['avg_price']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                <h4><?= $stats['bookings']['total'] ?> Pemesanan</h4>
                                <p class="mb-0">Total pendapatan: <?= format_rupiah($stats['bookings']['total_revenue'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                <h4>Tren Bulanan</h4>
                                <p class="mb-0"><?= count($monthly_bookings) ?> bulan terakhir</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Reports -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-file-pdf"></i> Generate Laporan PDF</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-map-marked-alt fa-2x text-primary mb-3"></i>
                                                <h6>Laporan Tour</h6>
                                                <p class="text-muted small">Daftar semua tour beserta detail</p>
                                                <a href="?generate_pdf=1&type=tours" target="_blank" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download"></i> Download PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-calendar-check fa-2x text-success mb-3"></i>
                                                <h6>Laporan Pemesanan</h6>
                                                <p class="text-muted small">Data pemesanan dan pendapatan</p>
                                                <a href="?generate_pdf=1&type=bookings" target="_blank" class="btn btn-success btn-sm">
                                                    <i class="fas fa-download"></i> Download PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-tags fa-2x text-warning mb-3"></i>
                                                <h6>Laporan Kategori</h6>
                                                <p class="text-muted small">Statistik kategori tour</p>
                                                <a href="?generate_pdf=1&type=categories" target="_blank" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-download"></i> Download PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trends -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-line"></i> Tren Pemesanan Bulanan</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($monthly_bookings)): ?>
                                    <p class="text-muted">Belum ada data pemesanan</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Bulan</th>
                                                    <th>Pemesanan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthly_bookings as $month_data): ?>
                                                    <tr>
                                                        <td><?= date('M Y', strtotime($month_data['month'] . '-01')) ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?= $month_data['count'] ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-info-circle"></i> Ringkasan Statistik</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <h5 class="text-primary"><?= $stats['tours']['total'] ?></h5>
                                        <small class="text-muted">Total Tour</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h5 class="text-success"><?= $stats['bookings']['total'] ?></h5>
                                        <small class="text-muted">Total Pemesanan</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h5 class="text-warning"><?= format_rupiah($stats['tours']['avg_price']) ?></h5>
                                        <small class="text-muted">Rata-rata Harga Tour</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h5 class="text-info"><?= format_rupiah($stats['bookings']['total_revenue'] ?? 0) ?></h5>
                                        <small class="text-muted">Total Pendapatan</small>
                                    </div>
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