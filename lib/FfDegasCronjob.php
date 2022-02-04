<?php

class FfDegasCronjob extends rex_cronjob
{
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $id = 0;
        $out = '';

        $degas = new FfDegas();
        $request_url = $degas->request->getUrl();

        $degas->requestSetHeader();
        $degas->requestSetMethod();
        $request_url->setQueryVariables($degas->getParameters());

        try {
            $images = $degas->getImages();
        } catch (rex_sql_exception $e) {
            rex_logger::logException($e);
            $this->setMessage(rex_addon::get('ff_degas')->i18n('error_noImages'));
            return false;
        }
        try {
            $out .= rex_addon::get('ff_degas')->i18n('countImages') . " " . $degas->getImageCount() . PHP_EOL;
        } catch (rex_sql_exception $e) {
            rex_logger::logException($e);
        }

        if ((isset($images)) && ([] !== $images)) {
            foreach ($images as $image) {
                $id++;
                $bild_url = rex::getServer() . 'media/' . $image['filename'];

                // Request body
                try {
                    $degas->request->setBody('{"url":"' . $bild_url . '"}');
                } catch (HTTP_Request2_LogicException $e) {
                    rex_logger::logException($e);
                }

                try {
                    $response = $degas->request->send();
                } catch (HTTP_Request2_Exception $e) {
                    rex_logger::logException($e);
                    return false;
                }

                try {
                    $result = $response->getBody();
                } catch (HTTP_Request2_Exception $e) {
                    rex_logger::logException($e);
                    return false;
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
                            $this->setMessage($azureErrorMessage);
                            return false;
                        case 400:
                        case 401: // not supported
                        case 415: // Unsupported media type error
                        case 'InvalidRequest':
                        default:
                            $out .= 'Azure AI Error ' . $azureErrorCode . ': ' . $azureErrorMessage .
                                ' ( ' . $bild_url . ' )' . PHP_EOL;
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

                    $out .= 'Zeit: ' . date('d.m.Y - H:i:s') . '
Dateiname: ' . $image['filename'] . '
Beschreibung: ' . $bild_beschreibung . '
Tags: ' . $tags . '
Confidence: ' . $confidence . '

';
                }
            }
        }
        $this->setMessage($out);
        return true;
    }

    public function getTypeName(): string
    {
        return rex_i18n::msg('ff_degas_cronjob');
    }
}
