<?php


namespace App\Bunny;


use Psr\Http\Message\ResponseInterface;

class Result
{
    private ResponseInterface $response;

    private $data;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->data = json_decode($this->response->getBody()->getContents(), false);
    }

    public function getData()
    {
        return $this->data;
    }

    public function success(): bool
    {
        return in_array(floor($this->response->getStatusCode() / 100), [2]);
    }
}
