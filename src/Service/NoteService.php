<?php

namespace App\Service;

use Exception;

class NoteService
{
    private array $errors = [
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];

    public function setNote(string $token, string $entity, string $id, string $noteText): void
    {
        $url = $entity . '/' . $id . '/notes';
        $value = ["text" => $noteText];
        $field = [
            "entity_id" => (int)$id,
            "note_type" => "common",
            "params" => $value
        ];
        $body = [$field];

        $result = $this->send($token, $url, $body);
        file_put_contents('result.txt', print_r($result, true));
    }

    private function send(string $token, string $url, array $data): array
    {
        $subdomain = $_ENV['AMO_SUBDOMAIN'];
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/' . $url;

        $headers = [
            'Authorization: Bearer ' . $token
        ];

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, false);

        $out = curl_exec($curl);

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int)$code;

        try {
            if ($code < 200 || $code > 204) {
                throw new Exception($this->errors[$code] ?? 'Undefined error', $code);
            }
        }
        catch(Exception $e) {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        return json_decode($out, true);
    }
}
