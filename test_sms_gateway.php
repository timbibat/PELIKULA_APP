<?php
require 'sms_gateway_config.php';

$sms = new SMSGateway();

// Change to YOUR phone number
$test_phone = "+639614244893";
$test_message = "Test from PELIKULA. Gateway working!";

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMS Gateway Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .test-container { max-width: 800px; margin: 0 auto; }
        pre { background: #fff; padding: 1rem; border-radius: 8px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
<div class="test-container">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">🔍 SMS Gateway Debug Test</h2>
            <hr>
            
            <h5>Configuration Check:</h5>
            <div class="alert alert-info">
                <strong>Gateway URL:</strong> http://192.168.1.15:8080<br>
                <strong>Username:</strong> sms<br>
                <strong>Password:</strong> •••••••• (8 characters)<br>
                <strong>Test Phone:</strong> <?= htmlspecialchars($test_phone) ?>
            </div>
            
            <h5>Step 1: Network Test</h5>
            <?php
            $ping_host = '192.168.1.15';
            echo "<p>Testing connection to <code>$ping_host</code>...</p>";
            
            // Test if host is reachable
            $connection = @fsockopen($ping_host, 8080, $errno, $errstr, 5);
            if ($connection) {
                echo "<div class='alert alert-success'>✅ Network connection OK - Port 8080 is open</div>";
                fclose($connection);
            } else {
                echo "<div class='alert alert-danger'>❌ Cannot reach gateway: $errstr ($errno)</div>";
                echo "<p><strong>Troubleshooting:</strong></p>";
                echo "<ul>
                    <li>Make sure Android device is on and connected to WiFi</li>
                    <li>Verify IP address in SMS Gateway app matches: <code>$ping_host</code></li>
                    <li>Check if both devices are on same network</li>
                    <li>Try ping in CMD: <code>ping $ping_host</code></li>
                </ul>";
            }
            ?>
            
            <h5>Step 2: SMS Send Test</h5>
            <?php
            echo "<p>Attempting to send SMS...</p>";
            $result = $sms->sendSMS($test_phone, $test_message);
            
            if ($result['success']):
            ?>
                <div class="alert alert-success">
                    <h4>✅ SUCCESS!</h4>
                    <p>SMS sent to <strong><?= htmlspecialchars($test_phone) ?></strong></p>
                    <p>Check your phone now!</p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4>❌ FAILED</h4>
                    <p><strong>Error:</strong> <?= htmlspecialchars($result['error']) ?></p>
                </div>
                
                <h5 class="mt-4">🛠️ Troubleshooting Steps:</h5>
                <ol>
                    <li><strong>Check Android SMS Gateway App:</strong>
                        <ul>
                            <li>App is running (not force-stopped)</li>
                            <li>Port is set to <code>8080</code></li>
                            <li>Username: <code>sms</code></li>
                            <li>Password: <code>88888888</code> (verify character count!)</li>
                            <li>IP shown in app matches: <code>192.168.1.15</code></li>
                        </ul>
                    </li>
                    <li><strong>Check Network:</strong>
                        <ul>
                            <li>Both devices on same WiFi network</li>
                            <li>WiFi not in "AP Isolation" mode</li>
                            <li>Firewall not blocking port 8080</li>
                        </ul>
                    </li>
                    <li><strong>Check Phone Number Format:</strong>
                        <ul>
                            <li>Must start with +63 or 09</li>
                            <li>Must be 13 digits total (+639XXXXXXXXX)</li>
                        </ul>
                    </li>
                    <li><strong>Check Android Permissions:</strong>
                        <ul>
                            <li>SMS permission granted</li>
                            <li>Battery optimization disabled for app</li>
                            <li>Background data allowed</li>
                        </ul>
                    </li>
                </ol>
            <?php endif; ?>
            
            <h5 class="mt-4">Full Response Details:</h5>
            <pre><?php print_r($result); ?></pre>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">← Back to Home</a>
                <button onclick="location.reload()" class="btn btn-success">🔄 Test Again</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>