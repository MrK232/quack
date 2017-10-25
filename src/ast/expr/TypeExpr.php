<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2016 Marcelo Camargo <marcelocamargo@linuxmail.org> and
 * CONTRIBUTORS.
 *
 * This file is part of Quack.
 *
 * Quack is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Quack is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Quack.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace QuackCompiler\Ast\Expr;

use \QuackCompiler\Intl\Localization;
use \QuackCompiler\Parser\Parser;
use \QuackCompiler\Scope\Kind;
use \QuackCompiler\Scope\Meta;
use \QuackCompiler\Types\TypeError;

class TypeExpr extends Expr
{
    private $name;
    private $values;

    public function __construct($name, $values)
    {
        $this->name = $name;
        $this->values = $values;
    }

    public function format(Parser $parser)
    {
        $source = $this->name;
        if (count($this->values) > 0) {
            $source .= '(';
            $source .= implode(', ', array_map(function ($expr) use ($parser) {
                return $expr->format($parser);
            }, $this->values));
            $source .= ')';
        }

        return $source;
    }

    public function injectScope($scope)
    {
        $this->scope = $scope;
        foreach ($this->values as $value) {
            $value->injectScope($scope);
        }
    }

    public function getType()
    {
        $myself = $this->scope->lookup($this->name);

        // Ensure the type is declared
        if (null === $myself) {
            throw new TypeError(Localization::message('TYP120', [$this->name]));
        }

        // Ensure the type is member of a tagged union
        if (~$myself & Kind::K_UNION_MEMBER) {
            throw new TypeError(Localization::message('TYP200', [$this->name]));
        }

        // Find the tagged union for what this type belongs
        $tagged_union = $this->scope->getMeta(Meta::M_PARENT, $this->name);

        // TODO: Check if the parameters match the type constructor

        return $tagged_union;
    }
}
