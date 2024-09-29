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


class UpdateProductVariant implements ShouldQueue
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
        $variants = [];
        foreach($this->data['variants'] as $variant){
            $variant['options'] = [$variant['title']];
            unset($variant['title']);
            $variants[] = $variant;
        }
        
        $variables = [
            "productId" => $this->data['productId'],
            "variants" => $variants
        ];
        
        $query = <<<'QUERY'
            mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
                productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                product {
                    id
                }
                productVariants {
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
                foreach($this->data['variants'] as $entry){
                    $variantId = $entry['id'];
                    unset($entry['id']);
                    $entry['json_data'] = json_encode($entry);
                    ProductVariant::where('admin_graphql_api_id', $variantId)->update($entry);                    
                }
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
