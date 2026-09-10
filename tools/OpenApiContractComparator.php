<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tools;

use JsonException;
use RuntimeException;

final class OpenApiContractComparator
{
    /**
     * @return array{missing: list<string>, added: list<string>, changed: list<string>}
     */
    public function compareFiles(string $snapshotPath, string $candidatePath): array
    {
        $snapshot = $this->operations($this->read($snapshotPath));
        $candidate = $this->operations($this->read($candidatePath));

        $missing = array_values(array_diff(array_keys($snapshot), array_keys($candidate)));
        $added = array_values(array_diff(array_keys($candidate), array_keys($snapshot)));
        $changed = [];

        foreach (array_intersect(array_keys($snapshot), array_keys($candidate)) as $operation) {
            if ($snapshot[$operation] !== $candidate[$operation]) {
                $changed[] = $operation;
            }
        }

        sort($missing);
        sort($added);
        sort($changed);

        return compact('missing', 'added', 'changed');
    }

    /** @return array<string, mixed> */
    private function read(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read OpenAPI document: {$path}");
        }

        try {
            $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid OpenAPI JSON: {$path}", previous: $exception);
        }

        if (! is_array($document) || ! isset($document['paths']) || ! is_array($document['paths'])) {
            throw new RuntimeException("OpenAPI document has no paths object: {$path}");
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, array<string, mixed>>
     */
    private function operations(array $document): array
    {
        $operations = [];

        foreach ($document['paths'] as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                continue;
            }

            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                $operation = $pathItem[$method] ?? null;
                if (! is_array($operation)) {
                    continue;
                }

                $operationId = $operation['operationId'] ?? null;
                if (! is_string($operationId) || $operationId === '') {
                    throw new RuntimeException("OpenAPI operation {$method} {$path} has no operationId.");
                }

                $parameters = array_merge(
                    is_array($pathItem['parameters'] ?? null) ? $pathItem['parameters'] : [],
                    is_array($operation['parameters'] ?? null) ? $operation['parameters'] : [],
                );

                $operations[$operationId] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'authenticated' => ! (array_key_exists('security', $operation) && $operation['security'] === []),
                    'parameters' => $this->parameters($parameters, $document),
                    'request' => $this->request($operation['requestBody'] ?? null, $document),
                ];
            }
        }

        ksort($operations);

        return $operations;
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private function parameters(array $parameters, array $document): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $parameter = $this->resolve($parameter, $document);
            $result[] = [
                'name' => $parameter['name'] ?? null,
                'in' => $parameter['in'] ?? null,
                'required' => (bool) ($parameter['required'] ?? false),
                'schema' => $this->schema(is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [], $document),
            ];
        }

        usort($result, static fn (array $left, array $right): int => ($left['in'].'/'.$left['name']) <=> ($right['in'].'/'.$right['name']));

        return $result;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>|null
     */
    private function request(mixed $requestBody, array $document): ?array
    {
        if (! is_array($requestBody)) {
            return null;
        }

        $requestBody = $this->resolve($requestBody, $document);
        $content = is_array($requestBody['content'] ?? null) ? $requestBody['content'] : [];
        $mediaTypes = [];

        foreach ($content as $mediaType => $definition) {
            if (is_string($mediaType) && is_array($definition)) {
                $mediaTypes[$mediaType] = $this->schema(
                    is_array($definition['schema'] ?? null) ? $definition['schema'] : [],
                    $document,
                );
            }
        }

        ksort($mediaTypes);

        return [
            'required' => (bool) ($requestBody['required'] ?? false),
            'content' => $mediaTypes,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function schema(array $schema, array $document): array
    {
        $schema = $this->resolve($schema, $document);
        $signature = [];

        foreach (['type', 'format', 'minimum', 'maximum', 'minLength', 'maxLength', 'minItems', 'pattern'] as $property) {
            if (array_key_exists($property, $schema)) {
                $signature[$property] = $schema[$property];
            }
        }

        if (is_array($schema['required'] ?? null)) {
            $required = array_values(array_filter($schema['required'], 'is_string'));
            sort($required);
            $signature['required'] = $required;
        }

        if (is_array($schema['enum'] ?? null)) {
            $signature['enum'] = array_values($schema['enum']);
        }

        if (is_array($schema['properties'] ?? null)) {
            $properties = [];
            foreach ($schema['properties'] as $name => $property) {
                if (is_string($name) && is_array($property)) {
                    $properties[$name] = $this->schema($property, $document);
                }
            }
            ksort($properties);
            $signature['properties'] = $properties;
        }

        if (is_array($schema['items'] ?? null)) {
            $signature['items'] = $this->schema($schema['items'], $document);
        }

        if (is_array($schema['allOf'] ?? null)) {
            $signature['allOf'] = array_map(
                fn (mixed $part): array => is_array($part) ? $this->schema($part, $document) : [],
                $schema['allOf'],
            );
        }

        return $signature;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function resolve(array $value, array $document): array
    {
        $reference = $value['$ref'] ?? null;
        if (! is_string($reference)) {
            return $value;
        }

        if (! str_starts_with($reference, '#/')) {
            throw new RuntimeException("Only local OpenAPI references are supported: {$reference}");
        }

        $current = $document;
        foreach (explode('/', substr($reference, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                throw new RuntimeException("Unresolvable OpenAPI reference: {$reference}");
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
