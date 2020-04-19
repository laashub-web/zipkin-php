<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Throwable;
use Zipkin\Tags;
use Zipkin\Tracer;
use Zipkin\Tracing;
use Psr\Http\Client\ClientInterface;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Zipkin\Propagation\RequestHeaders;
use Psr\Http\Message\ResponseInterface;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Instrumentation\Http\TraceContextExtractor;
use Zipkin\Instrumentation\Http\Client\Tracing as ClientTracing;

final class Client implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $delegate;

    /**
     * @var callable
     */
    private $injector;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var ClientTracing
     */
    private $clientTracing;

    /**
     * @var TraceContextExtractor
     */
    private $traceContextExtractor;

    public function __construct(
        ClientInterface $delegate,
        Tracing $tracing,
        ClientTracing $clientTracing = null,
        TraceContextExtractor $traceContextExtractor = null
    ) {
        $this->delegate = $delegate;
        $this->injector = $tracing->getPropagation()->getInjector(new RequestHeaders());
        $this->tracer = $tracing->getTracer();
        $this->clientTracing = $clientTracing ?? new DefaultTracing;
        $this->traceContextExtractor = $traceContextExtractor;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $context = null;
        if ($this->traceContextExtractor !== null) {
            $context = $this->traceContextExtractor->extract($request);
        }

        $sampled = $this->clientTracing->requestSampler($request);
        if ($sampled !== null) {
            if ($context instanceof TraceContext) {
                $context = $context->withSampled($sampled);
            } else { // $context is null or SamplingFlags
                $context = DefaultSamplingFlags::create($sampled, false);
            }
        }

        $span = ($context === null ? $this->tracer->nextSpan() : $this->tracer->nextSpan($context));
        $span->setName($this->clientTracing->spanName($request));
        $this->clientTracing->parseClientRequest($request, $span->getContext(), $span);

        ($this->injector)($span->getContext(), $request);
        try {
            $span->start();
            $response = $this->delegate->sendRequest($request);
            $this->clientTracing->parseClientResponse($response, $span->getContext(), $span);
            return $response;
        } catch (Throwable $e) {
            $span->tag(Tags\ERROR, $e->getMessage());
        } finally {
            $span->finish();
        }
    }
}
