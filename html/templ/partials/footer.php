<!-- jQuery -->
<script src="js/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Render any page-specific scripts if they are set
if (isset($page_scripts)) {
    echo $page_scripts;
}
?>

</body>
</html>
