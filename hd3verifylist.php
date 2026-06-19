#!/usr/bin/php
<?php
/*
    hd3verifylist.php
*/
require 'hd3-lib.php';

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
    exit(error("Empty input.")) . "\n";
}



// $hash_str = 'hashdeep-baseline-6.2.2.txt';
// $hash_str = $argv[1];
// $hash_str = $hash_str;




// $lines = explode("\n", file_get_contents($hash_str));
$lines = explode("\n", $hash_str);


$cols = str_getcsv(str_replace('%%%% ', '', $lines[1]));



$slash = 0;
$else = 0;
$slash_lines = '';
$else_lines = '';

$j = 5;
$missing = 0;
$matched = [];
$new_list = [];
$unchanged_list = [];
$edited_list = [];
while ($j < count($lines)) {
    $checked = [];

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
            $checked[trim($cols[$i])] = trim($row[$i]);
        }



        $found = R::findOne($tbl_name, 'filename = ?', [$checked['filename']]);

        if (!empty($found) && $checked['md5'] == $found->md5) {
            // echo "{$checked['filename']} found and unchanged.\n";
            $matched[] = $found->id;
            $unchanged_list[] = $checked['filename'];
        } elseif (!empty($found) && $checked['md5'] != $found->md5) {
            // echo "{$checked['filename']} edited.\n";
            $matched[] = $found->id;
            $edited_list[] = $checked['filename'];
        } else {
            // echo "{$checked['filename']} new file.\n";
            $new_list[] = $checked['filename'];
        }




    }


    $j++;

    // break;
    // if ($j > 10) {
    //     break;
    // }
}



if (!empty($matched)) {
    $not_in = '(' .implode(', ', $matched) .')';
}
$missing = R::findAll($tbl_name, 'id NOT IN '.$not_in);
// print_r($missing);



$old_total = R::count($tbl_name);


$new_total = substr_count($hash_str, "\n");


$new_total = $new_total - 6;



echo "-------------------------------\n";
echo "** AUDIT SUMMARY:\n\n";

echo 'Old total: '. $old_total ."\n";
echo 'New total: '. $new_total ."\n";
echo 'Unmodified: '. count($unchanged_list) ."\n";
echo '--' ."\n";
echo 'Modified/edited: '. count($edited_list) ."\n";
echo 'New: '. count($new_list) ."\n";
echo 'Now missing: '. count($missing) ."\n";
echo "\n";


if (!empty($edited_list)) {
    echo "-------------\n";
    // echo "** MODIFIED/EDITED:\n";
    echo bold("** MODIFIED/EDITED:") . "\n";
    // echo implode("\n", $edited_list) ."\n";
    foreach ($edited_list as $line) {
        echo warn('[edited]') . ' ' . $line . "\n";
    }
}

if (!empty($new_list)) {
    echo "-------------\n";
    // echo "** NEW:\n";
    bold("** NEW:") . "\n";
    // echo implode("\n", $new_list) ."\n";
    foreach ($new_list as $line) {
        echo success('[new]') . ' ' . $line . "\n";
    }
}

if (!empty($missing)) {
    echo "-------------\n";
    // echo "** NOW MISSING:\n";
    bold("** NOW MISSING:") . "\n";
    foreach ($missing as $miss) {
        // echo $miss->filename . "\n";
        echo error('[deleted]') . ' ' . $miss->filename . "\n";
    }
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



echo "\n---------------------\n";
echo 'Memory in use: ' .round(memory_get_usage()/1048576, 2) ."M \n";
echo 'Peak use: ' .round(memory_get_peak_usage()/1048576, 2) ."M \n";
echo 'Memory limit: ' .ini_get('memory_limit') ." \n";
echo 'Total execution time: ' .round((microtime(true) - $time_start), 4) ."s \n";



