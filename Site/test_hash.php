<?php

// Test simple du hashage
$plainPassword = 'test123';
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

echo "Plain password: " . $plainPassword . "\n";
echo "Hashed password: " . $hashedPassword . "\n";

// Vérifier le hash
if (password_verify($plainPassword, $hashedPassword)) {
    echo "✅ Hash verification successful!\n";
} else {
    echo "❌ Hash verification failed!\n";
}

// Test avec le même mot de passe plusieurs fois
echo "\nTesting multiple hashes of the same password:\n";
for ($i = 1; $i <= 3; $i++) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    echo "Hash $i: " . $hash . "\n";
    echo "Verification $i: " . (password_verify($plainPassword, $hash) ? "✅" : "❌") . "\n";
}
