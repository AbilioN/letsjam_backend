<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Application\UseCases\Auth\LoginUseCase;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginUseCase $loginUseCase)
    {
        $result = $loginUseCase->execute(
            $request->email, 
            $request->password
        );

        return response()->json($result);
    }
}
