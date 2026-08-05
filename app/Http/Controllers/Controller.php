<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Get(
 *     path="/api/user",
 *     summary="Get the authenticated user",
 *     tags={"User"},
 *     security={{"Bearer":{}}},
 *
 *     @OA\Response(response=200, description="The authenticated user"),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
