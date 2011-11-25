<?php
#
# This is a test program for the portable PHP password hashing framework.
#

require "PasswordHash.php";

header("Content-type: text/plain");

$ok = 0;

# Try to use stronger but system-specific hashes, with a possible fallback to
# the weaker portable hashes.
$t_hasher = new PasswordHash(8, false);

$correct = "test12345";
$hash = $t_hasher->hashPassword($correct);

print "Hash: " . $hash . "\n";

$check = $t_hasher->checkPassword($correct, $hash);
if ($check) $ok++;
print "Check correct: '" . $check . "' (should be '1')\n";

$wrong = "test12346";
$check = $t_hasher->checkPassword($wrong, $hash);
if (!$check) $ok++;
print "Check wrong: '" . $check . "' (should be '0' or '')\n";

unset($t_hasher);


# Force the use of weaker portable hashes.
$t_hasher = new PasswordHash(8, true);

$hash = $t_hasher->hashPassword($correct);

print "Hash: " . $hash . "\n";

$check = $t_hasher->checkPassword($correct, $hash);
if ($check) $ok++;
print "Check correct: '" . $check . "' (should be '1')\n";

$check = $t_hasher->checkPassword($wrong, $hash);
if (!$check) $ok++;
print "Check wrong: '" . $check . "' (should be '0' or '')\n";


# A correct portable hash for "test12345".
$hash = '$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0';

print "Hash: " . $hash . "\n";

$check = $t_hasher->checkPassword($correct, $hash);
if ($check) $ok++;
print "Check correct: '" . $check . "' (should be '1')\n";

$check = $t_hasher->checkPassword($wrong, $hash);
if (!$check) $ok++;
print "Check wrong: '" . $check . "' (should be '0' or '')\n";

if ($ok == 6)
	print "All tests have PASSED\n";
else
	print "Some tests have FAILED\n";

?>
