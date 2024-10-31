<?php

function handle_order($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity)
{
    $data = [
        "event_id" => $event_id,
        "event_date" => $event_date,
        "ticket_adult_price" => $ticket_adult_price,
        "ticket_adult_quantity" => $ticket_adult_quantity,
        "ticket_kid_price" => $ticket_kid_price,
        "ticket_kid_quantity" => $ticket_kid_quantity,
    ];

    $barcode = null;

    do {
        $barcode = generate_barcode();
        $data["barcode"] = $barcode;
        $response = book_tickets($data);
        $decoded_response = json_decode($response, true);
    } while (
        empty($decoded_response['message'])
    );

    $approve = approve_tickets($barcode);

    if (isset($approve['error'])) {
        // обработка ошибок
    }

    try {
        add_order($data);
    } catch (Exception $e) {
        //обработка ошибок
    }
}

function book_tickets(array $data): string
{
    $api_url = "https://api.site.com/book";
    // бизнес-логика обработки данных перед отправкой в апи
    $response = mock_send_request($url, $data);
}

function approve_tickets(int $data): string
{
    $api_url = "https://api.site.com/approve";
    // бизнес-логика обработки данных перед отправкой в апи
    $response = mock_send_request($url, $data);
}

/*TODO
* Уточнить у заказчика максимальную длинну баркода.
* Пока из ориентиров только тип столбца в БД под баркод - varchar(120). Это явно с запасом...
* Когда будет инфа - соответственно переписать генерацию баркода.
*/
function generate_barcode()
{
    return mt_rand();
}

// TODO Подключение к БД вынести в отдельную функцию.
function add_order($data)
{
    //код сохранения в БД через ORM или PDO
    // что-то типа:
    $dsn = 'mysql:host=host;dbname=dbname';
    $pdo = new PDO($dsn, 'name', 'password');
    $statement = $pdo->prepare("INSERT INTO `approved_orders` (
        `event_id`,
        `event_date`,
        `ticket_adult_price`,
        `ticket_adult_quantity`,
        `ticket_kid_price`,
        `ticket_kid_quantity`,
        `barcode`
        )
        VALUES (
            :event_id,
            :event_date,
            :ticket_adult_price,
            :ticket_adult_quantity,
            :ticket_kid_price,
            :ticket_kid_quantity,
            :barcode
        )"
    );

    $result = $statement->execute([
        'event_id' => $data['event_id'],
        'event_date' => $data['event_date'],
        'ticket_adult_price' => $data['ticket_adult_price'],
        'ticket_adult_quantity' => $data['ticket_adult_quantity'],
        'ticket_kid_price' => $data['ticket_kid_price'],
        'ticket_kid_quantity' => $data['ticket_kid_quantity'],
        'barcode' => $data['barcode'],
    ]);

    return $result;
}

function mock_send_request(string $url, ?array $payload = null)
{
    $book_requests = [
        ['message' => 'order successfully booked'],
        ['error' => 'barcode already exists'],
    ];

    $approve_requests = [
        ['message' => 'order successfully aproved'],
        ['error' => 'event cancelled'],
        ['error' => 'no tickets'],
        ['error' => 'no seats'],
        ['error' => 'fan removed'],
    ];

    $path = parse_url($url, PHP_URL_PATH);

    switch ($path) {
        case '/book':
            return json_encode($book_requests[mt_rand(0, count($book_requests) - 1)]);
        case '/approve':
            return json_encode($approve_requests[mt_rand(0, count($approve_requests) - 1)]);
    }
}
