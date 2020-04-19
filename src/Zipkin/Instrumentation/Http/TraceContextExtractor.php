<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

use Psr\Http\Message\RequestInterface;
use Zipkin\Propagation\SamplingFlags;

interface TraceContextExtractor
{
    public function extract(RequestInterface $request): ?SamplingFlags;
}
