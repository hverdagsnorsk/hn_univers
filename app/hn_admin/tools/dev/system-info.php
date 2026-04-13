<?php
echo "<div class='card'>";
echo "<h3>System Info</h3>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Memory Limit: " . ini_get('memory_limit') . "</li>";
echo "<li>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>Post Max Size: " . ini_get('post_max_size') . "</li>";
echo "<li>Timezone: " . date_default_timezone_get() . "</li>";
echo "</ul>";
echo "</div>";