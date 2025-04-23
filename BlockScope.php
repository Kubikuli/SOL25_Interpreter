<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief BlockScope class definition
 */

namespace IPP\Student;

use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\MessageDNUException;
use IPP\Student\Exception\UnexpectedXMLFormatException;
use IPP\Student\Exception\UsingUndefinedException;

/**
 * BlockScope class for processing assignments inside block
 */
class BlockScope
{
    /**
     * @var array<string, ClassInstance> List of variables of current block scope, associated by name
     */
    private array $variables = [];

    // Return value of the block/method (last executed assignment)
    private ClassInstance $return_value;

    /**
     * Constructor with default return value for empty block
     */
    public function __construct()
    {
        $this->return_value = ClassInstance::getInstance("Nil");
    }

    /**
     * Sets return value for this block
     *
     * @param ClassInstance $value The value to be returned
     */
    public function setReturnValue(ClassInstance $value): void
    {
        $this->return_value = $value;
    }

    /**
     * Returns current return value of the block
     *
     * @return ClassInstance
     */
    public function getReturnValue(): ClassInstance
    {
        return $this->return_value;
    }

    /**
     * Sets variable with value in the current scope
     *
     * @param string $name Variable name
     * @param ClassInstance $value Value of variable
     */
    public function setVariable(string $name, ClassInstance $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Returns value of given variable from current scope
     *
     * @param string $name Variable name
     * @return ClassInstance
     */
    public function getVariable(string $name): ClassInstance
    {
        if (!isset($this->variables[$name])) {
            // If given variable doesn't exist
            throw new UsingUndefinedException("Using undefined variable: " . $name);
        }
        return $this->variables[$name];
    }

    // ************************** BLOCK PROCESSING ***************************
    /**
     * Assigns arguments to parameters of the current block scope and calls processAssignments()
     *
     * @param \DOMElement $block_node Block node to be processed
     * @param array<ClassInstance> $args Arguments given to the block
     */
    public function processBlock(?\DOMElement $block_node, array $args): void
    {
        if ($block_node === null) {
            throw new UnexpectedXMLFormatException("Malformed XML format.");
        }

        $num_of_args = count($args);
        $args_found = 0;

        // ********** PARAMETER PROCESSING ***********
        // Assign argument values to parameters
        foreach ($block_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                // Check if it's a parameter
                if ($child_node->tagName === "parameter") {
                    // Get parameter attributes
                    $order = (int)$child_node->getAttribute("order");
                    $par_name = $child_node->getAttribute("name");

                    // Check for correct number of parameters
                    if ($order > $num_of_args) {
                        throw new MessageDNUException("Incorrect amount of parameters.");
                    }

                    // Assign it to the block as variable
                    $this->setVariable($par_name, $args[$order - 1]);
                    $args_found++;
                }
            }
        }

        // Check for correct number of parameters
        if ($args_found !== $num_of_args) {
            throw new MessageDNUException("Incorrect amount of parameters.");
        }

        $this->processAssignments($block_node);
    }

    // ************************* ASSIGN PROCESSING ***************************
    /**
     * Processes all assignments in the block
     *
     * @param \DOMElement $block_node Block to be processed
     */
    private function processAssignments(\DOMElement $block_node): void
    {
        $order = 0;
        $new_order = 1;
        // Makes sure the assignments are processed in the correct order
        while ($order !== $new_order) {
            $order = $new_order;
            // For each child node
            foreach ($block_node->childNodes as $child_node) {
                if ($child_node instanceof \DOMElement) {
                    // Check if it's an assignment
                    if ($child_node->tagName === "assign") {
                        // Make sure the order is correct
                        $assign_order = (int)$child_node->getAttribute("order");
                        if ($assign_order === $order) {
                            $new_order++;

                            // ************ ASSIGNMENT PROCESSING ************
                            $result = null;
                            $var_name = null;

                            foreach ($child_node->childNodes as $grand_child_node) {
                                if ($grand_child_node instanceof \DOMElement) {
                                    // Get variable name
                                    if ($grand_child_node->tagName === "var") {
                                        $var_name = $grand_child_node->getAttribute("name");

                                        if ($var_name === "") {
                                            throw new UnexpectedXMLFormatException("Malformed XML. Missing name");
                                        }
                                    } elseif ($grand_child_node->tagName === "expr") {
                                        // Get value to be assigned
                                        $evaluator = new ExpressionEvaluator($this);
                                        $result = $evaluator->evaluateExpr($grand_child_node);
                                    }
                                }
                            }

                            // Safety checks
                            if (is_string($result) || $result === null || $var_name === null) {
                                // This should never reach, only with malformed XML
                                throw new InterpretException("Malformed XML.");
                            }

                            // Assign value to the variable
                            $this->setVariable($var_name, $result);
                            $this->setReturnValue($result);
                        }
                    }
                }
            }
        }
    }
}
