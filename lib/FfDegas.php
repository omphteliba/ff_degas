<?php

//namespace ff_degas;

/**
 *
 */
class FfDegas
{

    public string $api_lang = '';
    public string $api_url = '';
    public int $max_count = 0;
    public array $headers = [];
    public array $parameters = [];
    public HTTP_Request2 $request;
    public string $requestMethod = 'POST';

    public function __construct()
    {
        require_once 'HTTP/Request2.php';
        $this->setApiLang();
        $this->setApiUrl();
        $this->setMaxCount();
        $this->setHeaders();
        $this->setParameters();
        $this->setRequest();
    }

    /**
     * @return HTTP_Request2
     */
    public function getRequest(): HTTP_Request2
    {
        return $this->request;
    }

    /**
     */
    public function setRequest(): void
    {
        $this->request = new Http_Request2($this->api_url);
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     */
    public function setParameters(): void
    {
        $this->parameters = array(
            // Request parameters
            'maxCandidates' => '1',
            'language' => $this->api_lang,
            'model-version' => 'latest',
        );
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set request headers
     */
    public function setHeaders(): void
    {
        $this->headers = array(
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => rex_addon::get('ff_degas')->getConfig('api_key'),
        );
    }

    /**
     * @return int
     */
    public function getMaxCount(): int
    {
        return $this->max_count;
    }

    /**
     */
    public function setMaxCount(): void
    {
        if (rex_addon::get('ff_degas')->getConfig('free_tier')) {
            $max_count = 20;
        } else {
            $max_count = 0;
        }
        $this->max_count = $max_count;
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

    public function requestSetHeader(): bool
    {
        try {
            $this->request->setHeader($this->headers);
        } catch (HTTP_Request2_LogicException $e) {
            rex_logger::logException($e);

            return false;
        }

        return true;
    }

    public function requestSetMethod(): bool
    {
        try {
            $this->request->setMethod($this->requestMethod);
        } catch (HTTP_Request2_LogicException $e) {
            rex_logger::logException($e);

            return false;
        }

        return true;
    }

    /**
     * @throws rex_sql_exception
     */
    public function getImages(): array
    {
        $images = rex_sql::factory();
        $images->setDebug(false);
        $query = $this->getQuery($this->getMaxCount());

        $images->setQuery($query);

        return $images->getArray();
    }

    /**
     * @throws rex_sql_exception
     */
    public function getImageCount(): int
    {
        $images = rex_sql::factory();
        $images->setDebug(false);
        $query = $this->getQuery();

        $images->setQuery($query);

        return count($images->getArray());
    }

    /**
     * @throws rex_sql_exception
     */
    public function updateDescription(string $filename, string $description): rex_sql
    {
        $image = rex_sql::factory();
        $image->setDebug(false);
        $query = 'UPDATE ' . rex::getTable('media') . ' 
        SET med_description = "' . $description . '",
        med_degas_description ="|true|" 
        WHERE filename = "' . $filename . '"';
        $image->setQuery($query);

        return $image;
    }

    /**
     * @param int $count
     * @return string
     */
    public function getQuery(int $count = 0): string
    {
        // Todo: Azure AI Error InvalidRequest: Input image is too large.
        // Todo: Azure AI Error InvalidRequest: Image must be at least 50 pixels in width and height
        $query = 'SELECT `filename` 
FROM ' . rex::getTable('media') . ' 
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
     * @param string $out
     * @return string
     */
    public static function beResultTable(string $out): string
    {
        $content = '<table class="table table-hover">
    <thead>
    <tr>
        <th>' . rex_addon::get('ff_degas')->i18n('id') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('zeit') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('bild') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('dateiname') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('beschreibung') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('tags') . '</th>
        <th>' . rex_addon::get('ff_degas')->i18n('confidence') . '</th>
    </tr>
    </thead>
    <tbody>';

        $content .= $out;

        $content .= '    </tbody>
</table>';

        $fragment = new rex_fragment();
        $fragment->setVar('title', rex_addon::get('ff_degas')->i18n('images'));
        $fragment->setVar('body', $content, false);
        try {
            echo $fragment->parse('core/page/section.php');
        } catch (rex_exception $e) {
            rex_logger::logException($e);
        }
        return $content;
    }
}
