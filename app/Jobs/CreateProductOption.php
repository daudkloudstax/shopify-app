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
use App\Models\ProductVariant;


class CreateProductOption implements ShouldQueue
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
        $client = $this->initializeClient();
        $variables = [
            "productId" => $this->data['shopify_product_id'],
            "options" => $this->data['options']
        ];
        $query = <<<'QUERY'
            mutation createOptions($productId: ID!, $options: [OptionCreateInput!]!) {
                productOptionsCreate(productId: $productId, options: $options) {
                    userErrors {
                        field
                        message
                        code
                    }
                    product {
                        id    
                    }
                }
            }
        QUERY;
        
        try {
            $response = $client->query(["query" => $query, "variables" => $variables]);      
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
