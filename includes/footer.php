</div>
<footer class="site-footer">
    <div class="container py-3 text-center small">
        Campus Trade — a marketplace for students, by students.
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Applies to every <form> on every page automatically — prevents double
// submission (e.g. double-clicking "Register" and creating two accounts)
// and gives the user visual feedback that something is happening.
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        if (form.checkValidity && !form.checkValidity()) return; // let native validation show first
        const btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Please wait...';
        }
    });
});
</script>
</body>
</html>
