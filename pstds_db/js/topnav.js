// Topnav.js: Handles top navigation functionality

document.addEventListener('DOMContentLoaded', function () {
    // Delete Notification Button
document.querySelectorAll('.delete-notification-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        var notificationId = this.closest('.notification-container').getAttribute('data-notification-id');
        document.getElementById('notificationId').value = notificationId;
    });
});


    // Mark Single Notification as Read
    document.querySelectorAll('.dropdown-item[data-notification-id]').forEach(function (notificationLink) {
        notificationLink.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default navigation

            const notificationId = this.getAttribute('data-notification-id');
            const href = this.getAttribute('href'); // Original navigation link

            if (!notificationId) {
                // No notification ID; just navigate
                window.location.href = href;
                return;
            }

            // AJAX request to mark notification as read
            fetch('functions/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Notification marked as read.');
                    } else {
                        console.error('Failed to mark notification as read:', data.message);
                    }
                    // Navigate to the original link regardless
                    window.location.href = href;
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = href; // Navigate on error
                });
        });
    });

    // Mark All Notifications as Read
    const markAllReadButton = document.getElementById('markAllRead');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function () {
            fetch('functions/mark-all-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: 'all' }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('All notifications marked as read.');
                        document.querySelectorAll('.unread').forEach(el => el.classList.remove('unread'));
                        const unreadBadge = document.querySelector('.badge.bg-danger');
                        if (unreadBadge) unreadBadge.remove();
                    } else {
                        alert('Failed to mark notifications as read.');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An unexpected error occurred. Please try again.');
                });
        });
    }

    // Search Functionality
    const searchButton = document.getElementById('btnNavbarSearch');
    const searchInput = document.getElementById('searchInput');
    if (searchButton && searchInput) {
        searchButton.addEventListener('click', function () {
            const searchQuery = searchInput.value.trim();
            if (searchQuery) {
                window.location.href = 'search.php?q=' + encodeURIComponent(searchQuery);
            }
        });

        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchButton.click();
            }
        });
    }
});
