<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     tags={"Product"},
     *     path="/api/products",
     *     @OA\Response(response="200", description="List Products.")
     * )
     */
    public function getList() {
//        $data = Product::all();
        $data = Product::with(['category', 'product_images'])
            ->get();

        return response()->json($data)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
    /**
     * @OA\Post(
     *     tags={"Product"},
     *     path="/api/product/create",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id","name","price","quantity","description","images[]"},
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number"
     *                 ),
     *                 @OA\Property(
     *                      property="quantity",
     *                      type="number"
     *                  ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Product.")
     * )
     */
    public function create(Request $request) {
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'price' => 'required',
            'description' => 'required',
            'quantity'=>'required',
            'images' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $product = Product::create($input);
        $images = $request->file('images');
        $sizes = [50,150,300,600,1200];
        $manager = new ImageManager(new Driver());
        if ($request->hasFile('images')) {
            foreach ($images as $image) {
                $imageName = uniqid() . '.webp';
                foreach ($sizes as $size) {
                    $fileSave = $size."_".$imageName;
                    $imageRead = $manager->read($image);
                    $imageRead->scale(width: $size);
                    $path=public_path('upload/'.$fileSave);
                    $imageRead->toWebp()->save($path);
                }
                ProductImage::create([
                    'product_id' => $product->id,
                    'name' => $imageName
                ]);
            }
        }
        $product->load('product_images');

        return response()->json($product, 200, [
            'Content-Type' => 'application/json;charset=UTF-8',
            'Charset' => 'utf-8'
        ], JSON_UNESCAPED_UNICODE);
    }
}
