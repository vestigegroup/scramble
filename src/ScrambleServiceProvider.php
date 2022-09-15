<?php

namespace Dedoc\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Infer;
use Dedoc\Scramble\Support\OperationBuilder;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Dedoc\Scramble\Support\OperationExtensions\ResponseExtension;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\AnonymousResourceCollectionTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\LengthAwarePaginatorTypeToSchema;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\InferExtensions\AnonymousResourceCollectionTypeInfer;
use Dedoc\Scramble\Support\InferExtensions\JsonResourceTypeInfer;
use Dedoc\Scramble\Support\InferExtensions\PhpDocTypeInfer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ScrambleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('scramble')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasViews('scramble');

        $this->app->bind(Infer::class, function () {
            $extensions = config('scramble.extensions', []);

            $expressionTypeInferringExtensions = array_values(array_filter(
                $extensions,
                fn ($e) => is_a($e, ExpressionTypeInferExtension::class, true),
            ));

            return new Infer(array_merge($expressionTypeInferringExtensions, [
                AnonymousResourceCollectionTypeInfer::class,
                JsonResourceTypeInfer::class,
                PhpDocTypeInfer::class,
            ]));
        });

        $this->app->singleton(TypeTransformer::class, function () {
            $extensions = config('scramble.extensions', []);

            $typesToSchemaExtensions = array_values(array_filter(
                $extensions,
                fn ($e) => is_a($e, TypeToSchemaExtension::class, true),
            ));

            return new TypeTransformer(
                $this->app->make(Infer::class),
                new Components,
                array_merge($typesToSchemaExtensions, [
                    JsonResourceTypeToSchema::class,
                    AnonymousResourceCollectionTypeToSchema::class,
                    LengthAwarePaginatorTypeToSchema::class,
                ]),
            );
        });

        $this->app->bind(OperationBuilder::class, function () {
            $extensions = config('scramble.extensions', []);

            $operationExtensions = array_values(array_filter(
                $extensions,
                fn ($e) => is_a($e, OperationExtension::class, true),
            ));

            $extensions = array_merge($operationExtensions, [
                RequestBodyExtension::class,
                ResponseExtension::class,
            ]);

            return new OperationBuilder(
                array_map(fn ($extensionClass) => $this->app->make($extensionClass), $extensions),
            );
        });
    }
}
