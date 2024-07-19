<?php

namespace Mhe\Imagetools\Calculations;

/*
 * Helper class to evaluate simple mathematical formulas, to interpret CSS calc()
 * see {@link https://github.com/louisfisch/mathematical-expressions-parser/blob/master/parser.py}
 */
class MathParser
{
    protected $string;
    protected $index;

    /**
     * @param string $string formula containing numbers, simple operators and parenthesis
     * @return float|int|null
     */
    public function calculate($string)
    {
        $this->string = preg_replace('/\s/', '', $string);
        $this->index = 0;
        $value = $this->parseExpression();
        if ($this->hasNext()) {
            // error
            return null;
        }
        return $value;
    }

    protected function parseExpression()
    {
        return $this->parseAddition();
    }

    protected function parseAddition()
    {
        $values = [$this->parseMultiplication()];

        while (true) {
            $char = $this->peek();

            if ($char == '+') {
                $this->index++;
                $values[] = $this->parseMultiplication();
            } elseif ($char == '-') {
                $this->index++;
                $values[] = -1 * $this->parseMultiplication();
            } else {
                break;
            }
        }
        return array_sum($values);
    }

    protected function parseMultiplication()
    {
        $values = [$this->parseParenthesis()];

        while (true) {
            $char = $this->peek();
            if ($char == '*') {
                $this->index++;
                $values[] = $this->parseParenthesis();
            } elseif ($char == '/') {
                $div_index = $this->index;
                $this->index++;
                $den = $this->parseParenthesis();
                if ($den == 0) {
                    throw new \Exception('Division by zero at ' . $div_index);
                }
                $values[] =  1.0 / $den;
            } else {
                break;
            }
        }

        $value = 1.0;
        foreach ($values as $factor) {
            $value *= $factor;
        }
        return $value;
    }

    protected function parseParenthesis()
    {
        $char = $this->peek();
        if ($char == '(') {
            $this->index++;
            $value = $this->parseExpression();
            if ($this->peek() != ')') {
                throw new \Exception('No closing parenthesis found at character ' . $this->index);
            }
            $this->index++;
            return $value;
        } else {
            return $this->parseNegative();
        }
    }

    protected function parseNegative()
    {
        $char = $this->peek();
        if ($char == '-') {
            $this->index++;
            return -1 * $this->parseParenthesis();
        } else {
            return $this->parseValue();
        }
    }

    protected function parseValue()
    {
        $char = $this->peek();

        if (strpos('0123456789.', $char) !== false) {
            return $this->parseNumber();
        }
        throw new \Exception('Not a valid expression');
    }

    protected function parseNumber()
    {
        $strValue = '';
        $decimal_found = false;
        while ($this->hasNext()) {
            $char = $this->peek();
            if ($char == '.') {
                if ($decimal_found) {
                    throw new \Exception('Found an extra period in a number');
                }
                $decimal_found = true;
                $strValue .= '.';
            } elseif (strpos('0123456789.', $char) !== false) {
                $strValue .= $char;
            } else {
                break;
            }
            $this->index++;
        }
        if (strlen($strValue) == 0) {
            throw new \Exception('Unexpected end / invalid number');
        }
        return (double) $strValue;
    }

    protected function peek()
    {
        return substr($this->string, $this->index, 1);
    }

    protected function hasNext()
    {
        return $this->index < strlen($this->string);
    }
}
