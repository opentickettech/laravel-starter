<?php

namespace Misc\Openticket;

use App\Models\CompanyAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class OTApi {

    protected $client;
    protected $companyAccessToken;
    protected $base;

    function __construct(CompanyAccessToken $companyAccessToken, $base = null) {
        $this->companyAccessToken = $companyAccessToken;
        $this->base = $base ?? 'https://api.eventix.io/3.0.0/';

        $this->prepareClient();
    }

    protected function prepareClient() {
        $this->client = new Client([
            'base_uri' => $this->base,
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function get(string $uri, array $options = []) {
        return $this->request('GET', $uri, $options);
    }

    public function delete(string $uri, array $options = [], $body = null) {
        return $this->request('DELETE', $uri, $options, $body);
    }

    public function head(string $uri, array $options = []) {
        return $this->request('HEAD', $uri, $options);
    }

    public function options(string $uri, array $options = []) {
        return $this->request('OPTIONS', $uri, $options);
    }

    public function patch(string $uri, array $options = [], $body = null) {
        return $this->request('PATCH', $uri, $options, $body);
    }

    public function post(string $uri, array $options = [], $body = null) {
        return $this->request('POST', $uri, $options, $body);
    }

    public function put(string $uri, array $options = [], $body = null) {
        return $this->request('PUT', $uri, $options, $body);
    }

    public function request(string $method, string $uri, array $options = [], $body = null) {
        $request = new Request($method, $uri, $options, $body);
        $request = $request->withHeader('Authorization', 'Bearer ' . $this->getAccessToken());
        $request = $request->withHeader('Company', [$this->getCompanyId()]);

        if(!is_null($body)) {
            $request = $request->withHeader('Accept', 'application/json');
            $request = $request->withHeader('Content-Type', 'application/json;charset=UTF-8');
        }

        try {
            $response = $this->client->send($request);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() == 401) {
                $this->refreshToken();
                $request = $request = new Request($method, $uri, $options, $body);
                $request = $request->withHeader('Authorization', 'Bearer ' . $this->getAccessToken());

                Log::info('Retrying request for ' . $this->companyAccessToken->guid);
                $response = $this->client->send($request);
            }
            else {
                throw $e;
            }
        }

        return json_decode($response->getBody());
    }

    public function getAccessToken() {
        return $this->companyAccessToken->access_token;
    }

    public function getCompanyId() {
        return $this->companyAccessToken->guid;
    }

    public function refreshToken() {
        Log::info('Refreshing token for cid: ' . $this->companyAccessToken->guid);
        $this->companyAccessToken->refreshToken();
    }
}
