<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
 * @OA\Get(
 *     path="/users",
 *     summary="Mendapatkan daftar semua pengguna (hanya untuk admin)",
 *     tags={"Users"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Daftar pengguna",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Unauthorized access")
 *         )
 *     )
 * )
 */
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

     /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Mendapatkan data pengguna berdasarkan ID",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID pengguna yang akan diambil",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="umur", type="integer", example=25, nullable=true),
     *             @OA\Property(property="status_keanggotaan", type="string", example="active", nullable=true),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        $user->makeHidden(['password']);

        return response()->json($user, 200);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login pengguna",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=500, description="Failed to login")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = auth()->guard('api')->attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to login.'.$e->getMessage()], 500);
        }
    }


     /**
     * @OA\Post(
     *     path="/register",
     *     summary="Register pengguna baru",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="umur", type="integer", example=25),
     *             @OA\Property(property="status_keanggotaan", type="string", example="standard")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=500, description="Failed to register user")
     * )
     */

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'umur' => 'required|integer|min:1',
            'status_keanggotaan' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'umur' => $validatedData['umur'],
                'status_keanggotaan' => $validatedData['status_keanggotaan'] ?? 'standard',
            ]);

            DB::commit();
            return response()->json(['message' => 'User registered successfully'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to register user. '.$e->getMessage()], 500);
        }
    }

  /**
     * @OA\Post(
     *     path="/users",
     *     summary="Menambahkan pengguna baru (hanya untuk admin)",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="umur", type="integer", example=30),
     *             @OA\Property(property="status_keanggotaan", type="string", example="premium")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created successfully"),
     *     @OA\Response(response=500, description="Failed to create user"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'umur' => 'required|integer|min:1',
            'status_keanggotaan' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'umur' => $validatedData['umur'],
                'status_keanggotaan' => $validatedData['status_keanggotaan'] ?? 'standard',
                'password' => Hash::make($validatedData['password']),
            ]);

            DB::commit();
            return response()->json($user, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create user.'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Memperbarui data pengguna berdasarkan ID ",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID pengguna yang akan diperbarui",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="Nama pengguna"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com", description="Email pengguna"),
     *             @OA\Property(property="umur", type="integer", example=25, description="Umur pengguna (opsional, minimal 1)"),
     *             @OA\Property(property="status_keanggotaan", type="string", example="active", description="Status keanggotaan (opsional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $id,
            'umur' => 'required|integer|min:1',
            'status_keanggotaan' => 'nullable|string|max:20',
        ]);

        $user->update($validatedData);
        return response()->json($user, 200);
    }

     /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Menghapus pengguna berdasarkan ID (hanya untuk admin)",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID pengguna yang akan dihapus",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete user",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to delete user.")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        DB::beginTransaction();

        try {
            $user->delete();
            DB::commit();
            return response()->json(['message' => 'User deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete user.'], 500);
        }
    }

}
