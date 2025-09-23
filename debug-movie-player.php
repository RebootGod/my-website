<?php
// Debug movie player 500 error dan episode play issue
echo "=== MOVIE PLAYER DEBUG ===\n";

echo "1. Check Movie Player Controller:\n";
$moviePlayerController = 'app/Http/Controllers/MoviePlayerController.php';
if (file_exists($moviePlayerController)) {
    echo "   - MoviePlayerController exists: YES\n";

    $content = file_get_contents($moviePlayerController);

    // Check for ->can() issues
    if (strpos($content, '->can(') !== false) {
        echo "   ❌ MoviePlayerController: HAS ->can() ISSUE\n";
        $matches = [];
        preg_match_all('/->can\([^)]+\)/', $content, $matches);
        foreach ($matches[0] as $match) {
            echo "     Found: " . $match . "\n";
        }
    } else {
        echo "   ✅ MoviePlayerController: NO ->can() issues\n";
    }

    // Check for authorization calls
    $authorizeCount = substr_count($content, '$this->authorize(');
    echo "   - Authorization calls: {$authorizeCount}\n";

} else {
    echo "   - MoviePlayerController exists: NO\n";
}

echo "\n2. Check Series Player Controller:\n";
$seriesPlayerController = 'app/Http/Controllers/SeriesPlayerController.php';
if (file_exists($seriesPlayerController)) {
    echo "   - SeriesPlayerController exists: YES\n";

    $content = file_get_contents($seriesPlayerController);

    // Check for ->can() issues
    if (strpos($content, '->can(') !== false) {
        echo "   ❌ SeriesPlayerController: HAS ->can() ISSUE\n";
        $matches = [];
        preg_match_all('/->can\([^)]+\)/', $content, $matches);
        foreach ($matches[0] as $match) {
            echo "     Found: " . $match . "\n";
        }
    } else {
        echo "   ✅ SeriesPlayerController: NO ->can() issues\n";
    }

} else {
    echo "   - SeriesPlayerController exists: NO\n";
}

echo "\n3. Check routes for player:\n";
$webRoutes = file_get_contents('routes/web.php');

// Look for movie player routes
if (strpos($webRoutes, 'movie') !== false && strpos($webRoutes, 'player') !== false) {
    echo "   ✅ Movie player routes found\n";
} else {
    echo "   ❌ Movie player routes missing\n";
}

// Look for series/episode player routes
if (strpos($webRoutes, 'series') !== false && strpos($webRoutes, 'episode') !== false) {
    echo "   ✅ Series/episode routes found\n";
} else {
    echo "   ❌ Series/episode routes missing\n";
}

echo "\n4. Check for any remaining ->can() issues in controllers:\n";
$controllers = glob('app/Http/Controllers/*.php');
$controllers = array_merge($controllers, glob('app/Http/Controllers/*/*.php'));

$foundIssues = false;
foreach ($controllers as $controller) {
    $content = file_get_contents($controller);
    if (strpos($content, '->can(') !== false) {
        echo "   ❌ " . basename($controller) . ": HAS ->can() ISSUE\n";
        $foundIssues = true;

        $matches = [];
        preg_match_all('/->can\([^)]+\)/', $content, $matches);
        foreach ($matches[0] as $match) {
            echo "     Found: " . $match . "\n";
        }
    }
}

if (!$foundIssues) {
    echo "   ✅ No ->can() issues found in controllers\n";
}

echo "\n=== DEBUG COMPLETE ===\n";