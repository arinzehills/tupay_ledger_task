<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Ledger",
 *     description="Ledger history endpoints"
 * )
 *
 * @OA\Get(
 *     path="/api/ledger/{wallet}",
 *     tags={"Ledger"},
 *     summary="Get wallet ledger entries",
 *     security={{"Bearer":{}}},
 *     @OA\Parameter(
 *         name="wallet",
 *         in="path",
 *         required=true,
 *         description="Wallet ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Entries per page",
 *         @OA\Schema(type="integer", default=50)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ledger entries retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="wallet_id", type="integer"),
 *             @OA\Property(property="currency", type="string", example="NGN"),
 *             @OA\Property(property="balance", type="integer"),
 *             @OA\Property(property="entries", type="array",
 *                 @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="type", type="string", enum={"debit","credit"}),
 *                     @OA\Property(property="amount", type="integer"),
 *                     @OA\Property(property="reference_id", type="string"),
 *                     @OA\Property(property="created_at", type="string", format="date-time")
 *                 )
 *             ),
 *             @OA\Property(property="pagination", type="object",
 *                 @OA\Property(property="total", type="integer"),
 *                 @OA\Property(property="per_page", type="integer"),
 *                 @OA\Property(property="current_page", type="integer"),
 *                 @OA\Property(property="last_page", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=403, description="Unauthorized wallet access")
 * )
 */
class LedgerDocs
{
}