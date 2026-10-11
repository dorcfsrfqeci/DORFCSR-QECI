<?php
$dir = __DIR__ . '/pages/admin_dashboard';
$files = glob($dir . '/*.php');

$auditLink = '                <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>' . PHP_EOL;

foreach ($files as $file) {
    if (basename($file) === 'audit_trail.php') continue; // Skip the one we already set up correctly or has it

    $content = file_get_contents($file);
    
    // Check if audit trail link already exists
    if (strpos($content, 'audit_trail.php') !== false) {
        continue;
    }

    // Replace the specific block. We need to find the <p>SETTINGS</p> and Account Management link.
    // It looks like there are variations in how Account Management is formatted.
    
    // Let's use preg_replace. We want to insert the audit link right before the Account Management link.
    // We look for: <a href="[something]account_management.php"
    
    $pattern = '/(<a\s+href="[^"]*account_management\.php")/i';
    
    // We'll insert the audit link before the matched string, ensuring proper indentation
    $replacement = '<a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>' . "\n        " . '$1';
    
    $newContent = preg_replace($pattern, $replacement, $content, 1);
    
    if ($newContent !== null && $newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Updated sidebar in " . basename($file) . "\n";
    } else {
        echo "Could not find account_management link in " . basename($file) . "\n";
    }
}
echo "Done.\n";
?>
