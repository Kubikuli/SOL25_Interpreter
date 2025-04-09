<?php

namespace IPP\Student;


// Each instance represents instance of user defined class
class Class_instance
{
    protected string $class_name;   // class type of the instance
    protected mixed $value;       // intern value attribute used only by built-in classes
    protected array $attributes;     // attributes of the instance

    // Constructor, sets class and initializes attributes
    public function __construct(string $class)
    {
        $this->class_name = $class;
        $this->attributes = [];
    }

    // Sets new attribute to the instance
    public function set_attribute(string $name, Class_instance $value): void
    {
        $this->attributes[$name] = $value;
    }

    // Gets attribute value of the instance
    public function get_attribute(string $name): ?Class_instance
    {
        return $this->attributes[$name] ?? null;
    }

    public function set_value(mixed $value): void
    {
        $this->value = $value;
    }

    public function get_value(): mixed
    {
        return $this->value ?? null;
    }

    public function get_class_name(): string
    {
        return $this->class_name;
    }

    public function is_instance_of(string $possible_parent): bool
    {
        if ($this->class_name === $possible_parent){
            return true;
        }

        $possible_parent_instance = Class_definition::get_class($possible_parent);
        $class = Class_definition::get_class($this->class_name);

        while(true){
            $parent_name = $class->get_parent_name();

            if ($parent_name === "Object" || $parent_name === ""){
                break;
            }

            $parent_instance = Class_definition::get_class($parent_name);

            if ($parent_instance === null){
                break;
            }

            if ($parent_instance === $possible_parent_instance){
                return true;
            }
        }

        return false;
    }

    public function copy_values(Class_instance $instance): void
    {
        $instance->set_value($this->value);

        foreach ($this->attributes as $key => $value) {
            $instance->attributes[$key] = $value;
        }
    }
}
