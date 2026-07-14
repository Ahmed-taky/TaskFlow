<?php
namespace App\Helpers;
class Request
{
    public $method = "";
    public $uri = "";
    public $headers = [];
    public $params = [];
    public $cookies = [];
    public $files = [];
    public $query = [];
    public $body = [];
    private array $attributes = [];

    public function __construct()
    {
        $this->extractRequestData();

    }
    private function extractRequestData()
    {

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );
        $this->headers = getallheaders();
        $body = json_decode(file_get_contents('php://input'), true);
        if (!empty($body) && !is_array($body)) {
            throw new \Exception('Invalid body shape');
        }
        $this->body = $body;
        $this->query = $_GET;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;

    }

    public function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

}
?>