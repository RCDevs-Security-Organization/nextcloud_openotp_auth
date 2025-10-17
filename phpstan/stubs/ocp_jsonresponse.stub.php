<?php

namespace OCP\AppFramework\Http;

/**
 * @template-covariant S of int
 * @template T
 * @template-covariant H of array<string, string>
 */
class JSONResponse extends Response
{
    /**
     * @param T $data
     * @param S $statusCode
     * @param H $headers
     * @param int $encodeFlags
     */
    public function __construct(
        $data = [],
        int $statusCode = Http::STATUS_OK,
        array $headers = [],
        int $encodeFlags = 0
    ) {}
}
