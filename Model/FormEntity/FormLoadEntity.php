<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormEntity;

use Hyva\Admin\Model\TypeReflection\ArrayValueExtractor;
use Hyva\Admin\Model\TypeReflection\CustomAttributesExtractor;
use Hyva\Admin\Model\TypeReflection\EavAttributeGroups;
use Hyva\Admin\Model\TypeReflection\ExtensionAttributeTypeExtractor;
use Hyva\Admin\Model\TypeReflection\GetterMethodsExtractor;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

use function array_combine as zip;
use function array_map as map;
use function array_merge as merge;

class FormLoadEntity
{
    private $value;

    /**
     * @var string
     */
    private $valueType;

    /**
     * @var array
     */
    private $customAttributes;

    /**
     * @var array
     */
    private $extensionAttributes;

    /**
     * @var array
     */
    private $getterMethodAttributes;

    /**
     * @var array
     */
    private $arrayKeyAttributes;

    /**
     * @var CustomAttributesExtractor
     */
    private $customAttributesExtractor;

    /**
     * @var ExtensionAttributeTypeExtractor
     */
    private $extensionAttributeTypeExtractor;

    /**
     * @var GetterMethodsExtractor
     */
    private $getterMethodsExtractor;

    /**
     * @var ArrayValueExtractor
     */
    private $arrayValueExtractor;

    /**
     * @var EavAttributeGroups
     */
    private $eavAttributeGroups;

    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    public function __construct(
        $value,
        string $valueType,
        CustomAttributesExtractor $customAttributesExtractor,
        ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor,
        GetterMethodsExtractor $getterMethodsExtractor,
        ArrayValueExtractor $arrayValueExtractor,
        EavAttributeGroups $eavAttributeGroups,
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory
    ) {
        $this->value                           = $value;
        $this->valueType                       = $valueType;
        $this->customAttributesExtractor       = $customAttributesExtractor;
        $this->extensionAttributeTypeExtractor = $extensionAttributeTypeExtractor;
        $this->getterMethodsExtractor          = $getterMethodsExtractor;
        $this->arrayValueExtractor             = $arrayValueExtractor;
        $this->eavAttributeGroups              = $eavAttributeGroups;
        $this->formFieldDefinitionFactory      = $formFieldDefinitionFactory;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return FormFieldDefinitionInterface[]
     */
    public function getFieldDefinitions(string $formName): array
    {
        $this->initFields();
        $fieldCodes = $this->getFieldCodes();
        return zip($fieldCodes, map(function (string $code) use ($formName): FormFieldDefinitionInterface {
            return $this->buildFieldDefinitionForAttribute($formName, $code);
        }, $fieldCodes));
    }

    private function buildFieldDefinitionForAttribute(string $formName, string $code): FormFieldDefinitionInterface
    {
        return $this->formFieldDefinitionFactory->create([
            'formName'   => $formName,
            'name'       => $code,
            'value'      => $this->getFieldValue($code),
            'valueType'  => $this->valueType,
            'label'      => $this->getFieldLabel($code),
            'options'    => $this->getFieldOptions($code),
            'inputType'  => $this->getFieldInputType($code),
            'groupId'    => $this->getFieldGroup($code),
            'isExcluded' => false,
            'isEnabled'  => true,
            //'sortOrder'      => null,
            //'valueProcessor' => null,
            'template'   => null,
        ]);
    }

    private function getFieldCodes(): array
    {
        return merge(
            $this->customAttributes,
            $this->extensionAttributes,
            $this->getterMethodAttributes,
            $this->arrayKeyAttributes
        );
    }

    private function getExtensionAttributesAsFieldNames(): array
    {
        $extensionAttributesType = $this->extensionAttributeTypeExtractor->forType($this->valueType);
        return $extensionAttributesType
            ? $this->getterMethodsExtractor->fromTypeAsFieldNames($extensionAttributesType)
            : [];
    }

    private function getFieldOptions(string $code): ?array
    {
        return $this->isCustomAttribute($code)
            ? $this->customAttributesExtractor->getAttributeOptions($this->valueType, $code)
            : null;
    }

    private function getFieldInputType(string $code): ?string
    {
        return $this->isCustomAttribute($code)
            ? $this->customAttributesExtractor->getAttributeInputType($this->valueType, $code)
            : null;
    }

    private function getFieldGroup(string $code): ?string
    {
        return $this->isCustomAttribute($code)
            ? $this->customAttributesExtractor->getAttributeGroup($this->value, $code)
            : null;
    }

    private function getFieldLabel(string $code): ?string
    {
        return $this->isCustomAttribute($code)
            ? $this->customAttributesExtractor->getAttributeLabel($this->valueType, $code)
            : null;
    }

    private function initFields(): void
    {
        if (!isset($this->customAttributes)) {
            $this->customAttributes = $this->customAttributesExtractor->attributesForInstanceAsFieldNames(
                $this->value,
                $this->valueType
            );
        }
        if (!isset($this->extensionAttributes)) {
            $this->extensionAttributes = $this->getExtensionAttributesAsFieldNames();
        }
        if (!isset($this->getterMethodAttributes)) {
            $this->getterMethodAttributes = $this->getterMethodsExtractor->fromTypeAsFieldNames($this->valueType);
        }
        if (!isset($this->arrayKeyAttributes)) {
            $this->arrayKeyAttributes = is_array($this->value)
                ? $this->arrayValueExtractor->forArray($this->value)
                : [];
        }
    }

    private function isCustomAttribute(string $code): bool
    {
        return in_array($code, $this->customAttributes, true);
    }

    /**
     * @param string $code
     * @return mixed
     */
    private function getFieldValue(string $code)
    {
        if (in_array($code, $this->customAttributes, true)) {
            return $this->customAttributesExtractor->getValue($this->value, $code);
        }
        if (in_array($code, $this->extensionAttributes, true)) {
            return $this->extensionAttributeTypeExtractor->getValue($this->valueType, $code, $this->value);
        }
        if (in_array($code, $this->getterMethodAttributes, true)) {
            return $this->getterMethodsExtractor->getValue($this->value, $code);
        }
        if (in_array($code, $this->arrayKeyAttributes, true)) {
            return $this->arrayValueExtractor->getValue($this->value, $code);
        }
    }
}
