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
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $description])) {
            $message = '<div class="alert alert-success">Kategori berhasil ditambahkan!</div>';
        }
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $id])) {
            $message = '<div class="alert alert-success">Kategori berhasil diupdate!</div>';
        }
    }
    
    if ($action == 'delete') {
        $id = (int)$_POST['id'];
        
        // Cek apakah kategori masih digunakan
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE category_id = ?");
        $check_stmt->execute([$id]);
        $tour_count = $check_stmt->fetchColumn();
        
        if ($tour_count > 0) {
            $message = '<div class="alert alert-danger">Kategori tidak dapat dihapus karena masih digunakan oleh ' . $tour_count . ' tour!</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Kategori berhasil dihapus!</div>';
            }
        }
    }
}

// Get categories with tour count
$stmt = $pdo->query("SELECT c.*, COUNT(t.id) as tour_count 
                     FROM categories c 
                     LEFT JOIN tours t ON c.id = t.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
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
                    <a href="categories.php" class="active">
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
                    <h2>Kelola Kategori</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>

                <?= $message ?>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="25%">Nama</th>
                                        <th width="40%">Deskripsi</th>
                                        <th width="15%">Jumlah Tour</th>
                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data kategori</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?= $category['id'] ?></td>
                                                <td><?= htmlspecialchars($category['name']) ?></td>
                                                <td><?= htmlspecialchars($category['description']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $category['tour_count'] ?> tour</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', <?= $category['tour_count'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
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

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description;
            
            var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            editModal.show();
        }
        
        function deleteCategory(id, name, tourCount) {
            if (tourCount > 0) {
                alert('Kategori "' + name + '" tidak dapat dihapus karena masih digunakan oleh ' + tourCount + ' tour!');
                return;
            }
            
            if (confirm('Apakah Anda yakin ingin menghapus kategori "' + name + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>