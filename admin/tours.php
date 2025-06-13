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
    
    if ($action == 'add') {
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $price = (float)$_POST['price'];
        $duration = clean_input($_POST['duration']);
        $location = clean_input($_POST['location']);
        $max_participants = (int)$_POST['max_participants'];
        $category_id = (int)$_POST['category_id'];
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = upload_file($_FILES['image']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO tours (title, description, price, duration, location, max_participants, category_id, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $price, $duration, $location, $max_participants, $category_id, $image])) {
            $message = '<div class="alert alert-success">Tour berhasil ditambahkan!</div>';
        }
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $price = (float)$_POST['price'];
        $duration = clean_input($_POST['duration']);
        $location = clean_input($_POST['location']);
        $max_participants = (int)$_POST['max_participants'];
        $category_id = (int)$_POST['category_id'];
        
        // Handle image upload
        $image_update = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $new_image = upload_file($_FILES['image']);
            if ($new_image) {
                $image_update = ', image = ?';
            }
        }
        
        $sql = "UPDATE tours SET title = ?, description = ?, price = ?, duration = ?, location = ?, max_participants = ?, category_id = ?" . $image_update . " WHERE id = ?";
        $params = [$title, $description, $price, $duration, $location, $max_participants, $category_id];
        
        if ($image_update) {
            $params[] = $new_image;
        }
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = '<div class="alert alert-success">Tour berhasil diupdate!</div>';
        }
    }
    
    if ($action == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Tour berhasil dihapus!</div>';
        }
    }
}

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$where_clause = '';
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE t.title LIKE ? OR t.location LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Count total
$count_sql = "SELECT COUNT(*) FROM tours t $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get tours
$sql = "SELECT t.*, c.name as category_name 
        FROM tours t 
        LEFT JOIN categories c ON t.category_id = c.id 
        $where_clause 
        ORDER BY t.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// Get categories
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tour - Admin</title>
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
        .tour-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
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
                    <a href="tours.php" class="active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Tour</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTourModal">
                        <i class="fas fa-plus"></i> Tambah Tour
                    </button>
                </div>

                <?= $message ?>

                <!-- Search & Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-8">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Cari tour..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="tours.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tours Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Judul</th>
                                        <th>Lokasi</th>
                                        <th>Harga</th>
                                        <th>Kategori</th>
                                        <th>Max Peserta</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tours)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data tour</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tours as $tour): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= $tour['image'] ? '../uploads/' . $tour['image'] : 'https://via.placeholder.com/80x60' ?>" 
                                                         class="tour-img" alt="<?= htmlspecialchars($tour['title']) ?>">
                                                </td>
                                                <td><?= htmlspecialchars($tour['title']) ?></td>
                                                <td><?= htmlspecialchars($tour['location']) ?></td>
                                                <td><?= format_rupiah($tour['price']) ?></td>
                                                <td><?= htmlspecialchars($tour['category_name']) ?></td>
                                                <td><?= $tour['max_participants'] ?> orang</td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editTour(<?= htmlspecialchars(json_encode($tour)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTour(<?= $tour['id'] ?>, '<?= htmlspecialchars($tour['title']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                                            <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
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

    <!-- Add Tour Modal -->
    <div class="modal fade" id="addTourModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Tour Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Judul Tour</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga</label>
                                    <input type="number" name="price" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Durasi</label>
                                    <input type="text" name="duration" class="form-control" placeholder="2 Hari 1 Malam" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Max Peserta</label>
                                    <input type="number" name="max_participants" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" name="location" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Gambar</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Tour Modal -->
    <div class="modal fade" id="editTourModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editTourForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Judul Tour</label>
                                    <input type="text" name="title" id="edit_title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="category_id" id="edit_category_id" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Durasi</label>
                                    <input type="text" name="duration" id="edit_duration" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Max Peserta</label>
                                    <input type="number" name="max_participants" id="edit_max_participants" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" name="location" id="edit_location" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Gambar (Kosongkan jika tidak ingin mengganti)</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                            </div>
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
        function editTour(tour) {
            document.getElementById('edit_id').value = tour.id;
            document.getElementById('edit_title').value = tour.title;
            document.getElementById('edit_description').value = tour.description;
            document.getElementById('edit_price').value = tour.price;
            document.getElementById('edit_duration').value = tour.duration;
            document.getElementById('edit_location').value = tour.location;
            document.getElementById('edit_max_participants').value = tour.max_participants;
            document.getElementById('edit_category_id').value = tour.category_id;
            
            var editModal = new bootstrap.Modal(document.getElementById('editTourModal'));
            editModal.show();
        }
        
        function deleteTour(id, title) {
            if (confirm('Apakah Anda yakin ingin menghapus tour "' + title + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>