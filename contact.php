<?php
ob_start();
require_once __DIR__ . '/includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
include 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Contact Us</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-xl font-medium text-gray-700 mb-3">Get in Touch</h3>
            <p class="text-gray-600 mb-4">Have questions about our products or need assistance with an order? Reach out to us, and we'll get back to you as soon as possible.</p>
            <div class="space-y-3">
                <p><strong>Email:</strong> <a href="mailto:support@petandgarden.com" class="text-green-600 hover:underline">support@petandgarden.com</a></p>
                <p><strong>Phone:</strong> +32 123 456 789 (Mon-Fri, 9am-5pm)</p>
                <p><strong>Address:</strong> Groenstraat 12, 2800 Mechelen, Belgium</p>
            </div>
        </div>
        <div>
            <h3 class="text-xl font-medium text-gray-700 mb-3">Send a Message</h3>
            <form action="#" method="POST" class="space-y-4">
                <div>
                    <label for="name" class="block text-gray-700 mb-1">Name</label>
                    <input type="text" id="name" name="name" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="email" class="block text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="subject" class="block text-gray-700 mb-1">Subject</label>
                    <input type="text" id="subject" name="subject" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="message" class="block text-gray-700 mb-1">Message</label>
                    <textarea id="message" name="message" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required></textarea>
                </div>
                <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800 transition">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 