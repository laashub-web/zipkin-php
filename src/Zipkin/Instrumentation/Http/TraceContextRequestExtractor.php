<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

use Psr\Http\Message\RequestInterface;
use Zipkin\Propagation\SamplingFlags;

final class TraceContextRequestExtractor
{
    public function extract(RequestInterface $request): ?SamplingFlags
    {
        if ($request instanceof TraceContextRequest) {
            return $request->getTraceContext();
        }

        return null;
    }
}
