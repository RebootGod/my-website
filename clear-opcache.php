<?php
// Clear OPcache script
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OPcache cleared successfully!\n";
    } else {
        echo "Failed to clear OPcache\n";
    }
} else {
    echo "OPcache not enabled\n";
}

// Also clear realpath cache
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    echo "Stat cache cleared\n";
}

echo "Cache clearing complete\n";