<?php

namespace App\Traits;
use Shopify\Clients\Graphql;
use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Illuminate\Http\Request;


trait ShopifyTrait {

    public function initializeClient(){
        Context::initialize(
            $_ENV['SHOPIFY_API_KEY'],
            $_ENV['SHOPIFY_API_SECRET'],
            $_ENV['SHOPIFY_APP_SCOPES'],
            $_ENV['SHOPIFY_APP_HOST_NAME'],
            new FileSessionStorage('/tmp/php_sessions'),
            '2024-04',
            true,
            false,
        );
        $client = new Graphql('petzzing.myshopify.com', $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN']);
        return $client;

    }
}
