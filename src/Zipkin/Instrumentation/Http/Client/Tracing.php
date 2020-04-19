<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Span;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Tracing
{
    public function spanName(RequestInterface $request): string;

    public function requestSampler(RequestInterface $request): ?bool;

    public function parseClientRequest(RequestInterface $request, TraceContext $context, Span $span);

    public function parseClientResponse(ResponseInterface $response, TraceContext $context, Span $span);
}
