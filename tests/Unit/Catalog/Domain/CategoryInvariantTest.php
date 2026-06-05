<?php

declare(strict_types=1);

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Exception\InvalidCategoryHierarchyException;

it('creates a root category with null parentId', function (): void {
    $cat = Category::createRoot(null, 'Biquínis', 'biquinis');

    expect($cat->parentId())->toBeNull()
        ->and($cat->isRoot())->toBeTrue()
        ->and($cat->name())->toBe('Biquínis')
        ->and($cat->slug())->toBe('biquinis')
        ->and($cat->active())->toBeTrue();
});

it('creates a child category with the parent id', function (): void {
    $root = Category::createRoot(5, 'Biquínis', 'biquinis');
    $child = Category::createChild(null, 'Top', 'top', $root);

    expect($child->parentId())->toBe(5)
        ->and($child->isRoot())->toBeFalse();
});

it('throws InvalidCategoryHierarchyException when creating a child under another child', function (): void {
    $root = Category::createRoot(1, 'Biquínis', 'biquinis');
    $child = Category::createChild(2, 'Top', 'top', $root);

    expect(fn () => Category::createChild(null, 'Sub', 'sub', $child))
        ->toThrow(InvalidCategoryHierarchyException::class);
});

it('reconstitutes a category without running invariant checks', function (): void {
    $cat = Category::reconstitute(10, 'Roupa', 'roupa', 3, false);

    expect($cat->id())->toBe(10)
        ->and($cat->parentId())->toBe(3)
        ->and($cat->active())->toBeFalse();
});

it('throws with the correct error code', function (): void {
    $root = Category::createRoot(1, 'Raiz', 'raiz');
    $child = Category::createChild(2, 'Filho', 'filho', $root);

    try {
        Category::createChild(null, 'Neto', 'neto', $child);
    } catch (InvalidCategoryHierarchyException $e) {
        expect($e->errorCode())->toBe('invalid_category_hierarchy')
            ->and($e->httpStatus())->toBe(422);
    }
});
