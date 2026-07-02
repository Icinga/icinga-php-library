<?php

namespace ipl\Web\FormElement;

use ArrayIterator;
use Exception;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use Psr\Http\Message\ServerRequestInterface;

use function ipl\Stdlib\yield_groups;

class SearchSuggestions extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'ul';

    /** @var iterable */
    protected iterable $provider;

    /** @var ?string */
    protected ?string $failureMessage = null;

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
     * * details: {@see ValidHtml} to render inside a button element instead of an input
     * * class: A CSS class
     * * title: A message shown upon hover on the term
     *
     * Any excess key is also transferred to the client, but currently unused.
     *
     * @param iterable $provider
     */
    public function __construct(iterable $provider = [])
    {
        $this->provider = $provider;
    }

    /**
     * Show a failure message
     *
     * @param ?string $message
     *
     * @return $this
     */
    public function showFailureMessage(?string $message)
    {
        $this->failureMessage = $message;

        return $this;
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
        $requestData = static::parseRequest($request);
        if ($requestData === null) {
            return $this;
        }

        $this->setSearchTerm($requestData['term']['label']);
        $this->setOriginalSearchValue($requestData['term']['search']);
        $this->setExcludeTerms($requestData['exclude'] ?? []);

        return $this;
    }

    protected function assembleSuggestions(): void
    {
        $groupingCallback = $this->getGroupingCallback();
        if ($groupingCallback) {
            $iterator = $this->provider;
            if (is_array($iterator)) {
                $iterator = new ArrayIterator($iterator);
            }

            $provider = yield_groups($iterator, $groupingCallback);
        } else {
            $provider = ['' => $this->provider];
        }

        /** @var iterable<?string, array<array<string, string|ValidHtml>>> $provider */
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
                $details = $data['details'] ?? null;
                unset($data['details']);
                foreach ($data as $name => $value) {
                    $attributes["data-$name"] = $value;
                }

                if ($details instanceof ValidHtml) {
                    $attributes['class'] = 'has-details';
                    $content = new HtmlElement(
                        'button',
                        Attributes::create($attributes),
                        $details
                    );
                } else {
                    $content = new HtmlElement(
                        'input',
                        Attributes::create($attributes)
                    );
                }

                $this->addHtml(
                    new HtmlElement(
                        'li',
                        null,
                        $content
                    )
                );
            }
        }
    }

    protected function assemble(): void
    {
        if ($this->failureMessage === null) {
            try {
                $this->assembleSuggestions();
            } catch (Exception $e) {
                $this->failureMessage = $e->getMessage();
                $this->setContent(null);
            }
        }

        if ($this->failureMessage !== null) {
            $this->addHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => 'failure-message']),
                new HtmlElement('em', null, Text::create($this->translate('Can\'t search:'))),
                Text::create($this->failureMessage)
            ));
            return;
        }

        if ($this->isEmpty()) {
            $this->addHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => 'nothing-to-suggest']),
                new HtmlElement('em', null, Text::create($this->translate('Nothing to suggest')))
            ));
        }
    }

    /**
     * Get the JSON-decoded body of a suggestion request
     *
     * Returns `null` for non-POST requests or an empty body
     *
     * @param ServerRequestInterface $request
     *
     * @return ?array<string, mixed>
     */
    public static function parseRequest(ServerRequestInterface $request): ?array
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        /** @var array<string, array<int|string, string>> $requestData */
        $requestData = json_decode($request->getBody()->read(8192), true);
        if (empty($requestData)) {
            return null;
        }

        return $requestData;
    }
}
