<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief ClassInstance class definition
 */

namespace IPP\Student;

/**
 * Each ClassInstance instance represents instance of a class while interpreting
 */
class ClassInstance
{
    // Class type of the instance
    private string $class_name;

    // Intern value attribute used only by some built-in classes
    private int|float|string|\DOMElement|bool|null $value;

    /**
     * @var array<string, ClassInstance> List of attributes of the instance
     */
    private array $attributes;

    /**
     * Constructor initializes attributes to default values
     */
    public function __construct(string $class)
    {
        $this->class_name = $class;
        $this->attributes = [];
    }

    /**
     * Sets attribute value of the instance
     *
     * @param string $name Name of the attribute
     * @param ClassInstance $value Value of the attribute
     */
    public function setAttribute(string $name, ClassInstance $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Gets attribute value of the instance
     *
     * @param string $name Name of the attribute
     * @return ClassInstance|null Value of the attribute or null if not set
     */
    public function getAttribute(string $name): ?ClassInstance
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Sets the internal value of the instance
     *
     * @param int|float|string|\DOMElement|bool|null $value Value to be set
     */
    public function setValue(int|float|string|\DOMElement|bool|null $value): void
    {
        $this->value = $value;
    }

    /**
     * Gets the internal value of the instance
     *
     * @return int|float|string|\DOMElement|bool|null Value of the instance or null if not set
     */
    public function getValue(): int|float|string|\DOMElement|bool|null
    {
        return $this->value ?? null;
    }

    /**
     * Gets the class name of the instance
     *
     * @return string Class name of the instance
     */
    public function getClassName(): string
    {
        return $this->class_name;
    }

    /**
     * Gets the class name of the instance
     *
     * @return string Class name of the instance
     */
    public function setClassName(string $class_name): void
    {
        $this->class_name = $class_name;
    }

    /**
     * Copies values from this instance to other instance given as an argument
     *
     * @param ClassInstance $instance Instance to which values will be copied
     */
    public function copyValues(ClassInstance $instance): void
    {
        $instance->setValue($this->value);

        // Copy all the attributes
        foreach ($this->attributes as $key => $value) {
            $instance->attributes[$key] = $value;
        }
    }

    /**
     * Checks if the given class is an instance of class given as argument
     *
     * @param string $possible_parent Name of the class to be checked as possible parent
     * @return bool True if the class is an instance of the given parent class, false otherwise
     */
    public function isInstanceOf(string $possible_parent): bool
    {
        // Same class name
        if ($this->class_name === $possible_parent) {
            return true;
        }

        // Get class definition instance of current class
        $class = ClassDefinition::getClass($this->class_name);

        while (true) {
            // Get parent name of current class
            $parent_name = $class->getParentName();

            // Check for match
            if ($parent_name === $possible_parent) {
                return true;
            }

            // Reached the end of inheritance hierarchy
            if ($parent_name === "Object" || $parent_name === "") {
                break;
            }

            // Get next class definition in the hierarchy
            $class = ClassDefinition::getClass($parent_name);
        }

        return false;
    }
}
