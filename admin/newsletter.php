<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Check if user is admin
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Get subscribed customers count
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM tblcustomer WHERE mail_subscription = 1");
$stmt->execute();
$result = $stmt->get_result();
$subscriber_count = $result->fetch_assoc()['count'];

// Handle newsletter submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_newsletter'])) {
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $errors = [];
    
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($content)) $errors[] = 'Content is required';
    
    if (empty($errors)) {
        // First, insert the newsletter
        $stmt = $mysqli->prepare("
            INSERT INTO tblnewsletters (subject, content, sent_date)
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param("ss", $subject, $content);
        
        if ($stmt->execute()) {
            $newsletter_id = $mysqli->insert_id;
            
            // Get all subscribed customers
            $stmt = $mysqli->prepare("
                SELECT customer_id, email, customer_name 
                FROM tblcustomer 
                WHERE mail_subscription = 1
            ");
            $stmt->execute();
            $subscribers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (!empty($subscribers)) {
                // Create recipient records and send emails
                $insert_recipient = $mysqli->prepare("
                    INSERT INTO tblnewsletter_recipients 
                    (newsletter_id, customer_id, status) 
                    VALUES (?, ?, 'sent')
                ");
                
                foreach ($subscribers as $subscriber) {
                    // Insert recipient record
                    $insert_recipient->bind_param("ii", $newsletter_id, $subscriber['customer_id']);
                    $insert_recipient->execute();
                    
                    // Here you would implement your email sending logic
                    // For example, using PHPMailer or the mail() function
                    // mail($subscriber['email'], $subject, $content);
                }
                
                // Update sent_to_count in the newsletters table
                $mysqli->query("
                    UPDATE tblnewsletters 
                    SET sent_to_count = " . count($subscribers) . " 
                    WHERE newsletter_id = " . $newsletter_id
                );
                
                $_SESSION['flash_message'] = 'Newsletter sent successfully to ' . count($subscribers) . ' subscribers.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'No subscribers found.';
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'Failed to save newsletter.';
            $_SESSION['flash_type'] = 'error';
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get previous newsletters
$newsletters_query = $mysqli->query("
    SELECT n.*, COUNT(nr.recipient_id) as recipient_count 
    FROM tblnewsletters n
    LEFT JOIN tblnewsletter_recipients nr ON n.newsletter_id = nr.newsletter_id
    GROUP BY n.newsletter_id, n.subject, n.content, n.sent_date, n.sent_to_count
    ORDER BY n.sent_date DESC
");
$newsletters = $newsletters_query->fetch_all(MYSQLI_ASSOC);

// Include header after all potential redirects
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Newsletter Management</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Subscriber Count -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-2">Current Subscribers</h2>
        <p class="text-3xl font-bold text-green-600"><?php echo $subscriber_count; ?></p>
    </div>
    
    <!-- New Newsletter Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Send New Newsletter</h2>
        
        <form method="POST" action="" class="space-y-4">
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                    Subject
                </label>
                <input type="text" 
                       id="subject" 
                       name="subject" 
                       required
                       class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                    Content
                </label>
                <textarea id="content" 
                          name="content" 
                          rows="10" 
                          required
                          class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" 
                        name="send_newsletter"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                    Send Newsletter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Previous Newsletters -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Previous Newsletters</h2>
        
        <?php if (empty($newsletters)): ?>
            <p class="text-gray-500">No newsletters have been sent yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($newsletters as $newsletter): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('F j, Y g:i A', strtotime($newsletter['sent_date'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($newsletter['subject']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $newsletter['recipient_count']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 