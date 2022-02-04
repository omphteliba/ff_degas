<?php

$file = rex_file::get(rex_path::addon('ff_degas', 'README.md'));
$Parsedown = new Parsedown();
$content =  '<div>'.$Parsedown->text($file) .'</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Info');
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
