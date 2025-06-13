<?php
require_once 'config/database.php';

echo "<h2>Testing Image Paths</h2>";

// Check uploads folder
echo "<h3>1. Uploads Folder Check:</h3>";
if (is_dir('uploads/')) {
    echo "<p style='color: green;'>✅ uploads/ folder exists</p>";
    if (is_writable('uploads/')) {
        echo "<p style='color: green;'>✅ uploads/ folder is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ uploads/ folder is NOT writable</p>";
    }
} else {
    echo "<p style='color: red;'>❌ uploads/ folder does NOT exist</p>";
    echo "<p>Creating uploads/ folder...</p>";
    if (mkdir('uploads/', 0755, true)) {
        echo "<p style='color: green;'>✅ uploads/ folder created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create uploads/ folder</p>";
    }
}

// Check .htaccess
echo "<h3>2. .htaccess Check:</h3>";
if (file_exists('uploads/.htaccess')) {
    echo "<p style='color: green;'>✅ uploads/.htaccess exists</p>";
} else {
    echo "<p style='color: orange;'>⚠️ uploads/.htaccess missing</p>";
    echo "<p>Creating .htaccess...</p>";
    $htaccess_content = "# Proteksi folder uploads\nOptions -Indexes\n# Hanya allow gambar\n<Files ~ \"\\.(jpg|jpeg|png|gif)$\">\n    Order allow,deny\n    Allow from all\n</Files>\n<Files ~ \"\\.php$\">\n    Order allow,deny\n    Deny from all\n</Files>";
    if (file_put_contents('uploads/.htaccess', $htaccess_content)) {
        echo "<p style='color: green;'>✅ .htaccess created successfully</p>";
    }
}

// Check tours with images
echo "<h3>3. Tours with Images:</h3>";
$stmt = $pdo->query("SELECT id, title, image FROM tours WHERE image IS NOT NULL AND image != ''");
$tours = $stmt->fetchAll();

if (empty($tours)) {
    echo "<p style='color: orange;'>⚠️ No tours with images found in database</p>";
} else {
    echo "<div class='row'>";
    foreach ($tours as $tour) {
        $image_path = 'uploads/' . $tour['image'];
        $file_exists = file_exists($image_path);
        
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px; width: 300px; display: inline-block;'>";
        echo "<h5>" . htmlspecialchars($tour['title']) . "</h5>";
        echo "<p><strong>Database Image:</strong> " . htmlspecialchars($tour['image']) . "</p>";
        echo "<p><strong>Full Path:</strong> " . $image_path . "</p>";
        echo "<p><strong>File Exists:</strong> " . ($file_exists ? "<span style='color: green;'>✅ Yes</span>" : "<span style='color: red;'>❌ No</span>") . "</p>";
        
        if ($file_exists) {
            echo "<img src='" . $image_path . "' style='max-width: 200px; max-height: 150px; object-fit: cover; border: 1px solid #ccc;' alt='Tour Image'>";
        } else {
            echo "<div style='width: 200px; height: 150px; background: #f0f0f0; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;'>No Image</div>";
        }
        echo "</div>";
    }
    echo "</div>";
}

// Test image URLs
echo "<h3>4. Test Image URLs:</h3>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>Test with placeholder:</p>";
echo "<img src='https://via.placeholder.com/300x200?text=Test+Image' style='max-width: 300px; border: 1px solid #ccc;'>";

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<p><a href='admin/tours.php'>Go to Admin Tours</a></p>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2, h3 { color: #333; }
</style>