<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\ShopifyTrait;
use Exception;


class EmptyProductVariant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShopifyTrait;
    private $data;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $id = $this->data['shopify_product_id'];
        $client = $this->initializeClient();
        $query = <<<QUERY
                    query {
                        product(id: "$id") {
                        title
                        variants(first: 1) {
                                edges {
                                    node {
                                        id
                                    }
                                }
                            }
                        }
                    }
                QUERY;
        try {
            $response = $client->query(["query" => $query]);
            $result = json_decode($response->getBody()->getContents(),true);

            if(!empty($result['data']) && !empty($result['data']['product'])){
                $variables = [
                    "productId" => $id,
                    "variantsIds"=>[$result['data']['product']['variants']['edges'][0]['node']['id']]
                ];
                $query = <<<'QUERY'
                    mutation productVariantsBulkDelete($productId: ID!, $variantsIds: [ID!]!) {
                        productVariantsBulkDelete(productId: $productId, variantsIds: $variantsIds) {
                            product {
                                id
                            }
                            userErrors {
                            field
                            message
                            }
                        }
                        }
                QUERY;

                $response = $client->query(["query" => $query, "variables" => $variables]);
                dd($response->getBody()->getContents());
                $result = json_decode($response->getBody()->getContents(),true);
                
            
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
