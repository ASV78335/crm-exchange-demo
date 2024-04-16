<?php

namespace App\Service;

use Exception;

class TokenService
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

    /**
     * @throws Exception
     */
    public function getToken(): array
    {
        $fileData = file_get_contents(dirname(__FILE__) . '/token.dat');
        $token = json_decode($fileData, true);
        $tokenDate = $token['date'];
        $currentDate = strtotime(date('Y-m-d H:i:s'));

        $expires = (int)$token['expires_in'];

        if (($currentDate - $tokenDate) >= $expires) {

            $newToken = $this->queryRefreshToken($token['refresh_token']);
            $newToken['date'] = $currentDate;
            file_put_contents(dirname(__FILE__) . '/token.dat', print_r(json_encode($newToken), true));
            return $newToken;

        } else {
            return $token;
        }
    }

    private function queryAccessToken(string $token): array
    {
        $data = [
            'client_id' => $_ENV['AMO_CLIENT_ID'],
            'client_secret' =>  $_ENV['AMO_CLIENT_SECRET'],
            'grant_type' => 'authorization_code',
            'code' =>  $token,
            'redirect_uri' => $_ENV['AMO_REDIRECT_URI']
        ];

        return $this->queryToken($data);
    }

    private function queryRefreshToken(string $refreshToken): array
    {
        $data = [
            'client_id' => $_ENV['AMO_CLIENT_ID'],
            'client_secret' =>  $_ENV['AMO_CLIENT_SECRET'],
            'grant_type' => 'refresh_token',
            'refresh_token' =>  $refreshToken,
            'redirect_uri' => $_ENV['AMO_REDIRECT_URI']
        ];

        return $this->queryToken($data);
    }

    private function queryToken(array $data): array
    {
        $subdomain = $_ENV['AMO_SUBDOMAIN'];
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
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

        $response = json_decode($out, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $token_type = $response['token_type'];
        $expires_in = $response['expires_in'];

        return compact('access_token', 'refresh_token', 'token_type', 'expires_in');
    }
}
