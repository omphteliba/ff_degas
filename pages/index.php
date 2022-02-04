<?php

$addon = rex_addon::get('ff_degas');

// Die layout/top.php und layout/bottom.php werden automatisch eingebunden

// Die Subpages müssen dem Titel nicht mehr übergeben werden
echo rex_view::title($addon->i18n('title') . ' - '. $addon->i18n('subtitle'));
// $addon->i18n('title') ist eine Kurzform für rex_i18n::msg('ff_copy_tool_title')

// Die Subpages werden nicht mehr über den "subpage"-Parameter gesteuert,
// sondern über "page" (getrennt mit einem Slash, z. B. page=ff_copy_tool/config)
// Die einzelnen Teile des page-Pfades können mit der folgenden Funktion ausgelesen werden.
$subpage = rex_be_controller::getCurrentPagePart(2);

// Subpages können über diese Methode eingebunden werden. So ist sichergestellt, dass auch Subpages funktionieren,
// die von anderen AddOns/Plugins hinzugefügt wurden
rex_be_controller::includeCurrentPageSubPath();
