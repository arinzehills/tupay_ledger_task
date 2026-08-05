<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Swap",
 *     description="Currency swap endpoints"
 * )
 *
 * @OA\Post(
 *     path="/api/swap",
 *     tags={"Swap"},
 *     summary="Execute currency swap",
 *     security={{"Bearer":{}}},
 *     @OA\Parameter(
 *         name="X-Elevated-Action-Token",
 *         in="header",
 *         required=true,
 *         description="EAT token from 2FA challenge",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"source_currency","destination_currency","amount"},
 *             @OA\Property(property="source_currency", type="string", example="NGN"),
 *             @OA\Property(property="destination_currency", type="string", example="CNY"),
 *             @OA\Property(property="amount", type="integer", example=1000000)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Swap executed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="transaction_id", type="integer"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="source_amount", type="integer"),
 *             @OA\Property(property="destination_amount", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Invalid or expired EAT token"),
 *     @OA\Response(response=409, description="Insufficient balance"),
 *     @OA\Response(response=422, description="Invalid request parameters")
 * )
 */
class SwapDocs
{
}