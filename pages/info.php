<?php
/**
 * @var rex_addon $this
 */

$file = rex_file::get($this->getPath('README.md'));

$parsedown = new Parsedown();
$content = $parsedown->text($file);

$fragment = new rex_fragment();
$fragment->setVar('content', $content, false);
try {
    $content = $fragment->parse('core/page/docs.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('info'));
$fragment->setVar('body', $content, false);
try {
    echo $fragment->parse('core/page/section.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}
