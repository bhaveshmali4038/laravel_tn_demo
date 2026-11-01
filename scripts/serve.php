<?php

// Apply upload limits to the actual PHP server process, including on Windows.
// Ten 4 MB branch images plus multipart overhead fit in a 50 MB POST.
$root = dirname(__DIR__);
$address = $argv[1] ?? '127.0.0.1:8010';
if (! preg_match('/\A(?:127\.0\.0\.1|localhost):[0-9]{2,5}\z/', $address)) {
    fwrite(STDERR, "Usage: php scripts/serve.php [127.0.0.1:8010]\n");
    exit(1);
}
// Laravel's development router resolves index.php relative to its working directory.
chdir($root.'/public');
$command = escapeshellarg(PHP_BINARY)
    .' -d upload_max_filesize=4M -d post_max_size=50M -d max_file_uploads=10 -d max_input_vars=50000'
    .' -S '.escapeshellarg($address)
    .' -t '.escapeshellarg($root.'/public')
    .' '.escapeshellarg($root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
passthru($command, $exitCode);
exit($exitCode);
