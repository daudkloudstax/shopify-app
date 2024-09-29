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


class DeleteProductVariant implements ShouldQueue
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
        $variables = $this->data;
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
        
        try {
            $response = $client->query(["query" => $query, "variables" => $variables]);
            $result = json_decode($response->getBody()->getContents(),true);

            if(!empty($result['data']) && !empty($result['data']['productVariantsBulkUpdate'])){
                foreach($this->data['variantIds'] as $entry){
                    ProductVariant::where('admin_graphql_api_id', $entry)->delete();                    
                }
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
