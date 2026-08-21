<?php
$ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
$r = file_get_contents('http://127.0.0.1:8765/admin', false, $ctx);
echo $http_response_header[0] ?? 'no response';
echo "\n";
echo substr($r, 0, 5000);
