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

namespace Ikarus\Logic\Compiler\Storage;


use Ikarus\Logic\Compiler\AbstractCompiler;
use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Executable\ExecutableCompiler;
use Ikarus\Logic\Compiler\Executable\ExposedSocketsCompiler;
use Ikarus\Logic\Model\Component\ComponentModelInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;
use Ikarus\Logic\Model\Exception\LogicException;

class InMemoryStorageCompiler extends AbstractCompiler
{
    private $memory;

    public function __construct(ComponentModelInterface $componentModel)
    {
        parent::__construct($componentModel);
    }

    public function setMemory(&$memory) {
        $this->memory = &$memory;
    }

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                $exec = $result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE );
                if(!$exec) {
                    throw new LogicException("No executable found", LogicException::CODE_SYMBOL_NOT_FOUND);
                }

                $exposed = $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS );

                $this->memory = ['x' => $exposed, 'X' => $exec];
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }
}