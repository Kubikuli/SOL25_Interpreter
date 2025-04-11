<?php

namespace IPP\Student;

use IPP\Student\Exception\UsingUndefinedException;

// Each instance represents instance of user defined class
class ClassInstance
{
    protected string $class_name;   // class type of the instance
    protected int|float|string|\DOMElement|bool|null $value;       // intern value attribute used only by some built-in classes
    /**
     * @var array<string, ClassInstance> List of attributes of the instance
     */
    protected array $attributes;

    // Constructor, sets class and initializes attributes
    public function __construct(string $class)
    {
        $this->class_name = $class;
        $this->attributes = [];
    }

    // Sets new attribute to the instance
    public function setAttribute(string $name, ClassInstance $value): void
    {
        $this->attributes[$name] = $value;
    }

    // Gets attribute value of the instance
    public function getAttribute(string $name): ?ClassInstance
    {
        return $this->attributes[$name] ?? null;
    }

    public function setValue(int|float|string|\DOMElement|bool|null $value): void
    {
        $this->value = $value;
    }

    public function getValue(): int|float|string|\DOMElement|bool|null
    {
        return $this->value ?? null;
    }

    public function getClassName(): string
    {
        return $this->class_name;
    }

    public function isInstanceOf(string $possible_parent): bool
    {
        if ($this->class_name === $possible_parent){
            return true;
        }

        $possible_parent_instance = ClassDefinition::getClass($possible_parent);
        $class = ClassDefinition::getClass($this->class_name);

        while(true){
            $parent_name = $class->getParentName();

            if ($parent_name === "Object" || $parent_name === ""){
                break;
            }

            $parent_instance = ClassDefinition::getClass($parent_name);

            if ($parent_instance === $possible_parent_instance){
                return true;
            }
        }

        return false;
    }

    public function copyValues(ClassInstance $instance): void
    {
        $instance->setValue($this->value);

        foreach ($this->attributes as $key => $value) {
            $instance->attributes[$key] = $value;
        }
    }
}
