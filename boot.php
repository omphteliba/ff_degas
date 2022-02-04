<?php
// ToDo: Meta-Info Feld für den Media-Pool anlegen 'med_degas_description' oder überprüfen

if (rex_addon::get('cronjob')->isAvailable()) {
    rex_cronjob_manager::registerType(FfDegasCronjob::class);
}
