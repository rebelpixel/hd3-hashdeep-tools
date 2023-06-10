#!/usr/bin/php
<?php
/*
    hd3update.php



hd3_update () {
    if [ ! -f /home/public_html/"$*"/private/hashdeep-hashes.db ]
    then
        touch /home/public_html/"$*"/private/hashdeep-hashes.db
    fi

    hashdeep -rl -c md5 /home/public_html/"$*"/public/ | hd3update.php
}

hd3_check () {
    if [ ! -f /home/public_html/"$*"/private/hashdeep-hashes.db ]
    then
        exit 1
    fi

    hashdeep -rl -c md5 /home/public_html/"$*"/public/ | hd3verify.php
}

hd3_audit () {
    if [ ! -f /home/public_html/"$*"/private/hashdeep-hashes.db ]
    then
        exit 1
    fi

    hashdeep -rl -c md5 /home/public_html/"$*"/public/ | hd3verifylist.php
}



*/
$time_start = microtime(true);
ini_set('memory_limit','512M');
require 'rb-sqlite.php';

$tbl_name = str_replace('-', '', slugify(basename(dirname(getcwd(), 1))));
$db_path = dirname(getcwd(), 1) . '/private/hashdeep-hashes.db';

R::setup('sqlite:' .$db_path);

$fd = fopen("php://stdin","r");
$hash_str = "";
while ( !feof($fd) ){
    $hash_str .= fread($fd,1024);
}
fclose($fd);


if (empty($hash_str)) {
    exit("Empty input.\n");
}



R::exec("DROP TABLE $tbl_name;");

$lines = explode("\n", $hash_str);

$cols = str_getcsv(str_replace('%%%% ', '', $lines[1]));


$inserted = 0;
$slash = 0;
$else = 0;
$slash_lines = '';
$else_lines = '';
$j = 5;

while ($j < count($lines)) {
    $insert = [];

    if (strpos($lines[$j], '"') !== false || strpos($lines[$j], "'") !== false) {
        $slash++;
        $slash_lines .= $lines[$j] . "\n";
    }

    if (!empty($lines[$j])) {
        $row = str_getcsv($lines[$j]);


        // reconnect the extra columns because of unquoted csv
        if (!empty($row) && count($cols) < count($row)) {
            $extra = count($row) - count($cols);

            for ($k=1; $k <= $extra; $k++) {
                $row[count($cols) -1] .= $row[count($cols) -1 + $k];
            }
        }


        for ($i=0; $i < count($cols); $i++) {
            $insert[trim($cols[$i])] = trim($row[$i]);
        }


        $new = R::dispense("$tbl_name");
        $new->size = $insert['size'];
        $new->md5 = $insert['md5'];
        $new->filename = $insert['filename'];
        $nid = R::store($new);
        if (!empty($nid)) {
           $inserted++;
       }

        if ($j == 5) {
            R::exec("CREATE INDEX idx_filename  ON $tbl_name (filename);");
            R::exec("CREATE INDEX idx_md5  ON $tbl_name (md5);");
        }


    }


    $j++;
    // break;
}


// if ($slash > 0) {
//     echo "-------------------------------\n";
//     echo "slash = $slash\n";
//     echo "$slash_lines\n";
// }

// if ($else > 0) {
//     echo "-------------------------------\n";
//     echo "else = $else\n";
//     echo "$else_lines\n";
// }



echo "\n-------------------------------\n";
echo "Hashes updated ($inserted rows).\n";
echo "-------------------------------\n";
echo 'Memory in use: ' .round(memory_get_usage()/1048576, 2) ."M \n";
echo 'Peak use: ' .round(memory_get_peak_usage()/1048576, 2) ."M \n";
echo 'Memory limit: ' .ini_get('memory_limit') ." \n";
echo 'Total execution time: ' .round((microtime(true) - $time_start), 4) ."s \n";





function slugify($text) {
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

  // trim
  $text = trim($text, '-');

  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // lowercase
  $text = strtolower($text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  if (empty($text)) {
    return 'n-a';
  }

  return $text;
}