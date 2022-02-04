<?php
$file = rex_file::get(rex_path::addon('ff_degas','LICENSE.md'));
$Parsedown = new Parsedown();

$content =  '<div>'.$Parsedown->text($file).'</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Lizenz');
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
