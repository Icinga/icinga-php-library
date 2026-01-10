<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

use function ipl\Stdlib\yield_groups;

class SearchSuggestions extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'ul';

    /** @var Traversable */
    protected $provider;

    /** @var ?callable */
    protected $groupingCallback;

    /** @var ?string */
    protected $searchTerm;

    /** @var ?string */
    protected $searchPattern;

    /** @var ?string */
    protected $originalValue;

    /** @var string[] */
    protected $excludeTerms = [];

    /**
     * Create new SearchSuggestions
     *
     * The provider must deliver terms in form of arrays with the following keys:
     * * (required) search: The search value
     * * label: A human-readable label
     * * class: A CSS class
     * * title: A message shown upon hover on the term
     *
     * Any excess key is also transferred to the client, but currently unused.
     *
     * @param Traversable $provider
     */
    public function __construct(Traversable $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Set a callback to identify groups for terms delivered by the provider
     *
     * The callback must return a string which is used as label for the group.
     * Its interface is: `function (array $data): string`
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setGroupingCallback(callable $callback): self
    {
        $this->groupingCallback = $callback;

        return $this;
    }

    /**
     * Get the callback used to identify groups for terms delivered by the provider
     *
     * @return ?callable
     */
    public function getGroupingCallback(): ?callable
    {
        return $this->groupingCallback;
    }

    /**
     * Set the search term (can contain `*` wildcards)
     *
     * @param string $term
     *
     * @return $this
     */
    public function setSearchTerm(string $term): self
    {
        $this->searchTerm = $term;
        $this->setSearchPattern(
            '/' . str_replace(
                '\\000',
                '.*',
                preg_quote(
                    str_replace(
                        '*',
                        "\0",
                        $term
                    ),
                    '/'
                )
            ) . '/i'
        );

        return $this;
    }

    /**
     * Get the search term
     *
     * @return ?string
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /**
     * Set the search pattern used by {@see matchSearch}
     *
     * @param string $pattern
     *
     * @return $this
     */
    protected function setSearchPattern(string $pattern): self
    {
        $this->searchPattern = $pattern;

        return $this;
    }

    /**
     * Set the original search value
     *
     * The one without automatically added wildcards.
     *
     * @param string $term
     *
     * @return $this
     */
    public function setOriginalSearchValue(string $term): self
    {
        $this->originalValue = $term;

        return $this;
    }

    /**
     * Get the original search value
     *
     * @return ?string
     */
    public function getOriginalSearchValue(): ?string
    {
        return $this->originalValue;
    }

    /**
     * Set the terms to exclude in the suggestion list
     *
     * @param string[] $terms
     *
     * @return $this
     */
    public function setExcludeTerms(array $terms): self
    {
        $this->excludeTerms = $terms;

        return $this;
    }

    /**
     * Get the terms to exclude in the suggestion list
     *
     * @return string[]
     */
    public function getExcludeTerms(): array
    {
        return $this->excludeTerms;
    }

    /**
     * Match the given search term against the users search
     *
     * @param string $term
     *
     * @return bool Whether the search matches or not
     */
    public function matchSearch(string $term): bool
    {
        if (! $this->searchPattern || $this->searchPattern === '.*') {
            return true;
        }

        return (bool) preg_match($this->searchPattern, $term);
    }

    /**
     * Load suggestions as requested by the client
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function forRequest(ServerRequestInterface $request): self
    {
        if ($request->getMethod() !== 'POST') {
            return $this;
        }

        /** @var array<string, array<int|string, string>> $requestData */
        $requestData = json_decode($request->getBody()->read(8192), true);
        if (empty($requestData)) {
            return $this;
        }

        $this->setSearchTerm($requestData['term']['label']);
        $this->setOriginalSearchValue($requestData['term']['search']);
        $this->setExcludeTerms($requestData['exclude'] ?? []);

        return $this;
    }

    protected function assemble(): void
    {
        $groupingCallback = $this->getGroupingCallback();
        if ($groupingCallback) {
            $provider = yield_groups($this->provider, $groupingCallback);
        } else {
            $provider = ['' => $this->provider];
        }

        /** @var iterable<?string, array<array<string, string>>> $provider */
        foreach ($provider as $group => $suggestions) {
            if ($group) {
                $this->addHtml(
                    new HtmlElement(
                        'li',
                        Attributes::create(['class' => 'suggestion-title']),
                        Text::create($group)
                    )
                );
            }

            foreach ($suggestions as $data) {
                $attributes = [
                    'type' => 'button',
                    'value' => $data['label'] ?? $data['search']
                ];
                foreach ($data as $name => $value) {
                    $attributes["data-$name"] = $value;
                }

                $this->addHtml(
                    new HtmlElement(
                        'li',
                        null,
                        new HtmlElement(
                            'input',
                            Attributes::create($attributes)
                        )
                    )
                );
            }
        }

        if ($this->isEmpty()) {
            $this->addHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => 'nothing-to-suggest']),
                new HtmlElement('em', null, Text::create($this->translate('Nothing to suggest')))
            ));
        }
    }
}
