#!/usr/bin/php
<?php
/*
    hd3verifylist.php
*/
// Define colors
define('RED',    "\033[0;31m");
define('GREEN',  "\033[0;32m");
define('YELLOW', "\033[0;33m");
define('CYAN',   "\033[0;36m");
define('BOLD',   "\033[1m");
define('RESET',  "\033[0m");




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





function info(string $msg): string {
    // echo CYAN . "[INFO] " . RESET . $msg . "\n";
    return CYAN . $msg . RESET;
}

function success(string $msg): string {
    // echo GREEN . "[OK]   " . RESET . $msg . "\n";
    return GREEN . $msg . RESET;
}

function warn(string $msg): string {
    // echo YELLOW . "[WARN] " . RESET . $msg . "\n";
    return YELLOW . $msg . RESET;
}

function error(string $msg): string {
    // echo RED . BOLD . "[ERR]  " . RESET . $msg . "\n";
    return RED . $msg . RESET;
}

function bold(string $msg): string {
    // echo BOLD . "[BOLD]  " . RESET . $msg . "\n";
    return BOLD . $msg . RESET;
}
