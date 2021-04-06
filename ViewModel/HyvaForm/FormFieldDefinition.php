<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Hyva\Admin\Model\FormEntity\FormFieldValueProcessorFactory;
use Hyva\Admin\Model\FormEntity\FormFieldValueProcessorInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use function array_filter as filter;
use function array_merge as merge;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var FormFieldValueProcessorFactory
     */
    private $fieldValueProcessorFactory;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @var string|null
     */
    private $inputType;

    /**
     * @var string
     */
    private $valueType;

    /**
     * @var string|null
     */
    private $groupId;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var bool|null
     */
    private $enabled;

    /**
     * @var bool|null
     */
    private $excluded;

    /**
     * @var string|null
     */
    private $valueProcessor;

    /**
     * @var bool
     */
    private $joinColumns;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $formName;

    private $value;

    /**
     * @var int|null
     */
    private $sortOrder;

    private $valueTypeToInputTypeMap = [
        'string'  => 'text',
        'decimal' => 'text',
        'int'     => 'number',
        'bool'    => 'boolean',
        'text'    => 'textarea',
        'array'   => 'select',
    ];

    public function __construct(
        LayoutInterface $layout,
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        FormFieldValueProcessorFactory $fieldValueProcessorFactory,
        string $formName,
        string $name,
        ?string $valueType = null,
        $value = null,
        ?string $label = null,
        ?array $options = [],
        ?string $inputType = null,
        ?string $groupId = null,
        ?string $template = null,
        ?bool $isEnabled = null,
        ?bool $isExcluded = null,
        bool $joinColumns = false,
        ?string $valueProcessor = null,
        ?int $sortOrder = null
    ) {
        $this->layout                     = $layout;
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
        $this->fieldValueProcessorFactory = $fieldValueProcessorFactory;
        $this->formName                   = $formName;
        $this->name                       = $name;
        $this->valueType                  = $valueType;
        $this->value                      = $value;
        $this->label                      = $label;
        $this->options                    = $options;
        $this->inputType                  = $inputType;
        $this->groupId                    = $groupId;
        $this->template                   = $template;
        $this->enabled                    = $isEnabled;
        $this->excluded                   = $isExcluded;
        $this->joinColumns                = $joinColumns;
        $this->valueProcessor             = $valueProcessor;
        $this->sortOrder                  = $sortOrder;
    }

    public function toArray(): array
    {
        return filter([
            'formName'       => $this->formName,
            'name'           => $this->name,
            'value'          => $this->value,
            'valueType'      => $this->valueType,
            'label'          => $this->label,
            'options'        => $this->options,
            'inputType'      => $this->inputType,
            'groupId'        => $this->groupId,
            'template'       => $this->template,
            'isEnabled'      => $this->enabled,
            'isExcluded'     => $this->excluded,
            'joinColumns'    => $this->joinColumns,
            'valueProcessor' => $this->valueProcessor,
            'sortOrder'      => $this->sortOrder,
        ]);
    }

    public function merge(FormFieldDefinitionInterface $field): FormFieldDefinitionInterface
    {
        return $this->formFieldDefinitionFactory->create(merge($this->toArray(), $field->toArray()));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucwords(str_replace('_', ' ', $this->getName()));
    }

    public function getValue()
    {
        return $this->valueProcessor
            ? $this->getValueProcessor()->toFieldValue($this->value)
            : $this->value;
    }

    public function getHtml(): string
    {
        return $this->renderTemplate($this->determineFieldContainerTemplate());
    }

    public function getContentHtml(): string
    {
        return $this->renderTemplate($this->determineFieldContentTemplate());
    }

    private function renderTemplate(string $template): string
    {
        $block = $this->layout->createBlock(Template::class);
        $block->setTemplate($template);
        $block->assign('field', $this);

        return $block->toHtml();
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGroupId(): string
    {
        return $this->groupId ?? FormGroupInterface::DEFAULT_GROUP_ID;
    }

    public function getInputType(): string
    {
        return $this->inputType ?? $this->determineInputType();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    private function determineFieldContainerTemplate(): string
    {
        return $this->joinColumns
            ? 'Hyva_Admin::form/field/one-col.phtml'
            : 'Hyva_Admin::form/field/two-col.phtml';
    }

    private function determineFieldContentTemplate(): string
    {
        return $this->template ?? 'Hyva_Admin::form/field/input/' . $this->getInputType() . '.phtml';
    }

    private function getValueProcessor(): FormFieldValueProcessorInterface
    {
        return $this->fieldValueProcessorFactory->get($this->name, $this->valueProcessor);
    }

    private function determineInputType(): string
    {
        if (in_array($this->valueType, ['string', 'float', 'decimal'], true)) {
            return 'text';
        }
        if ($this->valueType === 'text') {
            return 'textarea';
        }
        if ($this->valueType === 'array') {
            return 'select';
        }
        if ($this->valueType === 'bool') {
            return 'bool';
        }
        if ($this->valueType === 'int') {
            return 'number';
        }
        return 'text';
    }
}
