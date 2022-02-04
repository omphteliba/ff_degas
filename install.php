<?php

if (!rex_sql_table::get(rex::getTablePrefix().'media')->hasColumn('med_degas_description')) {
    rex_sql_table::get(rex::getTablePrefix().'media')
        ->ensureColumn(new rex_sql_column('med_degas_description', 'varchar(255)', true, 0))
        ->alter();
}