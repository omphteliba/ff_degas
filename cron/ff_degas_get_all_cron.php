<?php
$addon = rex_addon::get('ff_degas');
require_once 'HTTP/Request2.php';

$result = '';
$id = 0;
$error = '';

$lang = 'en';
if ((null !== $addon->getConfig('api_language')) && ('' !== $addon->getConfig('api_language'))) {
    $lang = $addon->getConfig('api_language');
}

// Todo: Warum geht https://redaxo-computer-vision.cognitiveservices.azure.com/ nicht?
$api_url = 'https://' . $addon->getConfig('api_region') . '.api.cognitive.microsoft.com/vision/v3.2/describe';

$request = new Http_Request2($api_url);
$url = $request->getUrl();

$headers = array(
    // Request headers
    'Content-Type' => 'application/json',
    'Ocp-Apim-Subscription-Key' => $addon->getConfig('api_key'),
);

try {
    $request->setHeader($headers);
} catch (HTTP_Request2_LogicException $e) {
    rex_logger::logException($e);
    die();
}

$parameters = array(
    // Request parameters
    'maxCandidates' => '1',
    'language' => $lang,
    'model-version' => 'latest',
);

$url->setQueryVariables($parameters);

try {
    $images = ff_degas::getImages();
} catch (rex_sql_exception $e) {
    rex_logger::logException($e);
    $error .=  $addon->i18n('error_noImages');
}
$content = $addon->i18n('countImages') . " " . ff_Degas::getImageCount();

if ((isset($images)) && ([] !== $images)) {
    foreach ($images as $image) {
        $id++;
        $bild_url = rex::getServer() . 'media/' . $image['filename'];

        try {
            $request->setMethod(HTTP_Request2::METHOD_POST);
        } catch (HTTP_Request2_LogicException $e) {
            rex_logger::logException($e);
            die();
        }

// Request body
        try {
            $request->setBody('{"url":"' . $bild_url . '"}');
        } catch (HTTP_Request2_LogicException $e) {
            rex_logger::logException($e);
            die();
        }

        try {
            $response = $request->send();
        } catch (HTTP_Request2_Exception $e) {
            rex_logger::logException($e);
            die();
        }
        try {
            $result = $response->getBody();
        } catch (HTTP_Request2_Exception $e) {
            rex_logger::logException($e);
            die();
        }

        $result_array = json_decode($result);

        if (@$result_array->error) {
            $azureErrorCode = $result_array->error->code;
            $azureErrorMessage = $result_array->error->message;
            rex_logger::logError(
                1,
                "Azure AI Error " . $azureErrorCode . ": " . $azureErrorMessage,
                __FILE__,
                __LINE__
            );
            switch ($azureErrorCode) {
                case 429: //rate limit exceeded
                    $error .= $azureErrorMessage;
                    break 2;
                case 401: // not supported
                    $error .= $azureErrorMessage;
                    break 2;
            }
        } else {
            $bild_beschreibung = $result_array->description->captions[0]->text;

            if (('' !== $bild_beschreibung) && (null !== $bild_beschreibung)) {
                try {
                    ff_degas::updateDescription($image['filename'], $bild_beschreibung);
                } catch (rex_sql_exception $e) {
                    rex_logger::logException($e);
                }
            }
        }
    }
}
echo $content . PHP_EOL . 'Error: ' . $error;
