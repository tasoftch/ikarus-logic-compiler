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

namespace Ikarus\Logic\Compiler\Consistency;


use Ikarus\Logic\Compiler\AbstractCompiler;
use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Exception\InvalidComponentReferenceException;
use Ikarus\Logic\Model\Component\NodeComponentInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;
use Ikarus\Logic\Model\Exception\SocketComponentNotFoundException;

class SocketComponentMappingCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_SOCKETS = 'RESULT_ATTRIBUTE_SOCKETS';
    const RESULT_ATTRIBUTE_TYPES = 'RESULT_ATTRIBUTE_TYPES';


    protected function getNode2ComponentCompiler(): NodeComponentMappingCompiler {
        return new NodeComponentMappingCompiler($this->getComponentModel());
    }

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                /** @var NodeComponentInterface[] $components */
                $components = $result->getAttribute( NodeComponentMappingCompiler::RESULT_ATTRIBUTE_COMPONENTS );
                if(NULL === $components) {
                    $this->getNode2ComponentCompiler()->compile($dataModel, $result);
                    $components = $result->getAttribute( NodeComponentMappingCompiler::RESULT_ATTRIBUTE_COMPONENTS );
                    if(!$result->isSuccess())
                        return;
                }

                $sockets = [];
                $types = [];

                foreach($components as $component) {
                    foreach($component->getInputSockets() as $socket) {
                        $tn = $socket->getSocketType();
                        if(!isset($sockets["types"][$tn])) {
                            try {
                                $types[$tn] = $this->getComponentModel()->getSocketType($tn);
                                $sockets["inputs"][ sprintf("%s:%s", $component->getName(), $socket->getName()) ] = $socket;
                            } catch (SocketComponentNotFoundException $exception) {
                                $e = new InvalidComponentReferenceException("Socket type $tn does not exist", InvalidComponentReferenceException::CODE_SYMBOL_NOT_FOUND, $exception);
                                $e->setProperty($socket);
                                throw $e;
                            }
                        }
                    }

                    foreach($component->getOutputSockets() as $socket) {
                        $tn = $socket->getSocketType();
                        if(!isset($sockets["types"][$tn])) {
                            try {
                                $types[$tn] = $this->getComponentModel()->getSocketType($tn);
                                $sockets["outputs"][ sprintf("%s:%s", $component->getName(), $socket->getName()) ] = $socket;
                            } catch (SocketComponentNotFoundException $exception) {
                                $e = new InvalidComponentReferenceException("Socket type $tn does not exist", InvalidComponentReferenceException::CODE_SYMBOL_NOT_FOUND, $exception);
                                $e->setProperty($socket);
                                throw $e;
                            }
                        }
                    }
                }

                $result->addAttribute(self::RESULT_ATTRIBUTE_SOCKETS, $sockets);
                $result->addAttribute(self::RESULT_ATTRIBUTE_TYPES, $types);
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }

}