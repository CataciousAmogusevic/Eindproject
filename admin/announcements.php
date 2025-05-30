<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();

// Check if user is admin
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Handle announcement creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    $announcement = $_POST['announcement'];
    
    if (isset($_POST['announcement_id'])) {
        // Update existing announcement
        $stmt = $mysqli->prepare("UPDATE tblannouncement SET announcement = ? WHERE announcement_id = ?");
        $announcement_id = (int)$_POST['announcement_id'];
        $stmt->bind_param("si", $announcement, $announcement_id);
    } else {
        // Create new announcement
        $stmt = $mysqli->prepare("INSERT INTO tblannouncement (announcement) VALUES (?)");
        $stmt->bind_param("s", $announcement);
    }
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Announcement saved successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to save announcement.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle announcement deletion
if (isset($_POST['delete_announcement'])) {
    $announcement_id = (int)$_POST['announcement_id'];
    $stmt = $mysqli->prepare("DELETE FROM tblannouncement WHERE announcement_id = ?");
    $stmt->bind_param("i", $announcement_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Announcement deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete announcement.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Include header after all redirects
include '../includes/header.php';

// Get all announcements
$announcements = $mysqli->query("SELECT * FROM tblannouncement ORDER BY announcement_id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Manage Announcements</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Add/Edit Announcement Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Add New Announcement</h2>
        
        <form method="POST" action="" class="space-y-4">
            <div>
                <label for="announcement" class="block text-sm font-medium text-gray-700 mb-1">
                    Announcement Text
                </label>
                <textarea id="announcement" 
                         name="announcement" 
                         required
                         rows="3"
                         class="w-full border border-gray-300 rounded-md shadow-sm p-2"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" 
                        name="save_announcement"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                    Add Announcement
                </button>
            </div>
        </form>
    </div>
    
    <!-- Announcements List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">All Announcements</h2>
        
        <div class="space-y-4">
            <?php foreach ($announcements as $announcement): ?>
                <div class="border rounded-lg p-4">
                    <p class="text-gray-900 mb-4">
                        <?php echo htmlspecialchars($announcement['announcement']); ?>
                    </p>
                    <div class="flex justify-end space-x-2">
                        <button onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)"
                                class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </button>
                        <form method="POST" action="" class="inline">
                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                            <button type="submit" 
                                    name="delete_announcement"
                                    onclick="return confirm('Are you sure you want to delete this announcement?')"
                                    class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function editAnnouncement(announcement) {
    document.getElementById('announcement').value = announcement.announcement;
    
    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[name="save_announcement"]');
    
    // Add hidden announcement_id field
    let announcementIdInput = form.querySelector('input[name="announcement_id"]');
    if (!announcementIdInput) {
        announcementIdInput = document.createElement('input');
        announcementIdInput.type = 'hidden';
        announcementIdInput.name = 'announcement_id';
        form.appendChild(announcementIdInput);
    }
    announcementIdInput.value = announcement.announcement_id;
    
    // Update button text
    submitButton.textContent = 'Update Announcement';
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include '../includes/footer.php'; ?> 