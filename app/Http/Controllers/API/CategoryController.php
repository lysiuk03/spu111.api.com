<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// Контролер для роботи з категоріями
class CategoryController extends Controller
{
    /**
     *  Отримання списку всіх категорій
     * @OA\Get(
     *     tags={"Category"},
     *     path="/api/categories",
     *     @OA\Response(response="200", description="List Categories.")
     * )
     */

    function getAll()
    {
        // Отримуємо всі категорії з бази даних
        $list = Categories::all();
        // Повертаємо відповідь у форматі JSON зі списком категорій
        return response()->json($list,200,['Charset'=>'utf-8']);
    }

    /**
     *  Створення нової категорії
     * @OA\Post(
     *     tags={"Category"},
     *     path="/api/categories/create",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="file",
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Category.")
     * )
     */

    function create(Request $request) {
        $input = $request->all(); //отримуємо із запита усі інпути
        $image = $request->file("image"); //отримуємо із запита фото
        // Створюємо менеджер для роботи з зображеннями та унікальне ім'я для зображення
        $manager = new ImageManager(new Driver()); //менеджер для фото
        $imageName = uniqid().".webp";
        $sizes = [50,150,300,600,1200];
        // Змінюємо розміри та зберігаємо зображення у кожному з розмірів
        foreach ($sizes as $size) {
            $imageSave = $manager->read($image);
            $imageSave->scale(width: $size);
            $path = public_path("upload/".$size."_".$imageName);
            $imageSave->toWebp()->save($path);
        }
        // Оновлюємо дані щодо зображення та створюємо нову категорію в базі даних
        $input["image"]=$imageName;
        $category = Categories::create($input);
        // Повертаємо відповідь із створеною категорією
        return response()->json($category,200,['Charset'=>'utf-8']);
    }
    /**
     * Отримання категорії за ідентифікатором
     * @OA\Get(
     *     tags={"Category"},
     *     path="/api/categories/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(response="200", description="List Categories."),
     * @OA\Response(
     *    response=404,
     *    description="Wrong id",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong Category Id has been sent. Pls try another one.")
     *        )
     *     )
     * )
     */
    public function getById($id) {
        // Знаходимо категорію за ідентифікатором
        $category = Categories::findOrFail($id);
        // Повертаємо відповідь із знайденою категорією
        return response()->json($category,200, ['Charset' => 'utf-8']);
    }

    /**
     * Видалення категорії за ідентифікатором
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     tags={"Category"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успішне видалення категорії"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Категорії не знайдено"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизований"
     *     )
     * )
     */
    public function delete($id) {
        // Знаходимо категорію за ідентифікатором
        $category = Categories::findOrFail($id);
        $sizes = [50,150,300,600,1200];
        // Видаляємо файли зображень для кожного розміру
        foreach ($sizes as $size) {
            $fileSave = $size."_".$category->image;
            $path=public_path('upload/'.$fileSave);
            if(file_exists($path))
                unlink($path);
        }
        // Видаляємо категорію з бази даних
        $category->delete();
        // Повертаємо відповідь про успішне видалення
        return response()->json("",200, ['Charset' => 'utf-8']);
    }

    /**
     * Редагування категорії за ідентифікатором
     * @OA\Post(
     *     tags={"Category"},
     *     path="/api/categories/edit/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="file"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Category.")
     * )
     */
    public function edit($id, Request $request) {
        // Знаходимо категорію за ідентифікатором
        $category = Categories::findOrFail($id);
        $imageName=$category->image;
        $inputs = $request->all();
        // Перевіряємо, чи надіслано нове зображення
        if($request->hasFile("image")) {
            $image = $request->file("image");
            $imageName = uniqid() . ".webp";
            $sizes = [50, 150, 300, 600, 1200];
            // Створюємо менеджер для роботи з зображеннями
            $manager = new ImageManager(new Driver());
            // Змінюємо розміри та оновлюємо зображення для кожного розміру
            foreach ($sizes as $size) {
                $fileSave = $size . "_" . $imageName;
                $imageRead = $manager->read($image);
                $imageRead->scale(width: $size);
                $path = public_path('upload/' . $fileSave);
                $imageRead->toWebp()->save($path);
                // Видаляємо попереднє зображення для даного розміру
                $removeImage = public_path('upload/'.$size."_". $category->image);
                if(file_exists($removeImage))
                    unlink($removeImage);
            }
        }
        // Оновлюємо дані категорії та повертаємо відповідь
        $inputs["image"]= $imageName;
        $category->update($inputs);
        return response()->json($category,200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
}
