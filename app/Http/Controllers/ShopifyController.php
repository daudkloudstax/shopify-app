<?php
 
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Jobs\CreateProduct;
use App\Jobs\CreateProductVariant;
use App\Jobs\DeleteProductVariant;
use App\Jobs\UpdateProduct;
use App\Jobs\UpdateProductVariant;
use App\Models\ProductImage;
use App\Models\ProductVariant;

class ShopifyController extends Controller
{
    /**
     * Show the profile for a given user.
     *
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $data = $request->all();
        //check if the product exist in db or not
        $isExist = Product::where('handle', $data['handle'])->whereNull('deleted_at')->first();
        if(!$isExist){
            CreateProduct::dispatch($data);
        }else{
            $variants = $data['variants'];
            $images = $data['images'];
            unset($data['variants']);
            unset($data['images']);
            $porductData = json_decode($isExist->json_data,true);
            $productDiff = array_diff_assoc($data, $porductData);
            $newImages = [];
            foreach($images as $image){
                $imageExist = ProductImage::where('product_id', $isExist->id)->where('originalSource', $image['originalSource'])->where('mediaContentType', $image['mediaContentType'])->first();
                if(!$imageExist){
                    $newImages[]=$image;
                }
            }
            if(!empty($productDiff)){
                $productDiff['id'] = $isExist->admin_graphql_api_id;
                $productDiff['postedData'] = $data;
                $productDiff['media'] = $newImages;
                UpdateProduct::dispatch($productDiff);
            }
            $newVariants = [];
            $updateVariants = [];
            $skus = [];
            
            foreach($variants as $variant){
                $skus[]=$variant['sku'];
                $variantExist = ProductVariant::where('sku', $variant['sku'])->where('product_id',$isExist->id)->first();
                if($variantExist){
                    $variantDiff = array_diff_assoc($variant, json_decode($variantExist->json_data,true));
                    if(!empty($variantDiff)){
                        $variant['id'] = $variantExist->admin_graphql_api_id;
                        $updateVariants[] = $variant;
                    }
                }else{
                    $newVariants[]=$variant;
                }
            }

            if(!empty($updateVariants)){
                UpdateProductVariant::dispatch(['productId'=>$isExist->admin_graphql_api_id, 'variants'=>$updateVariants]);
            }

            if(!empty($newVariants)){
                CreateProductVariant::dispatch(['shopify_product_id'=>$isExist->admin_graphql_api_id, 'product_id'=>$isExist->id, 'variants'=>$newVariants]);
            }

            //check for deleted variants
            $deletedVariants = ProductVariant::select(['admin_graphql_api_id', 'sku'])->where('product_id', $isExist->id)->whereNotIn('sku', $skus)->get();
            if(!empty($deletedVariants)){
                $ids = array_column($deletedVariants->toArray(), 'admin_graphql_api_id');
                DeleteProductVariant::dispatch(['productId'=>$isExist->admin_graphql_api_id, 'variantsIds'=>$ids]);
            }

        }
    }
}