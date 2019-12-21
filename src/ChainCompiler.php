<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\Logic\Compiler;


use Ikarus\Logic\Model\Component\ComponentModelInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use TASoft\Collection\PriorityCollection;

class ChainCompiler implements CompilerInterface
{
    private $compilers;

    public function __construct(ComponentModelInterface $componentModel = NULL)
    {
        $this->compilers = new PriorityCollection();
    }

    /**
     * Adds a compiler to the chain
     *
     * @param CompilerInterface $compiler
     * @param int $priority
     */
    public function addCompiler(CompilerInterface $compiler, int $priority = 0) {
        $this->compilers->add($priority, $compiler);
    }

    /**
     * Removes a compiler from chain
     *
     * @param $compiler
     */
    public function removeCompiler($compiler) {
        $this->compilers->remove($compiler);
    }

    /**
     * Checks if a compiler is in chain
     *
     * @param $compiler
     * @return bool
     */
    public function hasCompiler($compiler) {
        return $this->compilers->contains($compiler);
    }

    /**
     * @inheritDoc
     */
    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        /** @var CompilerInterface $compiler */
        foreach($this->compilers->getOrderedElements() as $compiler) {
            $compiler->compile($dataModel, $result);
        }
    }
}