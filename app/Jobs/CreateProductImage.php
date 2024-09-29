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
use App\Models\ProductImage;


class CreateProductImage implements ShouldQueue
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
            "media" => $this->data['images']
        ];
        $query = <<<'QUERY'
            mutation productCreateMedia($media: [CreateMediaInput!]!, $productId: ID!) {
                productCreateMedia(media: $media, productId: $productId) {
                media {
                    id
                    status
                }
                mediaUserErrors {
                    field
                    message
                }
                product {
                    id
                    title
                }
                }
            }
        QUERY;
        
        try {
            $response = $client->query(["query" => $query, "variables" => $variables]);
            $result = json_decode($response->getBody()->getContents(),true);
            
            if(!empty($result['data']) && !empty($result['data']['productCreateMedia'])){
                $insertData=[];
                foreach($this->data['images'] as $index => $entry){
                    $insertData[$index] = $entry;
                    $insertData[$index]['product_id'] = $this->data['product_id'];
                    $insertData[$index]['admin_graphql_api_id'] = $result['data']['productCreateMedia']['media'][$index]['id'];
                    $insertData[$index]['status'] = $result['data']['productCreateMedia']['media'][$index]['status'];
                }
                if(!empty($insertData)){
                    ProductImage::insert($insertData);
                }
            }
            
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
