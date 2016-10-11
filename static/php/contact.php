<?php

date_default_timezone_set('America/Chicago');

require_once __DIR__ . '/../../../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
$dotenv->load();

class Validator
{
    protected $data;
    protected $messages = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function fails()
    {
        return ! $this->passes();
    }

    public function messages()
    {
        if (empty($this->messages)) {
            $this->passes();
        }

        return $this->messages;
    }

    public function passes()
    {
        if (! array_key_exists('name', $this->data) || empty($this->data['name'])) {
            $this->messages['name'] = 'Your name is require.';
        }
        if (! array_key_exists('email', $this->data) || empty($this->data['email'])) {
            $this->messages['email'] = 'Your email is required.';
        } else {
            if(! filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->messages['email'] = 'Your email is invalid, check for typos.';
            }
        }
        if (! array_key_exists('comment', $this->data) || empty($this->data['comment'])) {
            $this->messages['comment'] = 'A message is required.';
        }

        return count($this->messages) === 0;
    }
}

function json_response($message = null, $code = 200) {
    // clear the old headers
    header_remove();
    // set the actual code
    http_response_code($code);
    // set the header to make sure cache is forced
    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
    // treat this as json
    header('Content-Type: application/json');

    exit(json_encode($message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    if ($validator->passes()) {
        $headers = [
            'From: "' . getenv('CONTACT_FROM_NAME') . '" <' . getenv('CONTACT_FROM_EMAIL') . '>',
            "Reply-To: {$_POST['email']}"
        ];

        $message = $_POST['comment'] . PHP_EOL . PHP_EOL;
        $message .= $_POST['name'] . PHP_EOL;
        $message .= $_POST['email'];

        $sent = mail(
            getenv('CONTACT_TO_EMAIL'),
            'Contact Form Message',
            $message,
            implode(PHP_EOL, $headers)
        );

        if ($sent) {
            json_response(['status' => 'Your message was sent. Thanks!']);
        } else {
            json_response(['status' => 'That didn\'t work. Something blew up.'], 500);
        }
    } else {
        json_response($validator->messages(), 422);
    }
} else {
    http_response_code(405);
}
