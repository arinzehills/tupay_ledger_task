<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Settlement Debug",
 *     description="Settlement webhook debug endpoints (development only, requires APP_DEBUG=true)"
 * )
 *
 * @OA\Get(
 *     path="/api/debug/settlement-signature",
 *     tags={"Settlement Debug"},
 *     summary="Generate settlement webhook signature",
 *     description="DEVELOPMENT ONLY - Generates a valid HMAC-SHA256 signature for testing the settlement webhook. Only available when APP_DEBUG=true.",
 *
 *     @OA\Parameter(
 *         name="transaction_id",
 *         in="query",
 *         description="Transaction ID to settle",
 *         required=false,
 *
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Settlement status",
 *         required=false,
 *
 *         @OA\Schema(type="string", enum={"completed", "failed"}, example="completed")
 *     ),
 *
 *     @OA\Parameter(
 *         name="settled_at",
 *         in="query",
 *         description="Settlement timestamp (ISO 8601)",
 *         required=false,
 *
 *         @OA\Schema(type="string", format="date-time")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Generated signature and payload",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="transaction_id", type="integer", example=1),
 *             @OA\Property(property="status", type="string", example="completed"),
 *             @OA\Property(property="settled_at", type="string", example="2026-08-05T13:00:00Z"),
 *             @OA\Property(property="signature", type="string", example="abc123..."),
 *             @OA\Property(property="curl_example", type="string", description="Ready-to-use curl command")
 *         )
 *     ),
 *
 *     @OA\Response(response=403, description="Debug mode disabled (APP_DEBUG=false)"),
 *     @OA\Response(response=404, description="Transaction not found")
 * )
 */
class SettlementDebugDocs {}
