--TEST--
FPM: Bug #73342 DoS by changing stdin to non-blocking
--SKIPIF--
<?php include "skipif.inc"; ?>
--XFAIL--
stdin can be changed from script to non-blocking, causing accept call to fail
--FILE--
<?php

include "include.inc";

$logfile = __DIR__.'/php-fpm.log.tmp';
$srcfile = __DIR__.'/php-fpm.tmp.php';
$port = 9000+PHP_INT_SIZE;

$cfg = <<<EOT
[global]
error_log = $logfile
[unconfined]
listen = 127.0.0.1:$port
pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOT;

$code = <<<EOT
<?php
stream_set_blocking(fopen('php://stdin', 'r'), false);
EOT;
file_put_contents($srcfile, $code);

$fpm = run_fpm($cfg, $tail);
if (is_resource($fpm)) {
    fpm_display_log($tail, 2);
    $req = run_request('127.0.0.1', $port, $srcfile);
    usleep(100000);
    proc_terminate($fpm);
    echo stream_get_contents($tail);
    fclose($tail);
    proc_close($fpm);
}

?>
Done
--EXPECTF--
[%s] NOTICE: fpm is running, pid %d
[%s] NOTICE: ready to handle connections
[%s] NOTICE: Terminating ...
[%s] NOTICE: exiting, bye-bye!
Done
--CLEAN--
<?php
	$logfile = __DIR__.'/php-fpm.log.tmp';
	$srcfile = __DIR__.'/php-fpm.tmp.php';
    @unlink($logfile);
    @unlink($srcfile);
?>
