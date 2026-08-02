<?php

declare(strict_types=1);

namespace App\Doctrine\DqlFunction;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "REGEXP" "(" ArithmeticPrimary "," ArithmeticPrimary ")".
 */
final class RegexpFunction extends FunctionNode
{
    public Node $subject;

    public Node $pattern;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '%s REGEXP %s',
            $this->subject->dispatch($sqlWalker),
            $this->pattern->dispatch($sqlWalker),
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->subject = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->pattern = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
