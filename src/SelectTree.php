<?php

namespace CodeWithDennis\FilamentSelectTree;

use Closure;
use Filament\Forms\Components\Concerns\CanBeDisabled;
use Filament\Forms\Components\Concerns\CanBeSearchable;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class SelectTree extends Field
{
    use CanBeDisabled;
    use CanBeSearchable;
    use HasPlaceholder;

    protected string $view = 'select-tree::select-tree';

    protected bool $withCount = false;

    protected bool $alwaysOpen = false;

    protected string $emptyLabel = '';

    protected bool $independent = true;

    protected string $titleAttribute;

    protected string $parentAttribute;

    protected null|int|string $parentNullValue = null;

    protected bool $clearable = true;

    protected bool $expandSelected = true;

    protected bool $enableBranchNode = false;

    protected bool $grouped = true;

    protected string|Closure $relationship;

    protected ?Closure $modifyQueryUsing;

    protected Closure|int $defaultOpenLevel = 0;

    protected string $direction = 'auto';

    protected Closure|array $disabledOptions = [];

    protected function setUp(): void
    {
        // Load the state from relationships using a callback function.
        $this->loadStateFromRelationshipsUsing(static function (self $component): void {
            // Get the current relationship associated with the component.
            $relationship = $component->getRelationship();

            // Check if the relationship is a BelongsToMany relationship.
            if ($relationship instanceof BelongsToMany) {
                // Retrieve related model instances and extract their IDs into an array.
                $state = $relationship->getResults()
                    ->pluck($relationship->getRelatedKeyName())
                    ->toArray();

                // Set the component's state with the extracted IDs.
                $component->state($state);
            }
        });

        // Save relationships using a callback function.
        $this->saveRelationshipsUsing(static function (self $component, $state) {
            // Check if the component's relationship is a BelongsToMany relationship.
            if ($component->getRelationship() instanceof BelongsToMany) {
                // Wrap the state in a collection and convert it to an array if it's not set.
                $state = Collection::wrap($state ?? []);

                // Sync the relationship with the provided state (IDs).
                $component->getRelationship()->sync($state->toArray());
            }
        });
    }

    private function buildTree(): Collection
    {
        // Start with two separate query builders
        $nullParentQuery = $this->getRelationship()->getRelated()->query()->where($this->getParentAttribute(), $this->getParentNullValue());
        $nonNullParentQuery = $this->getRelationship()->getRelated()->query()->whereNot($this->getParentAttribute(), $this->getParentNullValue());

        // If we're not at the root level and a modification callback is provided, apply it to null query
        if ($this->modifyQueryUsing) {
            $nullParentQuery = $this->evaluate($this->modifyQueryUsing, ['query' => $nullParentQuery]);
        }

        // Fetch results for both queries
        $nullParentResults = $nullParentQuery->get();
        $nonNullParentResults = $nonNullParentQuery->get();

        // Combine the results from both queries
        $combinedResults = $nullParentResults->concat($nonNullParentResults);

        return $this->buildTreeFromResults($combinedResults);
    }

    private function buildTreeFromResults($results, $parent = null): Collection
    {
        // Assign the parent's null value to the $parent variable if it's not null
        if ($parent == null || $parent == $this->getParentNullValue()) {
            $parent = $this->getParentNullValue() ?? $parent;
        }

        // Create a collection to store the tree
        $tree = collect();

        // Create a mapping of results by their parent IDs for faster lookup
        $resultMap = [];

        // Group results by their parent IDs
        foreach ($results as $result) {
            $parentId = $result->{$this->getParentAttribute()};
            if (! isset($resultMap[$parentId])) {
                $resultMap[$parentId] = [];
            }
            $resultMap[$parentId][] = $result;
        }

        // Recursively build the tree starting from the root (null parent)
        $rootResults = $resultMap[$parent] ?? [];
        foreach ($rootResults as $result) {
            // Build a node and add it to the tree
            $node = $this->buildNode($result, $resultMap);
            $tree->push($node);
        }

        return $tree;
    }

    private function buildNode($result, $resultMap): array
    {
        // Create a node with 'name' and 'value' attributes
        $node = [
            'name' => $result->{$this->getTitleAttribute()},
            'value' => $result->getKey(),
            'disabled' => in_array($result->getKey(), $this->getDisabledOptions()),
        ];

        // Check if the result has children
        if (isset($resultMap[$result->getKey()])) {
            $children = collect();
            // Recursively build child nodes
            foreach ($resultMap[$result->getKey()] as $child) {
                $childNode = $this->buildNode($child, $resultMap);
                $children->push($childNode);
            }
            // Add children to the node
            $node['children'] = $children->toArray();
        }

        return $node;
    }

    public function relationship(string $relationship, string $titleAttribute, string $parentAttribute, Closure $modifyQueryUsing = null): self
    {
        $this->relationship = $relationship;
        $this->titleAttribute = $titleAttribute;
        $this->parentAttribute = $parentAttribute;
        $this->modifyQueryUsing = $modifyQueryUsing;

        return $this;
    }

    public function withCount(bool $withCount = true): static
    {
        $this->withCount = $withCount;

        return $this;
    }

    public function direction(string $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function parentNullValue(int|string $parentNullValue = null): static
    {
        $this->parentNullValue = $parentNullValue;

        return $this;
    }

    public function getRelationship(): BelongsToMany|BelongsTo
    {
        return $this->getModelInstance()->{$this->evaluate($this->relationship)}();
    }

    public function getTitleAttribute(): string
    {
        return $this->evaluate($this->titleAttribute);
    }

    public function getParentAttribute(): string
    {
        return $this->evaluate($this->parentAttribute);
    }

    public function getParentNullValue(): null|int|string
    {
        return $this->evaluate($this->parentNullValue);
    }

    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function grouped(bool $grouped = true): static
    {
        $this->grouped = $grouped;

        return $this;
    }

    public function defaultOpenLevel(Closure|int $defaultOpenLevel = 0): static
    {
        $this->defaultOpenLevel = $defaultOpenLevel;

        return $this;
    }

    public function expandSelected(bool $expandSelected = true): static
    {
        $this->expandSelected = $expandSelected;

        return $this;
    }

    public function emptyLabel(string $emptyLabel): static
    {
        $this->emptyLabel = $emptyLabel;

        return $this;
    }

    public function independent(bool $independent = true): static
    {
        $this->independent = $independent;

        return $this;
    }

    public function disabledOptions(Closure|array $disabledOptions): static
    {
        $this->disabledOptions = $disabledOptions;

        return $this;
    }

    public function alwaysOpen(bool $alwaysOpen = true): static
    {
        $this->alwaysOpen = $alwaysOpen;

        return $this;
    }

    public function enableBranchNode(bool $enableBranchNode = true): static
    {
        $this->enableBranchNode = $enableBranchNode;

        return $this;
    }

    public function getTree(): Collection|array
    {
        return $this->evaluate($this->buildTree());
    }

    public function getExpandSelected(): bool
    {
        return $this->evaluate($this->expandSelected);
    }

    public function getGrouped(): bool
    {
        return $this->evaluate($this->grouped);
    }

    public function getIndependent(): bool
    {
        return $this->evaluate($this->independent);
    }

    public function getWithCount(): bool
    {
        return $this->evaluate($this->withCount);
    }

    public function getMultiple(): bool
    {
        return $this->evaluate($this->getRelationship() instanceof BelongsToMany);
    }

    public function getClearable(): bool
    {
        return $this->evaluate($this->clearable);
    }

    public function getAlwaysOpen(): bool
    {
        return $this->evaluate($this->alwaysOpen);
    }

    public function getEnableBranchNode(): bool
    {
        return $this->evaluate($this->enableBranchNode);
    }

    public function getDefaultOpenLevel(): int
    {
        return $this->evaluate($this->defaultOpenLevel);
    }

    public function getEmptyLabel(): string
    {
        return $this->emptyLabel ? $this->evaluate($this->emptyLabel) : __('No results found');
    }

    public function getDirection(): string
    {
        return $this->evaluate($this->direction);
    }

    public function getDisabledOptions(): array
    {
        return $this->evaluate($this->disabledOptions);
    }
}
