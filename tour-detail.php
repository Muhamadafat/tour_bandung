<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header('Location: index.php');
    exit;
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
                <img src="<?= $tour['image'] ? 'uploads/' . $tour['image'] : 'https://via.placeholder.com/800x400' ?>" 
                     class="tour-image mb-4" alt="<?= htmlspecialchars($tour['title']) ?>">

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
                    
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-primary btn-lg" onclick="alert('Fitur booking akan segera hadir!')">
                            <i class="fas fa-calendar-check"></i> Pesan Sekarang
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Tour
                        </a>
                    </div>

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
                                    <img src="<?= $related['image'] ? 'uploads/' . $related['image'] : 'https://via.placeholder.com/300x150' ?>" 
                                         class="card-img-top related-tour-img" alt="<?= htmlspecialchars($related['title']) ?>">
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

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Tour Bandung. Jelajahi keindahan Indonesia bersama kami.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>