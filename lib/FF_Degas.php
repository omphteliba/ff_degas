<?php

namespace ff_degas;

class ff_degas
{
    public static function getImages()
    {
        $images = \rex_sql::factory();
        $images->setDebug(true);
        $images->setQuery('SELECT * FROM ' . \rex::getTable('media') . ' WHERE description = ""');

    }
}