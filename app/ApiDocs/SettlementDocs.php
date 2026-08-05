<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Settlement",
 *     description="Settlement webhook endpoints"
 * )
 *
 * @OA\Post(
 *     path="/api/webhook/settlement",
 *     tags={"Settlement"},
 *     summary="Process settlement webhook",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"transaction_id","status","settled_at","signature"},
 *
 *             @OA\Property(property="transaction_id", type="integer", example=1),
 *             @OA\Property(property="status", type="string", enum={"completed","failed"}),
 *             @OA\Property(property="settled_at", type="string", format="date-time", example="2026-08-05T12:00:00Z"),
 *             @OA\Property(property="signature", type="string", description="HMAC-SHA256 signature")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Settlement processed successfully",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Invalid signature"),
 *     @OA\Response(response=404, description="Transaction not found")
 * )
 */
class SettlementDocs {}
