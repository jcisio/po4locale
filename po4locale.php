<?php
/**
 * Replace button/menu/window... text with its translation in software
 * so that there is consistence between the manual and the software itself.
 *
 * Written by jcisio, 2010, licensed under GPLv2
 */
$T = array();
$LOCALE = isset($argv[1]) ? $argv[1] : 'vi';
foreach (glob('../rosetta-lucid/'. $LOCALE .'/LC_MESSAGES/*.po') as $filename) {
  parse_file($filename);
}

$T2 = array();
foreach ($T as $key => $values) {
  $T2[$key] = max_key($values);
}

$c = count($T);
$before = memory_get_usage();
unset($T);
$after = memory_get_usage();

$data = file_get_contents('po/'. $LOCALE .'.po');
$data = preg_replace_callback('#\\\\(application|menu|button|checkbox|tab|dropdown|window|textfield)\{([^\{]+?)\}#', 'po4local_replace', $data);
rename('po/'. $LOCALE .'.po', 'po/'. $LOCALE .'.bak.po');
file_put_contents('po/'. $LOCALE .'.po', $data);

printf("%d strings, %d KB used, %d title replaced.\n\n", $c, ($before - $after)/1024, po4local_item_replace());

function parse_file($filename) {
  global $T;
  
  $data = file($filename);
  $k = 0;
  for ($k = 0, $l = count($data); $k < $l; $k++) {
    // we take care of only one line strings
    if (preg_match('/^msgid "(.+?)"\s*$/', $data[$k++], $mid)) {
      if (preg_match('/^msgstr "(.+?)"\s*$/', $data[$k++], $mstr)) {
        $src = stripslashes($mid[1]);
        $dst = stripslashes($mstr[1]);
        // no string that has more than 4 words is considered
        if (substr_count($src, ' ') > 3) {
          continue;
        }

        if (! isset($T[$src])) {
          $T[$src] = array($dst => 1);
        }
        elseif (! isset($T[$src][$dst])) {
          $T[$src][$dst] = 1;
        }
        else {
          $T[$src][$dst]++;
        }
      }
    }
  }
}

function max_key($array) {
  foreach ($array as $key => $val) {
    if ($val == max($array)) return $key;
  }
}

function po4local_replace($match) {
  $data = explode('\\then', $match[2]);
  foreach ($data as $k => $text) {
    $data[$k] = po4local_item_replace(trim($text));
  }
  
  $output = '\\'. $match[1] .'{'. implode('\\then ', $data) .'}';
  if ($output != $match[0]) {
    printf("Replace %s by %s\n", $match[0], $output);
  }

  return $output;
}

function po4local_item_replace($source = NULL) {
  static $total = 0;
  global $T2;
  
  if ($source == NULL) {
    return $total;
  }
  
  if (isset($T2[$source])) {
    if ($source != $T2[$source]) $total++;
    return $T2[$source];
  }
  else {
    return $source;
  }
}
