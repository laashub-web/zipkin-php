<?php

declare(strict_types=1);

namespace ZipkinTests\Instrumentation\Http;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\TraceContextRequest;
use Zipkin\Instrumentation\Http\TraceContextRequestExtractor;

final class TraceContextRequestExtractorTest extends TestCase
{
    public function testExtractForTracedRequestSuccess()
    {
        $request = new Request('GET', 'http://mytest/things');
        $context = TraceContext::createAsRoot();
        $traceContextRequest = TraceContextRequest::wrap($request, $context);
        $extractor = new TraceContextRequestExtractor();
        $this->assertSame($context, $extractor->extract($traceContextRequest));
    }

    public function testExtractForNotTracedRequestReturnsNullContext()
    {
        $request = new Request('GET', 'http://mytest/things');
        $extractor = new TraceContextRequestExtractor();
        $this->assertNull($extractor->extract($request));
    }
}
