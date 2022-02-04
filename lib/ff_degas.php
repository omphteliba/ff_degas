<?php

//namespace ff_degas;

/**
 *
 */
class ff_degas
{

    public string $api_lang = '';
    public string $api_url = '';

    public function __construct()
    {
        $this->setApiLang();
        $this->setApiUrl();
    }

    /**
     * @return string
     */
    public function getApiLang(): string
    {
        return $this->api_lang;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->api_url;
    }

    /**
     * @return bool
     */
    public function setApiLang(): bool
    {
        if ((null !== rex_addon::get('ff_degas')->getConfig('api_language')) &&
            ('' !== rex_addon::get('ff_degas')->getConfig('api_language'))) {
            $this->api_lang = rex_addon::get('ff_degas')->getConfig('api_language');
        } else {
            $this->api_lang = 'en';
        }
        return true;
    }

    /**
     * @return bool
     */
    public function setApiUrl(): bool
    {
        // Todo: Warum geht https://redaxo-computer-vision.cognitiveservices.azure.com/ nicht?
        $this->api_url = 'https://' . rex_addon::get('ff_degas')->getConfig('api_region') .
            '.api.cognitive.microsoft.com/vision/v3.2/describe';

        return true;
    }

    /**
     * @throws \rex_sql_exception
     */
    public static function getImages(int $count = 0): array
    {
        $images = \rex_sql::factory();
        $images->setDebug(false);
        $query = self::getQuery($count);

        $images->setQuery($query);

        return $images->getArray();
    }

    /**
     * @throws \rex_sql_exception
     */
    public static function getImageCount(): int
    {
        $images = \rex_sql::factory();
        $images->setDebug(false);
        $query = self::getQuery(0);

        $images->setQuery($query);

        return count($images->getArray());
    }

    /**
     * @throws \rex_sql_exception
     */
    public static function updateDescription(string $filename, string $description): \rex_sql
    {
        $image = \rex_sql::factory();
        $image->setDebug(false);
        $query = 'UPDATE ' . \rex::getTable('media') . ' 
        SET med_description = "' . $description . '" 
        WHERE filename = "' . $filename . '"';
        $image->setQuery($query);

        return $image;
    }

    /**
     * @param int $count
     * @return string
     */
    public static function getQuery(int $count): string
    {
        // Todo: Azure AI Error InvalidRequest: Input image is too large.
        // Todo: Azure AI Error InvalidRequest: Image must be at least 50 pixels in width and height
        $query = 'SELECT `filename` 
FROM ' . \rex::getTable('media') . ' 
WHERE ( `filetype` = "image/png" 
OR `filetype` = "image/jpeg"
OR `filetype` = "image/gif"
OR `filetype` = "image/bmp" )
AND (`width` >= 50 AND `height` >= 50)
AND (`width` < 10000 AND `height` < 10000)
AND `filesize` < 4194304
AND (`med_description` = "" OR `med_description` IS NULL)';

        if ($count > 0) {
            $query .= ' LIMIT ' . $count;
        }
        return $query;
    }

    /**
     * @param $ding
     * @param string $out
     * @return string
     */
    public static function beResultTable($ding, string $out): string
    {
        $content = '<table class="table table-hover">
    <thead>
    <tr>
        <th>' . $ding->i18n('id') . '</th>
        <th>' . $ding->i18n('zeit') . '</th>
        <th>' . $ding->i18n('bild') . '</th>
        <th>' . $ding->i18n('dateiname') . '</th>
        <th>' . $ding->i18n('beschreibung') . '</th>
    </tr>
    </thead>
    <tbody>';

        $content .= $out;

        $content .= '    </tbody>
</table>';

        $fragment = new rex_fragment();
        $fragment->setVar('title', $ding->i18n('images'));
        $fragment->setVar('body', $content, false);
        try {
            echo $fragment->parse('core/page/section.php');
        } catch (rex_exception $e) {
            rex_logger::logException($e);
        }
        return $content;
    }
}
