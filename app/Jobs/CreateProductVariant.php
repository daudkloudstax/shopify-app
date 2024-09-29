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


class CreateProductVariant implements ShouldQueue
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
            "variants" => $this->data['variants']
        ];
        $query = <<<'QUERY'
            mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
                productVariantsBulkCreate(productId: $productId, variants: $variants) {
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
            if(!empty($result['data']) && !empty($result['data']['productVariantsBulkCreate'])){
                $insertData=[];
                foreach($this->data['variants'] as $index => $entry){
                    $options = [];
                    $i=0;
                    foreach($entry['optionValues'] as $key => $option){
                        if($option['name'] != "Default"){
                            $options['option_'.($i+1)] = $option['name'];
                            $i++;
                        }
                    }
                    $insertData[$index] = $entry;
                    $insertData[$index]['json_data'] = json_encode($entry);
                    $insertData[$index]['product_id'] = $this->data['product_id'];
                    $insertData[$index]['admin_graphql_api_id'] = $result['data']['productVariantsBulkCreate']['productVariants'][$index]['id'];
                    if($options){
                        $insertData[$index] = array_merge($insertData[$index],$options);
                    }
                }
                if(!empty($insertData)){
                    ProductVariant::insert($insertData);
                }
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
