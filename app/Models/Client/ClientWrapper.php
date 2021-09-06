<?php
/**
 * Copyright (c) 2020. Dynacommerce B.V.
 */

namespace App\Models\Client;

use Bitbucket\Client;
use Bitbucket\HttpClient\Builder;
use Dotenv\Dotenv;

class ClientWrapper extends Client
{
    public function __construct(Builder $httpClientBuilder = null)
    {
        parent::__construct($httpClientBuilder);
        $this->authenticate(
            Client::AUTH_HTTP_PASSWORD,
            ENV('BB_USERNAME'),
            ENV('BB_PASSWORD')
        );
    }

}
