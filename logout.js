function logoutUser() {
    fetch('/eBoto/logout.php', { method: 'GET', cache: 'no-cache' })
        .then(response => {
            // Redirect to login after logout
            window.location.href = '/eBoto/login.html';
        })
        .catch(err => {
            console.error('Logout failed:', err);
            window.location.href = '/eBoto/login.html';
        });
}

// Prevent browser from showing cached pages after logout
window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
        window.location.href = '/eBoto/login.html';
    }
});
