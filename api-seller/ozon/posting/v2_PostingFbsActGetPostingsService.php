<?php

$HOME = strlen($_SERVER['DOCUMENT_ROOT']) != 0 ? $_SERVER['DOCUMENT_ROOT'] : $_SERVER['PHP_CRON_HOME'];

include_once "$HOME/env.php";

class v2_PostingFbsActGetPostingsService {
    public function executeCron() {
        global $HOME;
        $PATH_FILE = "$HOME/data/v2_PostingFbsActListService.json";
        $jsonString = file_get_contents($PATH_FILE);

        $data = json_decode($jsonString, true);

        if ($data === null) {
            throw new Exception('Ошибка декодирования JSON');
        }

        if (!isset($data['cache'])) {
            throw new Exception('В JSON нет ключа cache: data.cache');
        }

        if (!isset($data['cache']['data'])) {
            throw new Exception('В JSON нет ключа data: data.cache.data');
        }

        $array = $data['cache']['data'];

        $result_array = [];
        for ($i = 0; $i < count($array); $i++) {
            $current = $array[$i];
            $current_id = $current['id'];
            $current_more_data = v2_PostingFbsActGetPostingsService::getById($current_id);
            array_push($result_array, [
                'id' => $current_id,
                'data' => $current,
                'more' => $current_more_data,
            ]);
        }

        $data = $result_array;

        $FILE_PATH = "$HOME/data/v2_PostingFbsActGetPostingsService.json";

        $FILE_TEXT = json_encode(
            [
                'cache' => [
                    'createdAt' => date('Y-m-d_H-i-s'),
                    'data' => $data,
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );

        file_put_contents($FILE_PATH, $FILE_TEXT);
    }

    static function getById($id) {
        return v2_PostingFbsActGetPostingsService::fetchJson([
            'id' => $id,
        ]);
    }

    static function fetchJson($data) {
        global $env;

        $URI = "/v2/posting/fbs/act/get-postings";
        $FETCH_URL = "https://api-seller.ozon.ru$URI";

        $json_string = json_encode($data);
        $http_data = $json_string;

        $ozonClientId = $env['ozon-client-id'];
        $ozonApiKey = $env['ozon-api-key'];

        $http_headers = [
            "Content-Type: application/json",
            "Client-Id: $ozonClientId",
            "Api-Key: $ozonApiKey",
        ];

        $http_cookie = implode("; ", [
            "Client-Id=$ozonClientId",
            "Api-Key=$ozonApiKey",
        ]);

        $ch = curl_init($FETCH_URL);                            // Инициализируем cURL сессии
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);                   // Устанавливаем метод POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $http_data);       // Тело запроса
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);    // Устанавливаем заголовки
        curl_setopt($ch, CURLOPT_COOKIE, $http_cookie);         // Передаем куки
        $response = curl_exec($ch);                             // Выполняем запрос и получаем ответ

        if (curl_errno($ch)) {                                  // Проверяем на наличие ошибок
            $err = curl_error($ch);                             // Получаем сообщение об ошибке
            curl_close($ch);                                    // Закрываем cURL сессию
            throw new Error("Fetch error: $err");
        }

        curl_close($ch);                                        // Закрываем cURL сессию
        $json_string = $response;
        $php_object = json_decode($json_string, true);
        return $php_object;
    }
}
