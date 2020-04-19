<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Server\Http;

use Throwable;
use Zipkin\Tags;
use Zipkin\Tracing;
use Zipkin\Propagation\RequestHeaders;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Middleware implements MiddlewareInterface
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var callable
     */
    private $extractor;

    public function __construct(Tracing $tracing)
    {
        $this->tracer = $tracing->getTracer();
        $this->extractor = $tracing->getPropagation()->getExtractor(new RequestHeaders());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extractedContext = ($this->extractor)($request);
        $span = $this->tracer->nextSpan($extractedContext);
        $span->setName($request->getMethod());
        $span->start();
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath());
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        try {
            $response = $handler->handle($request);
            $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
            if ($response->getStatusCode() > 399) {
                $span->tag(Tags\ERROR, (string) $response->getStatusCode());
            }
            return $response;
        } catch (Throwable $e) {
            $span->tag(Tags\ERROR, $e->getMessage());
        } finally {
            $span->finish();
        }
    }
}
