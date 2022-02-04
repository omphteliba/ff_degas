<?php
/**
 * Created by PhpStorm.
 * User: oliver.hoerold
 * Date: 11.10.2021
 * Time: 12:07.
 */

$content = '';

if (rex_post('config-submit', 'boolean')) {
    $this->setConfig(rex_post('config', [
        ['api_key', 'string'],
        ['api_region', 'string'],
        ['api_language', 'string'],
        ['free_tier', 'boolean'],
    ]));

    $content .= rex_view::info($this->i18n('config_saved'));
}

$content .= '<div class="rex-form">';
$content .= '  <form action="' . rex_url::currentBackendPage() . '" method="post">';
$content .= '    <fieldset>';

$formElements = [];

$n = [];
$n['label'] = '<label for="degas-api_key">' . $this->i18n('api_key') . '</label>';
$n['field'] = '<input class="form-control" type="password" id="degas-api_key" name="config[api_key]" value="' . $this->getConfig('api_key') . '" >';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="degas-api_region">' . $this->i18n('api_region') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="degas-api_region" name="config[api_region]" value="' . $this->getConfig('api_region') . '" >';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="degas-language">' . $this->i18n('api_language') . '</label>';
$lang_field = '<select class="form-control" id="degas-language" name="config[api_language]" value="' . $this->getConfig('api_language') . '" >';
$lang_field .= '<option value="en">English</option>';
$lang_field .= '<option value="es">Spanisch</option>';
$lang_field .= '<option value="ja">Japanisch</option>';
$lang_field .= '<option value="pt">Portugiesisch</option>';
$lang_field .= '<option value="zh">Vereinfachtes Chinesisch</option>';
$lang_field .= '</select>';
$n['field'] = $lang_field;
 $formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
try {
    $content .= $fragment->parse('core/form/form.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}

// Checkbox
// Checkbox
$formElements = [];
$n = [];
$n['label'] = '<label for="degas_free_tier">' . $this->i18n('free_tier') . '</label>';
$n['field'] = '<input type="checkbox" id="degas_free_tier" name="config[free_tier]"' .
    (!empty($this->getConfig('free_tier')) && $this->getConfig('free_tier') == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$content .= '    </fieldset>';

$content .= '    <fieldset class="rex-form-action">';

$formElements = [];

$n = [];
$n['field'] = '<input type="submit" name="config-submit" value="' . $this->i18n('config_save') .
    '" ' . rex::getAccesskey($this->i18n('save_button'), 'save') . '>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
try {
    $content .= $fragment->parse('core/form/submit.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}

$content .= '    </fieldset>';
$content .= '  </form>';
$content .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $this->i18n('config'));
$fragment->setVar('body', $content, false);
try {
    echo $fragment->parse('core/page/section.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}
