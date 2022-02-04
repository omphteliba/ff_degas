<?php

$content = '';

if (rex_get('func', 'string') === 'getAll') {
    $result = '';
    $id = 0;
    $out = '';

    $degas = new FfDegas();

    $lang = $degas->getApiLang();
    $api_url = $degas->getApiUrl();
    $request = $degas->getRequest();
    $request_url = $request->getUrl();

    $degas->requestSetHeader();
    $degas->requestSetMethod();
    $request_url->setQueryVariables($degas->getParameters());

    try {
        $images = $degas->getImages();
    } catch (rex_sql_exception $e) {
        rex_logger::logException($e);
        echo '<div class="alert alert-danger">' . rex_addon::get('ff_degas')->i18n('error_noImages') . '</div>';
    }
    try {
        $content = rex_addon::get('ff_degas')->i18n('countImages') . " " . $degas->getImageCount();
    } catch (rex_sql_exception $e) {
        rex_logger::logException($e);
    }

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_addon::get('ff_degas')->i18n('infos'), false);
    $fragment->setVar('body', $content, false);
    try {
        echo $fragment->parse('core/page/section.php');
    } catch (rex_exception $e) {
        rex_logger::logException($e);
    }

    if ((isset($images)) && ([] !== $images)) {
        foreach ($images as $image) {
            $id++;
            $bild_url = rex::getServer() . 'media/' . $image['filename'];

            // Request body
            try {
                $request->setBody('{"url":"' . $bild_url . '"}');
            } catch (HTTP_Request2_LogicException $e) {
                rex_logger::logException($e);
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
                if (!empty($result_array->error->innererror)) {
                    $azureInnerErrorCode = $result_array->error->innererror->code;
                    $azureInnerErrorMessage = $result_array->error->innererror->message;
                    $azureErrorMessage .= ', InnerError: ' . $azureInnerErrorCode . '/ ' . $azureInnerErrorMessage . ' ';
                }
                rex_logger::logError(
                    1,
                    "Azure AI Error " . $azureErrorCode . ": " . $azureErrorMessage . ' ( ' . $bild_url . ' )',
                    __FILE__,
                    __LINE__
                );
                switch ($azureErrorCode) {
                    case 429: //rate limit exceeded
                    case 500: // internatl service error
                    case 510: // Service unavailable
                    case 'InternalServerError':
                    case 'ServiceUnavailable':
                        echo '<div class="alert alert-danger">' . $azureErrorMessage . '</div>';
                        break 2;
                    case 400:
                    case 401: // not supported
                    case 415: // Unsupported media type error
                    case 'InvalidRequest':
                    default:
                        echo '<div class="alert alert-warning">' .
                            'Azure AI Error ' . $azureErrorCode . ': ' . $azureErrorMessage .
                            ' ( <a href="' . $bild_url . '">' . $bild_url . '</a> )' . '</div>';
                }
            } else {
                $bild_beschreibung = $result_array->description->captions[0]->text;
                $tags = $result_array->description->tags;
                $tags = implode(', ', $tags);
                $confidence = $result_array->description->captions[0]->confidence;

                if (('' !== $bild_beschreibung) && (null !== $bild_beschreibung)) {
                    try {
                        $degas->updateDescription($image['filename'], $bild_beschreibung);
                    } catch (rex_sql_exception $e) {
                        rex_logger::logException($e);
                    }
                }

                $out .= '    <tr class="">
        <td data-title="Id">' . $id . '</td>
        <td data-title="Zeit">' . date('d.m.Y - H:i:s') . '</td>
        <td data-title="Bild"><img alt="' . $bild_beschreibung . '" src="' .
                    rex_media_manager::getUrl('rex_mediapool_preview', $image['filename']) .
                    '"</td>
        <td data-title="Dateiname">' . $image['filename'] . '</td>
        <td data-title="Beschreibung"><div class="rex-word-break">' . $bild_beschreibung . '</div></td>
        <td data-title="Tags">' . $tags . '</td>
        <td data-title="Confidence">' . $confidence . '</td>
    </tr>';
            }
        }
    }

    if ('' !== $out) {
        $content = FfDegas::beResultTable($out);
    }
}

$buttons = [];

$n = [];
$n['url'] = rex_url::backend() . 'index.php?page=ff_degas/image&func=getAll';
$n['label'] = $this->i18n('getAll');
$n['attributes']['class'] = array('btn-primary');

$buttons[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/buttons/button_group.php');

$fragment = new rex_fragment();
$fragment->setVar('title', 'Tools', false);
$fragment->setVar('body', $content, false);
try {
    echo $fragment->parse('core/page/section.php');
} catch (rex_exception $e) {
    rex_logger::logException($e);
}
