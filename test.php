<?php
require_once 'core/bootstrap.php';

echo "Bootstrap loaded successfully.\n";
echo "Hashids test: " . encodeID(24) . "\n";

echo "My IP address is: " . $_SERVER['REMOTE_ADDR'] . "\n";

echo decodeID('Z59'); // What does this return?