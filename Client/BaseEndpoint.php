<?php

namespace Jane\OpenApiRuntime\Client;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\SerializerInterface;

abstract class BaseEndpoint implements Endpoint
{
    protected $queryParameters = [];
    protected $headerParameters = [];
    protected $body;

    abstract public function getMethod(): string;

    abstract public function getBody(SerializerInterface $serializer, $streamFactory = null): array;

    abstract public function getUri(): string;

    abstract protected function transformResponseBody(string $body, int $status, SerializerInterface $serializer, ?string $contentType);

    abstract protected function getAuthenticationScopes(): array;

    protected function getExtraHeaders(): array
    {
        return [];
    }

    public function getQueryString(): string
    {
        $optionsResolved = $this->getQueryOptionsResolver()->resolve($this->queryParameters);
        $optionsResolved = array_map(function ($value) { return null !== $value ? $value : ''; }, $optionsResolved);

        return http_build_query($optionsResolved, null, '&', PHP_QUERY_RFC3986);
    }

    public function getHeaders(array $baseHeaders = []): array
    {
        return array_merge($this->getExtraHeaders(), $baseHeaders, $this->getHeadersOptionsResolver()->resolve($this->headerParameters));
    }

    protected function getQueryOptionsResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }

    protected function getHeadersOptionsResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }

    // ----------------------------------------------------------------------------------------------------
    // Used for OpenApi2 compatibility

    protected function getFormBody(): array
    {
        return [
            ['Content-Type' => ['application/x-www-form-urlencoded']],
            http_build_query($this->getFormOptionsResolver()->resolve($this->formParameters)),
        ];
    }

    protected function getMultipartBody($streamFactory = null): array
    {
        $bodyBuilder = new MultipartStreamBuilder($streamFactory);
        $formParameters = $this->getFormOptionsResolver()->resolve($this->formParameters);

        foreach ($formParameters as $key => $value) {
            $bodyBuilder->addResource($key, $value);
        }

        return [
            ['Content-Type' => ['multipart/form-data; boundary="' . ($bodyBuilder->getBoundary() . '"')]],
            $bodyBuilder->build(),
        ];
    }

    protected function getFormOptionsResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }

    protected function getSerializedBody(SerializerInterface $serializer): array
    {
        return [
            ['Content-Type' => ['application/json']],
            $serializer->serialize($this->body, 'json'),
        ];
    }
}
