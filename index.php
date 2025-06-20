<?php
require_once 'config/database.php';

$auth_message = '';

// Handle Login
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: index.php');
            exit;
        } else {
            $auth_message = '<div class="alert alert-danger">Email atau password salah!</div>';
        }
    } else {
        $auth_message = '<div class="alert alert-warning">Mohon lengkapi email dan password!</div>';
    }
}

// Handle Register
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'register') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($full_name) && !empty($email) && !empty($password)) {
        if ($password === $confirm_password) {
            // Cek email sudah ada atau belum
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $auth_message = '<div class="alert alert-warning">Email sudah terdaftar!</div>';
            } else {
                // Register user baru
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $username = strtolower(str_replace(' ', '_', $full_name)) . rand(100, 999);
                
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'user')");
                
                if ($insert_stmt->execute([$username, $email, $password_hash, $full_name])) {
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;
                    
                    $auth_message = '<div class="alert alert-success">Registrasi berhasil! Selamat datang!</div>';
                    header('Location: index.php');
                    exit;
                } else {
                    $auth_message = '<div class="alert alert-danger">Terjadi kesalahan saat registrasi!</div>';
                }
            }
        } else {
            $auth_message = '<div class="alert alert-warning">Password konfirmasi tidak cocok!</div>';
        }
    } else {
        $auth_message = '<div class="alert alert-warning">Mohon lengkapi semua field!</div>';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Search & Filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Query tours dengan pagination dan filter
$where_clause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (t.title LIKE ? OR t.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category > 0) {
    $where_clause .= " AND t.category_id = ?";
    $params[] = $category;
}

// Hitung total data
$count_sql = "SELECT COUNT(*) FROM tours t $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Ambil data tours
$sql = "SELECT t.*, c.name as category_name 
        FROM tours t 
        LEFT JOIN categories c ON t.category_id = c.id 
        $where_clause 
        ORDER BY t.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// Ambil categories untuk filter
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Bandung - Jelajahi Keindahan Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            color: white;
        }
        .tour-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .tour-card:hover {
            transform: translateY(-5px);
        }
        .search-section {
            background: #f8f9fa;
            padding: 30px 0;
        }
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
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
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
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
                <?php else: ?>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="admin/login.php">
                    <i class="fas fa-user-shield"></i> Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Jelajahi Keindahan Indonesia</h1>
            <p class="lead mb-4">Temukan destinasi wisata terbaik dengan paket tour terpercaya</p>
            <a href="#tours" class="btn btn-primary btn-lg">Lihat Tour</a>
        </div>
    </section>

    <!-- Search & Filter Section -->
    <section class="search-section" id="tours">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Cari destinasi atau lokasi..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="GET">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="0">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Tours Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Paket Tour Populer</h2>
            
            <?php if (empty($tours)): ?>
                <div class="text-center">
                    <h4>Tidak ada tour yang ditemukan</h4>
                    <p>Coba gunakan kata kunci lain atau pilih kategori berbeda</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($tours as $tour): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card tour-card h-100">
                                <img src="<?= $tour['image'] ? 'uploads/' . $tour['image'] : 'https://via.placeholder.com/300x200?text=No+Image' ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($tour['title']) ?>" style="height: 200px; object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($tour['title']) ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($tour['location']) ?>
                                        <br>
                                        <i class="fas fa-clock"></i> <?= htmlspecialchars($tour['duration']) ?>
                                        <br>
                                        <i class="fas fa-users"></i> Max <?= $tour['max_participants'] ?> orang
                                    </p>
                                    <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($tour['description']), 0, 100) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <h6 class="text-primary mb-0"><?= format_rupiah($tour['price']) ?></h6>
                                        <a href="tour_detail.php?id=<?= $tour['id'] ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page-1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $category > 0 ? '&category=' . $category : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $category > 0 ? '&category=' . $category : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page+1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $category > 0 ? '&category=' . $category : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Auth Message -->
    <?php if (!empty($auth_message)): ?>
        <div class="container">
            <?= $auth_message ?>
        </div>
    <?php endif; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sign-in-alt text-primary"></i> Login User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">
                                Belum punya akun? 
                                <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">
                                    Daftar di sini
                                </a>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus text-success"></i> Register User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= isset($_POST['email']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">
                                Sudah punya akun? 
                                <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    Login di sini
                                </a>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Daftar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 Tour Bandung. Jelajahi keindahan Indonesia bersama kami.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto show modal if there's an auth error
        <?php if (!empty($auth_message) && $_POST): ?>
            <?php if (isset($_POST['action']) && $_POST['action'] == 'login'): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                });
            <?php elseif (isset($_POST['action']) && $_POST['action'] == 'register'): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
                    registerModal.show();
                });
            <?php endif; ?>
        <?php endif; ?>
        
        // Auto hide auth message after 5 seconds
        <?php if (!empty($auth_message)): ?>
            setTimeout(function() {
                var alertDiv = document.querySelector('.alert');
                if (alertDiv) {
                    alertDiv.style.transition = 'opacity 0.5s';
                    alertDiv.style.opacity = '0';
                    setTimeout(function() {
                        if (alertDiv.parentNode) {
                            alertDiv.parentNode.removeChild(alertDiv);
                        }
                    }, 500);
                }
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>