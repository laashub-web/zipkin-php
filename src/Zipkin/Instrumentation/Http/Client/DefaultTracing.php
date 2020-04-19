<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Span;
use Zipkin\Tags;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultTracing implements Tracing
{
    public function spanName(RequestInterface $request): string
    {
        return $request->getMethod();
    }

    public function requestSampler(RequestInterface $request): ?bool
    {
        return null;
    }

    public function parseClientRequest(RequestInterface $request, TraceContext $context, Span $span)
    {
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath());
    }

    public function parseClientResponse(ResponseInterface $response, TraceContext $context, Span $span)
    {
        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }
    }
}
