<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$success = false;

if ($id == 0) {
    header('Location: index.php');
    exit;
}

// Handle booking submission
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'book_tour') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $booking_date = clean_input($_POST['booking_date']);
    $participants = (int)$_POST['participants'];
    
    if (!empty($full_name) && !empty($email) && !empty($booking_date) && $participants > 0) {
        // Cek apakah user sudah login
        if (!isset($_SESSION['user_id'])) {
            $message = '<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                Silakan login terlebih dahulu untuk melakukan pemesanan.
            </div>';
        } else {
        // Ambil data tour untuk hitung total harga
        $tour_stmt = $pdo->prepare("SELECT price FROM tours WHERE id = ?");
        $tour_stmt->execute([$id]);
        $tour_data = $tour_stmt->fetch();
        
        if ($tour_data) {
            $total_price = $tour_data['price'] * $participants;
            $user_id = $_SESSION['user_id'];
            
            // Insert booking langsung dengan user yang sudah login
            $booking_stmt = $pdo->prepare("INSERT INTO bookings (user_id, tour_id, booking_date, participants, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            
            if ($booking_stmt->execute([$user_id, $id, $booking_date, $participants, $total_price])) {
                $success = true;
                $booking_id = $pdo->lastInsertId();
                $message = '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Pemesanan Berhasil!</strong><br>
                    ID Booking: <strong>#' . $booking_id . '</strong><br>
                    Total: <strong>' . format_rupiah($total_price) . '</strong><br>
                    Status: <strong>Pending</strong> (Menunggu konfirmasi admin)
                </div>';
            } else {
                $message = '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Terjadi kesalahan saat memproses pemesanan. Silakan coba lagi.
                </div>';
            }
        }
        }
    } else {
        $message = '<div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Mohon lengkapi semua field yang diperlukan.
        </div>';
    }
}

// Ambil detail tour
$stmt = $pdo->prepare("SELECT t.*, c.name as category_name 
                       FROM tours t 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       WHERE t.id = ?");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    header('Location: index.php');
    exit;
}

// Ambil tour terkait
$related_stmt = $pdo->prepare("SELECT * FROM tours 
                               WHERE category_id = ? AND id != ? 
                               LIMIT 3");
$related_stmt->execute([$tour['category_id'], $id]);
$related_tours = $related_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tour['title']) ?> - Tour Bandung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tour-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .price-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .related-tour-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .booking-form {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
            margin-top: 15px;
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
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="admin/login.php">Admin</a>
            </div>
        </div>
    </nav>

    <!-- Tour Detail -->
    <div class="container my-5">
        <?= $message ?>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($tour['title']) ?></li>
                    </ol>
                </nav>

                <!-- Tour Image -->
                <img src="<?= $tour['image'] ? 'uploads/' . $tour['image'] : 'https://via.placeholder.com/800x400?text=No+Image' ?>" 
                     class="tour-image mb-4" alt="<?= htmlspecialchars($tour['title']) ?>"
                     onerror="this.src='https://via.placeholder.com/800x400?text=No+Image'">

                <!-- Tour Info -->
                <h1 class="mb-3"><?= htmlspecialchars($tour['title']) ?></h1>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <i class="fas fa-map-marker-alt text-primary"></i>
                        <strong>Lokasi:</strong><br>
                        <?= htmlspecialchars($tour['location']) ?>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-clock text-primary"></i>
                        <strong>Durasi:</strong><br>
                        <?= htmlspecialchars($tour['duration']) ?>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-users text-primary"></i>
                        <strong>Max Peserta:</strong><br>
                        <?= $tour['max_participants'] ?> orang
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-tag text-primary"></i>
                        <strong>Kategori:</strong><br>
                        <?= htmlspecialchars($tour['category_name']) ?>
                    </div>
                </div>

                <!-- Description -->
                <h4>Deskripsi Tour</h4>
                <p class="text-justify"><?= nl2br(htmlspecialchars($tour['description'])) ?></p>
            </div>

            <div class="col-md-4">
                <!-- Price Box -->
                <div class="price-box sticky-top">
                    <h3 class="text-center text-primary mb-3"><?= format_rupiah($tour['price']) ?></h3>
                    <p class="text-center text-muted">per orang</p>
                    
                    <?php if ($success): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="text-success"><strong>Pemesanan Berhasil!</strong></p>
                            <a href="my_bookings.php" class="btn btn-success mb-2">
                                <i class="fas fa-calendar-check"></i> Lihat My Bookings
                            </a>
                            <br>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary btn-lg w-100 mb-2" data-bs-toggle="modal" data-bs-target="#bookingModal">
                                <i class="fas fa-calendar-check"></i> Pesan Sekarang
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Login Required</strong><br>
                                Silakan login terlebih dahulu untuk melakukan pemesanan
                            </div>
                            <a href="index.php" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="fas fa-sign-in-alt"></i> Login untuk Pesan
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Tour
                    </a>

                    <hr>
                    
                    <h6>Informasi Tambahan:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success"></i> Include transportasi</li>
                        <li><i class="fas fa-check text-success"></i> Include makan</li>
                        <li><i class="fas fa-check text-success"></i> Guide berpengalaman</li>
                        <li><i class="fas fa-check text-success"></i> Asuransi perjalanan</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Related Tours -->
        <?php if (!empty($related_tours)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h4 class="mb-4">Tour Serupa</h4>
                    <div class="row">
                        <?php foreach ($related_tours as $related): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <img src="<?= $related['image'] ? 'uploads/' . $related['image'] : 'https://via.placeholder.com/300x150?text=No+Image' ?>" 
                                         class="card-img-top related-tour-img" alt="<?= htmlspecialchars($related['title']) ?>"
                                         onerror="this.src='https://via.placeholder.com/300x150?text=No+Image'">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($related['title']) ?></h6>
                                        <p class="card-text small text-muted"><?= htmlspecialchars($related['location']) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-primary"><?= format_rupiah($related['price']) ?></span>
                                            <a href="tour_detail.php?id=<?= $related['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pesan Tour: <?= htmlspecialchars($tour['title']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="book_tour">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '' ?>" 
                                   <?= isset($_SESSION['user_name']) ? 'readonly' : '' ?> required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '' ?>" 
                                   <?= isset($_SESSION['user_email']) ? 'readonly' : '' ?> required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Tour *</label>
                                <input type="date" name="booking_date" class="form-control" 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Peserta *</label>
                                <select name="participants" class="form-select" id="participants" required>
                                    <?php for($i = 1; $i <= min(10, $tour['max_participants']); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> orang</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Harga</label>
                                <input type="text" id="total_price" class="form-control" readonly 
                                       value="<?= format_rupiah($tour['price']) ?>">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Catatan:</strong> Setelah pemesanan, admin akan menghubungi Anda untuk konfirmasi dan pembayaran.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Pemesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Tour Bandung. Jelajahi keindahan Indonesia bersama kami.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate total price dynamically
        document.getElementById('participants').addEventListener('change', function() {
            const participants = parseInt(this.value);
            const pricePerPerson = <?= $tour['price'] ?>;
            const totalPrice = participants * pricePerPerson;
            
            document.getElementById('total_price').value = 'Rp ' + totalPrice.toLocaleString('id-ID');
        });
    </script>
</body>
</html>