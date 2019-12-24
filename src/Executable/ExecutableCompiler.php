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

namespace Ikarus\Logic\Compiler\Executable;


use Ikarus\Logic\Compiler\AbstractCompiler;
use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Consistency\ConnectionConsistencyCompiler;
use Ikarus\Logic\Compiler\Consistency\SocketComponentMappingCompiler;
use Ikarus\Logic\Compiler\Exception\DuplicateSocketReferenceException;
use Ikarus\Logic\Compiler\Exception\MissingSignalExecutableException;
use Ikarus\Logic\Model\Component\Socket\InputSocketComponentInterface;
use Ikarus\Logic\Model\Component\Socket\OutputSocketComponentInterface;
use Ikarus\Logic\Model\Component\Socket\Type\SignalTypeInterface;
use Ikarus\Logic\Model\Component\Socket\Type\TypeInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Data\Node\AttributedNodeDataModel;
use Ikarus\Logic\Model\Data\Node\NodeDataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;
use Ikarus\Logic\Model\Executable\ExecutableExpressionNodeComponentInterface;
use Ikarus\Logic\Model\Executable\ExecutableSignalTriggerNodeComponentInterface;

class ExecutableCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_EXECUTABLE = 'RESULT_ATTRIBUTE_EXECUTABLE';

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                $connections = $result->getAttribute( ConnectionConsistencyCompiler::RESULT_ATTRIBUTE_CONNECTIONS );
                /** @var TypeInterface[] $types */
                $types = $result->getAttribute( SocketComponentMappingCompiler::RESULT_ATTRIBUTE_TYPES );

                $exec = ['i2o' => [], 'o2i' => []];

                if(NULL !== $connections) {
                    foreach($connections as $connection) {
                        /**
                         * @var NodeDataModelInterface $inputNode
                         * @var InputSocketComponentInterface $inputSocketComponent
                         * @var NodeDataModelInterface $outputNode
                         * @var OutputSocketComponentInterface $outputSocketComponent
                         */
                        list($inputNode, $inputSocketComponent, $outputNode, $outputSocketComponent) = $connection;
                        $inputType = $types[ $inputSocketComponent->getSocketType() ];



                        if($inputType instanceof SignalTypeInterface) {
                            // Is signal socket
                            $nodeData = [
                                'dn' => $inputNode->getIdentifier(),
                                'dc' => $dc = $this->getComponentModel()->getComponent( $inputNode->getComponentName() ),
                                'dk' => $inputSocketComponent->getName()
                            ];

                            if(!($dc instanceof ExecutableSignalTriggerNodeComponentInterface)) {
                                $e = new MissingSignalExecutableException("Node component %s has a connection to signal socket %s but does not implement ExecutableSignalTriggerNodeComponentInterface",
                                    MissingSignalExecutableException::CODE_SYMBOL_NOT_FOUND, NULL, $dc->getName(), $inputSocketComponent->getName());
                                $e->setProperty($inputNode);
                                throw $e;
                            }

                            if($inputNode instanceof AttributedNodeDataModel)
                                $nodeData["a"] = $inputNode->getAttributes();

                            $k = sprintf( "%s:%s", $outputNode->getIdentifier(), $outputSocketComponent->getName() );
                            $q = 'o2i';

                            if($outputSocketComponent->allowsMultiple() == false && count( $exec[$q][$k] ?? [] ) > 0) {
                                $e = new DuplicateSocketReferenceException("Output socket %s of component %s does not allow multiple connections", DuplicateSocketReferenceException::CODE_INVALID_PLACEMENT, NULL,
                                    $outputSocketComponent->getName(),
                                    $outputNode->getComponentName()
                                    );
                                $e->setProperty($outputSocketComponent);
                                throw $e;
                            }
                        } else {
                            // Is expression socket
                            $nodeData = [
                                'dn' => $outputNode->getIdentifier(),
                                'dc' => $dc = $this->getComponentModel()->getComponent( $outputNode->getComponentName() ),
                                'dk' => $outputSocketComponent->getName()
                            ];

                            if(!($dc instanceof ExecutableSignalTriggerNodeComponentInterface) && !($dc instanceof ExecutableExpressionNodeComponentInterface)) {
                                $e = new MissingSignalExecutableException("Node component %s has a connection from socket %s but does not implement ExecutableSignalTriggerNodeComponentInterface or ExecutableExpressionNodeComponentInterface",
                                    MissingSignalExecutableException::CODE_SYMBOL_NOT_FOUND, NULL, $dc->getName(), $outputSocketComponent->getName());
                                $e->setProperty($inputNode);
                                throw $e;
                            }

                            if($outputNode instanceof AttributedNodeDataModel)
                                $nodeData["a"] = $outputNode->getAttributes();

                            $k = sprintf( "%s:%s", $inputNode->getIdentifier(), $inputSocketComponent->getName() );
                            $q = 'i2o';

                            if($inputSocketComponent->allowsMultiple() == false && count( $exec[$q][$k] ?? [] ) > 0) {
                                $e = new DuplicateSocketReferenceException("Input socket %s of component %s does not allow multiple connections", DuplicateSocketReferenceException::CODE_INVALID_PLACEMENT, NULL,
                                    $inputSocketComponent->getName(),
                                    $inputNode->getComponentName()
                                );
                                $e->setProperty($inputSocketComponent);
                                throw $e;
                            }
                        }

                        $exec[$q][$k][] = $nodeData;
                    }
                }

                $result->addAttribute( static::RESULT_ATTRIBUTE_EXECUTABLE, $exec );
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }
}