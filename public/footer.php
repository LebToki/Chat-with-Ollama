<?php
require_once __DIR__ . '/path_helper.php';
$assetsPath = getAssetsPath();
$debugAssets = isset($_GET['debug']) && $_GET['debug'] === 'assets';
?>
    <script src="<?php echo $assetsPath; ?>/libs/jquery/jquery.min.js" onerror="console.error('Failed to load jQuery:', this.src)"></script>
    <script src="<?php echo $assetsPath; ?>/libs/axios/axios.min.js" onerror="console.error('Failed to load Axios:', this.src)"></script>
    <script src="<?php echo $assetsPath; ?>/libs/bootstrap/js/bootstrap.bundle.min.js" onerror="console.error('Failed to load Bootstrap JS:', this.src)"></script>
    <script src="<?php echo $assetsPath; ?>/js/icon-helper.js" onerror="console.error('Failed to load Icon Helper:', this.src)"></script>
    <script src="<?php echo $assetsPath; ?>/js/html-sanitizer.js" onerror="console.error('Failed to load HTML Sanitizer:', this.src)"></script>
    <?php if ($debugAssets): ?>
    <script>
        console.log('=== JS ASSETS DEBUG ===');
        console.log('jQuery:', '<?php echo $assetsPath; ?>/libs/jquery/jquery.min.js');
        console.log('Axios:', '<?php echo $assetsPath; ?>/libs/axios/axios.min.js');
        console.log('Bootstrap JS:', '<?php echo $assetsPath; ?>/libs/bootstrap/js/bootstrap.bundle.min.js');
        console.log('Modern Chat JS:', '<?php echo $assetsPath; ?>/js/modern-chat.js');
        
        // Test if files are accessible
        fetch('<?php echo $assetsPath; ?>/libs/jquery/jquery.min.js')
            .then(r => console.log('jQuery accessible:', r.ok))
            .catch(e => console.error('jQuery error:', e));
    </script>
    <?php endif; ?>
    
    <!-- Footer Credits - Moved to chat-input-container -->
    <!-- Old footer removed to prevent overlay -->
</body>
</html>
