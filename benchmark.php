<?php
/*
==============================================================
                ULTIMATE HOSTING BENCHMARK 
   CPU • CORE • RAM • I/O • IOPS • NETWORK • OPCACHE • LSAPI
==============================================================
*/

header("Content-Type: text/plain");

function title($t){ echo "=== {$t} ===\n"; }

/* ==========================================================
   1) PHP LIMITS
==========================================================*/
title("PHP LIMITS");
echo "memory_limit      : " . ini_get("memory_limit") . "\n";
echo "max_execution_time: " . ini_get("max_execution_time") . " sec\n";
echo "upload_max_filesize: " . ini_get("upload_max_filesize") . "\n";
echo "post_max_size      : " . ini_get("post_max_size") . "\n\n";

/* ==========================================================
   2) MEMORY ALLOCATION TEST
==========================================================*/
title("MEMORY ALLOCATION TEST (max MB allowed)");
$block = str_repeat("A", 1024 * 1024);
$mem=[]; $max=0;
try{
    for($i=1;$i<=3000;$i++){
        $mem[]=$block;
        $max=$i;
    }
}catch(Throwable $e){}
echo "Max allocated memory: {$max} MB\n\n";

/* ==========================================================
   3) CPU SPEED TEST (burst)
==========================================================*/
title("CPU SPEED TEST (500M ops)");
$start=microtime(true);
$sum=0;
for($i=0;$i<500000000;$i++){ $sum+=$i; }
$t=microtime(true)-$start;
echo "Time: {$t} sec\n";
echo "Checksum: {$sum}\n\n";

/* ==========================================================
   4) PARALLEL CORE TEST (detect real cores)
==========================================================*/
title("PARALLEL CORE TEST (4 parallel requests)");

$temp=__DIR__."/_load_tmp.php";
file_put_contents($temp,"<?php sleep(2); echo 'ok'; ?>");

$mh=curl_multi_init(); $hs=[]; $req=4;

$url=(isset($_SERVER['HTTPS'])?'https://':'http://')
    .$_SERVER['HTTP_HOST']
    .rtrim(dirname($_SERVER['REQUEST_URI']),'/')
    ."/_load_tmp.php";

for($i=0;$i<$req;$i++){
    $ch=curl_init($url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_multi_add_handle($mh,$ch);
    $hs[]=$ch;
}

$start=microtime(true);
do{ curl_multi_exec($mh,$running); }while($running);
$tpar=microtime(true)-$start;

foreach($hs as $c){ curl_multi_remove_handle($mh,$c); }
curl_multi_close($mh);
unlink($temp);

echo "Parallel finish time: {$tpar} sec\n";
echo "Interpretation:\n";
echo "  ~8 sec  = 1 core\n";
echo "  ~4 sec  = 2 core\n";
echo "  ~2 sec  = 4 core\n\n";

/* ==========================================================
   5) DISK I/O TEST (20MB sequential)
==========================================================*/
title("DISK I/O TEST (20MB write)");

$tf=__DIR__."/_io_test.bin";
$data=random_bytes(20*1024*1024);
$start=microtime(true);
file_put_contents($tf,$data);
$tw=microtime(true)-$start;
unlink($tf);

echo "Write speed (20MB): {$tw} sec\n";
echo "Interpretation:\n  <1s = NVMe fast\n  1–3s = SSD\n  >4s = HDD/slow\n\n";

/* ==========================================================
   6) OPCACHE STATUS
==========================================================*/
title("OPCACHE STATUS");
if(function_exists("opcache_get_status")){
    $op=opcache_get_status(false);
    if($op && isset($op['memory_usage'])){
        echo "Enabled        : YES\n";
        echo "Used memory    : ".$op['memory_usage']['used_memory']."\n";
        echo "Free memory    : ".$op['memory_usage']['free_memory']."\n";
        echo "Wasted memory  : ".$op['memory_usage']['wasted_memory']."\n";
        echo "Hits           : ".$op['opcache_statistics']['hits']."\n";
        echo "Misses         : ".$op['opcache_statistics']['misses']."\n";
    } else {
        echo "OPcache enabled but restricted.\n";
    }
}else{
    echo "OPcache not available.\n";
}
echo "\n";

/* ==========================================================
   7) PHP-FPM / LSAPI Detection
==========================================================*/
title("PHP ENGINE DETECTION");
ob_start(); phpinfo(INFO_GENERAL); $info=ob_get_clean();
preg_match('/Server API => (.*)/',$info,$m);
$api=trim($m[1]??"Unknown");
echo "Server API: {$api}\n";
if(stripos($api,"fpm")!==false) echo "Engine: PHP-FPM\n";
elseif(stripos($api,"lsapi")!==false) echo "Engine: LiteSpeed LSAPI\n";
else echo "Engine: CGI/Unknown\n";
echo "\n";

/* ==========================================================
   8) NETWORK DOWNLOAD TEST (5MB)
==========================================================*/
title("NETWORK DOWNLOAD TEST (5MB)");
$start=microtime(true);
$d=@file_get_contents("https://speed.cloudflare.com/__down?bytes=5000000");
$td=microtime(true)-$start;
echo $d!==false ? "Download 5MB: {$td} sec\n" : "Blocked by hosting\n";
echo "\n";

/* ==========================================================
   9) NETWORK LATENCY TEST (Google)
==========================================================*/
title("NETWORK LATENCY TEST");
$start=microtime(true);
$g=@file_get_contents("https://www.google.com");
$lat=microtime(true)-$start;
echo $g!==false ? "Latency: {$lat} sec\n" : "Latency test blocked\n";
echo "\n";

/* ==========================================================
   10) IOPS TEST (4KB x 2000 ops)
==========================================================*/
title("IOPS TEST (4KB x 2000 ops)");

$ops=2000;
$tf=__DIR__."/_iops.bin";
$blk=random_bytes(4096);
$start=microtime(true);

for($i=0;$i<$ops;$i++){
    file_put_contents($tf,$blk);
    file_get_contents($tf);
}
@unlink($tf);

$tiops=microtime(true)-$start;
$iops=$ops/$tiops;

echo "Total time: {$tiops} sec\n";
echo "IOPS approx: {$iops} ops/sec\n";
echo "Interpretation:\n";
echo "  > 5000  = NVMe high-IOPS\n";
echo "  2000–5000 = Good SSD/NVMe\n";
echo "  < 1500 = Slow SSD/throttled\n\n";

/* ==========================================================
   11) SUSTAINED CPU (10s throttle detection)
==========================================================*/
title("SUSTAINED CPU TEST (10s)");

$start=microtime(true);
$loop=0;
while(microtime(true)-$start < 10){ $loop++; }
echo "Operations in 10s: {$loop}\n";
echo "Compare with burst test above.\n";
echo "If much lower → CPU throttled.\n\n";

/* ==========================================================
   12) LSAPI BURST TEST (10 parallel)
==========================================================*/
title("LSAPI BURST TEST (10 parallel)");

$burst=__DIR__."/_burst_tmp.php";
file_put_contents($burst,"<?php echo 'x'; ?>");

$mh=curl_multi_init(); $hs=[];

for($i=0;$i<10;$i++){
    $ch=curl_init($url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_multi_add_handle($mh,$ch);
    $hs[]=$ch;
}

$start=microtime(true);
do{ curl_multi_exec($mh,$running); }while($running);
$tburst=microtime(true)-$start;

foreach($hs as $c) curl_multi_remove_handle($mh,$c);
curl_multi_close($mh);
unlink($burst);

echo "Burst 10 req: {$tburst} sec\n";
echo "Interpretation:\n";
echo "  <0.5s → Strong LSAPI\n";
echo "  0.5–1s → Normal\n";
echo "  >2s → Concurrency limited\n\n";

/* ==========================================================
   13) FILE DESCRIPTOR LIMIT TEST
==========================================================*/
title("FILE DESCRIPTOR TEST (open 200 files)");

$ok=0; $fs=[];
for($i=0;$i<200;$i++){
    $f=@fopen(__FILE__,"r");
    if($f){ $fs[]=$f; $ok++; } else break;
}
foreach($fs as $f){ fclose($f); }

echo "Successfully opened: {$ok} descriptors\n";
echo "Interpretation:\n";
echo "  >150 → generous\n";
echo "  80–150 → normal\n";
echo "  <80 → restrictive\n\n";

/* ==========================================================
   14) PROCESS SPAWN LATENCY
==========================================================*/
title("PROCESS SPAWN LATENCY (100 microtime calls)");

$start=microtime(true);
$a=[];
for($i=0;$i<100;$i++){ $a[]=microtime(true); }
$lat=microtime(true)-$start;

echo "Latency: {$lat} sec\n";
echo "Interpretation:\n";
echo "  <0.001 = extremely fast\n";
echo "  0.002–0.006 = normal\n";
echo "  >0.01 = slow engine\n\n";

echo "=== FINISHED (ALL-IN-ONE) ===\n";
?>
