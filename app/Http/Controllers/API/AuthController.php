<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    /**
     * Реєстрація new user.
     * @OA\Post(
     *     tags={"Auth"},
     *     path="/api/register",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email", "lastName", "name", "phone", "image", "password", "password_confirmation"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lastName",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Category.")
     * )
     */
    public function register(Request $request) {
        $input = $request->all();
        $validation = Validator::make($input,[
            'name'=> 'required|string',
            'lastName'=> 'required|string',
            'image'=> 'required|string',
            'phone'=> 'required|string',
            'email'=> 'required|email',
            'password'=> 'required|string|min:6',
        ]);

        if($validation->fails()) {
            return response()->json($validation->errors(), Response::HTTP_BAD_REQUEST);
        }

        $imageName = uniqid().".webp";
        $sizes = [50,150,300,600,1200];
        $manager = new ImageManager(new Driver()); //менеджер для фото
        foreach ($sizes as $size) {
            $imageSave = $manager->read($input["image"]);
            $imageSave->scale(width: $size);
            $path = public_path("upload/".$size."_".$imageName);
            $imageSave->toWebp()->save($path);
        }
        $user = User::create(array_merge(
            $validation->validated(),
            ['password'=>bcrypt($input['password']), 'image'=>$imageName]
        ));
        return response()->json(['user'=>$user], Response::HTTP_OK);
    }
    /**
     * Вхід user
     * @OA\Post(
     *     tags={"Auth"},
     *     path="/api/login",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *    @OA\Response(response="200", description="Login successful.", @OA\JsonContent(
     *          @OA\Property(property="token", type="string", description="JWT Token")
     *     ))
     * )
     */
    public function login(Request $request) {
        $input = $request->all();
        $validation = Validator::make($input,[
            'email'=> 'required|email',
            'password'=> 'required|string|min:6',
        ]);


        if ($validation->fails()) {
            return response()->json($validation->errors(), Response::HTTP_BAD_REQUEST);
        }

        // Перевірка логіну та паролю
        if (!Auth::attempt($input)) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Отримання залогіненого користувача
        $user = Auth::user();

        // Генерація та повернення JWT-токена
        $token = $user->createToken('AuthToken')->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_OK);
    }
}
