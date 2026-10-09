<?php

namespace ipl\Web\Control;

use GuzzleHttp\Psr7\ServerRequest;
use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\FormElement\ButtonElement;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Orm\Common\SortUtil;
use ipl\Orm\Query;
use ipl\Stdlib\Str;
use ipl\Web\Common\FormUid;
use ipl\Web\Widget\Icon;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows to adjust the order of the items to display
 */
class SortControl extends Form
{
    use FormUid;
    use Translation;

    /** @var string Default sort param */
    public const DEFAULT_SORT_PARAM = 'sort';

    protected $defaultAttributes = ['class' => 'sort-control'];

    /** @var string Name of the URL parameter which stores the sort column */
    protected $sortParam = self::DEFAULT_SORT_PARAM;

    /** @var array Possible sort columns as sort string-value pairs */
    private $columns;

    /** @var ?string Default sort string */
    private $default;

    protected $method = 'GET';

    /**
     * Create a new sort control
     *
     * @param array $columns Possible sort columns
     *
     * @internal Use {@see self::create()} instead.
     */
    private function __construct(array $columns)
    {
        $this->setColumns($columns);
    }

    /**
     * Create a new sort control with the given options
     *
     * @param array<string,string> $options A sort spec to label map
     *
     * @return static
     */
    public static function create(array $options)
    {
        $normalized = [];
        foreach ($options as $spec => $label) {
            $normalized[SortUtil::normalizeSortSpec($spec)] = $label;
        }

        $self = new static($normalized);

        $self->on(self::ON_REQUEST, function (ServerRequestInterface $request) use ($self) {
            // If the form is submitted by POST, handleRequest() won't access the URL, so we have to
            if (($sort = $request->getQueryParams()[$self->getSortParam()] ?? null)) {
                $self->populate([$self->getSortParam() => $sort]);
            }
        });

        return $self;
    }

    /**
     * Get the possible sort columns
     *
     * @return array Sort string-value pairs
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the possible sort columns
     *
     * @param array $columns Sort string-value pairs
     *
     * @return $this
     */
    public function setColumns(array $columns): self
    {
        // We're working with lowercase keys throughout the sort control
        $this->columns = array_change_key_case($columns, CASE_LOWER);

        return $this;
    }

    /**
     * Get the default sort string
     *
     * @return ?string
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * Set the default sort string
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): self
    {
        // We're working with lowercase keys throughout the sort control
        $this->default = strtolower($default);

        return $this;
    }

    /**
     * Get the name of the URL parameter which stores the sort
     *
     * @return string
     */
    public function getSortParam(): string
    {
        return $this->sortParam;
    }

    /**
     * Set the name of the URL parameter which stores the sort
     *
     * @param string $sortParam
     *
     * @return $this
     */
    public function setSortParam(string $sortParam): self
    {
        $this->sortParam = $sortParam;

        return $this;
    }

    /**
     * Get the sort string
     *
     * @return ?string
     */
    public function getSort(): ?string
    {
        $sort = $this->getPopulatedValue($this->getSortParam(), $this->getDefault());
        if (! empty($sort)) {
            $columns = $this->getColumns();

            if (! isset($columns[$sort])) {
                // Choose sort string based on the first closest match
                foreach (array_keys($columns) as $key) {
                    if (Str::startsWith($key, $sort)) {
                        $this->populate([$this->getSortParam() => $key]);
                        $sort = $key;

                        break;
                    }
                }
            }
        }

        return $sort;
    }

    /**
     * Sort the given query according to the request
     *
     * @param Query $query
     * @param ?array|string $defaultSort
     *
     * @return $this
     */
    public function apply(Query $query, $defaultSort = null): self
    {
        $default = $defaultSort ?? (array) $query->getModel()->getDefaultSort();
        if (! empty($default)) {
            $this->setDefault(SortUtil::normalizeSortSpec($default));
        }

        $sort = $this->getSort();
        if (! empty($sort)) {
            $query->orderBy(SortUtil::createOrderBy($sort));
        }

        return $this;
    }

    /**
     * Prepare the visual representation of the sort control
     *
     * This is called just before rendering happens. What is being done here, doesn't influence validity in any way.
     * So there is no need to have the result already at hand during validation. Instead, delaying it allows
     * to influence the visual result as long as possible.
     *
     * @return void
     */
    protected function prepareContent(): void
    {
        $columns = $this->getColumns();
        $sort = $this->getSort();

        if (empty($sort)) {
            reset($columns);
            $sort = key($columns);
        }

        $sort = explode(',', $sort, 2);
        list($column, $direction) = Str::symmetricSplit(array_shift($sort), ' ', 2);

        if (! $direction || strtolower($direction) === 'asc') {
            $toggleIcon = 'sort-alpha-down';
            $toggleDirection = 'desc';
        } else {
            $toggleIcon = 'sort-alpha-down-alt';
            $toggleDirection = 'asc';
        }

        if ($direction !== null) {
            $value = implode(',', array_merge(["{$column} {$direction}"], $sort));
            if (! isset($columns[$value])) {
                foreach ([$column, "{$column} {$toggleDirection}"] as $key) {
                    $key = implode(',', array_merge([$key], $sort));
                    if (isset($columns[$key])) {
                        $columns[$value] = $columns[$key];
                        unset($columns[$key]);

                        break;
                    }
                }
            }
        } else {
            $value = implode(',', array_merge([$column], $sort));
        }

        if (! isset($columns[$value])) {
            $columns[$value] = 'Custom';
        }

        $this->addElement('select', $this->getSortParam(), [
            'class'   => 'autosubmit',
            'label'   => $this->translate('Sort By'),
            'options' => $columns,
            'value'   => $value
        ]);
        $select = $this->getElement($this->getSortParam());
        (new DivDecorator())->decorate($select);

        // Apply Icinga Web 2 style, for now
        $select->prependWrapper(HtmlElement::create('div', ['class' => 'icinga-controls']));

        $toggleButton = new ButtonElement($this->getSortParam(), [
            'class' => 'control-button spinner',
            'title' => $this->translate('Change sort direction'),
            'type'  => 'submit',
            'value' => implode(',', array_merge(["{$column} {$toggleDirection}"], $sort))
        ]);
        $toggleButton->add(new Icon($toggleIcon));

        $this->addHtml($toggleButton);
    }

    protected function assemble()
    {
        if ($this->getMethod() === 'POST' && $this->hasAttribute('name')) {
            $this->addElement($this->createUidElement());
        }
    }

    public function renderUnwrapped()
    {
        $this->prepareContent();

        return parent::renderUnwrapped();
    }
}
