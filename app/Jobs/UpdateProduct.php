<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\ShopifyTrait;
use Exception;
use App\Models\Product;


class UpdateProduct implements ShouldQueue
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

        $productData = $this->data;
        $postedData = $productData['postedData'];
        $productId = $productData['id'];
        unset($productData['postedData']);
        $variables = [
            "input" => $productData,
            "media" => []
        ];
        $query = <<<'QUERY'
            mutation UpdateProductWithNewMedia($input: ProductInput!, $media: [CreateMediaInput!]) {
                productUpdate(input: $input, media: $media) {
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
            if(!empty($result['data']) && !empty($result['data']['productUpdate'])){
                $productData['seo_title'] = $productData['seo']['title'];
                $productData['seo_description'] = $productData['seo']['description'];
                unset($productData['seo']);
                $productData['json_data'] = json_encode($postedData);
                unset($productData['id']);
                Product::where('admin_graphql_api_id',$productId)->update($productData);
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
