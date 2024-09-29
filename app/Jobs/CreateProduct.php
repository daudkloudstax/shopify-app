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
use App\Models\Product;
use App\Jobs\CreateProductOption;
use App\Jobs\CreateProductVariant;
use App\Jobs\CreateProductImage;
use App\Jobs\EmptyProductVariant;


class CreateProduct implements ShouldQueue
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
        
        $productVariantData = $this->data['variants'];
        $productImageData = $this->data['images'];
        $productOptionData = $this->data['options'];
        unset($this->data['variants']);
        unset($this->data['images']);
        unset($this->data['options']);
        $productData = $this->data;
        $variables = [
            "input" => $productData
        ];
        $query = <<<'QUERY'
            mutation createProductMetafields($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                    }
                    userErrors {
                        message
                        field
                    }
                }
            }
        QUERY;
        
        try {
            $response = $client->query(["query" => $query, "variables" => $variables]);
            $result = json_decode($response->getBody()->getContents(),true);
            
            if(!empty($result['data']) && !empty($result['data']['productCreate'])){
                $productData['seo_title'] = $productData['seo']['title'];
                $productData['seo_description'] = $productData['seo']['description'];
                unset($productData['seo']);
                $productData['json_data'] = json_encode($productData);
                $productData['admin_graphql_api_id'] = $result['data']['productCreate']['product']['id'];
                $product = Product::create($productData);
                CreateProductOption::dispatch(['options'=>$productOptionData, 'shopify_product_id'=>$productData['admin_graphql_api_id']]);
                CreateProductVariant::dispatch(['variants'=>$productVariantData, 'shopify_product_id'=>$productData['admin_graphql_api_id'], 'product_id'=>$product->id]);
                EmptyProductVariant::dispatch(['shopify_product_id'=>$productData['admin_graphql_api_id']]);
                CreateProductImage::dispatch(['images'=>$productImageData, 'shopify_product_id'=>$productData['admin_graphql_api_id'], 'product_id'=>$product->id]);

            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
