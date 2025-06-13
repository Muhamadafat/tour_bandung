<?php
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle Actions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_status') {
        $id = (int)$_POST['id'];
        $status = clean_input($_POST['status']);
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $message = '<div class="alert alert-success">Status pemesanan berhasil diupdate!</div>';
        }
    }
    
    if ($action == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Pemesanan berhasil dihapus!</div>';
        }
    }
}

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

$where_clause = '';
$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(t.title LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Count total
$count_sql = "SELECT COUNT(*) 
              FROM bookings b 
              JOIN tours t ON b.tour_id = t.id 
              JOIN users u ON b.user_id = u.id 
              $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get bookings
$sql = "SELECT b.*, t.title as tour_title, u.full_name as user_name, u.email as user_email
        FROM bookings b 
        JOIN tours t ON b.tour_id = t.id 
        JOIN users u ON b.user_id = u.id 
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - Admin</title>
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
                    <a href="bookings.php" class="active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Pemesanan</h2>
                    <div class="text-muted">
                        Total: <?= $total_records ?> pemesanan
                    </div>
                </div>

                <?= $message ?>

                <!-- Search & Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-5">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Cari pemesanan..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="bookings.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Pemesan</th>
                                        <th>Tour</th>
                                        <th>Tanggal Booking</th>
                                        <th>Peserta</th>
                                        <th>Total Harga</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data pemesanan</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['id'] ?></td>
                                                <td>
                                                    <?= htmlspecialchars($booking['user_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($booking['tour_title']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></td>
                                                <td><?= $booking['participants'] ?> orang</td>
                                                <td><?= format_rupiah($booking['total_price']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($booking['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-info" onclick="updateStatus(<?= $booking['id'] ?>, '<?= $booking['status'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteBooking(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['user_name']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status Pemesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="status_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status_select" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(id, currentStatus) {
            document.getElementById('status_id').value = id;
            document.getElementById('status_select').value = currentStatus;
            
            var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        }
        
        function deleteBooking(id, userName) {
            if (confirm('Apakah Anda yakin ingin menghapus pemesanan dari "' + userName + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>