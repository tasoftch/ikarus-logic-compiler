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
use Ikarus\Logic\Compiler\Exception\InvalidNodeRecursionReferenceException;
use Ikarus\Logic\Compiler\Exception\InvalidNodeReferenceException;
use Ikarus\Logic\Compiler\Exception\InvalidSocketReferenceException;
use Ikarus\Logic\Compiler\Exception\InvalidSocketTypesReferenceException;
use Ikarus\Logic\Component\SceneGatewayComponent;
use Ikarus\Logic\Model\Component\ComponentInterface;
use Ikarus\Logic\Model\Component\NodeComponentInterface;
use Ikarus\Logic\Model\Component\Socket\SocketComponentInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Data\Node\NodeDataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;

class ConnectionConsistencyCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_CONNECTIONS = 'RESULT_ATTRIBUTE_CONNECTIONS';

    protected function getNode2ComponentCompiler(): NodeComponentMappingCompiler {
        return new NodeComponentMappingCompiler($this->getComponentModel());
    }

    protected function getGatewayCompiler(): GatewayConsistencyCompiler {
        return new GatewayConsistencyCompiler( $this->getComponentModel() );
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
                }

                $gateways = $result->getAttribute( GatewayConsistencyCompiler::RESULT_ATTRIBUTE_GATEWAYS );
                if(NULL === $gateways) {
                    $this->getGatewayCompiler()->compile($dataModel, $result);
                    $gateways = $result->getAttribute( GatewayConsistencyCompiler::RESULT_ATTRIBUTE_GATEWAYS );
                }

                $nodes = $result->getAttribute( NodeComponentMappingCompiler::RESULT_ATTRIBUTE_NODES );
                $connections = [];

                foreach($dataModel->getSceneDataModels() as $sceneDataModel) {
                    foreach($dataModel->getConnectionsInScene($sceneDataModel) as $connectionDataModel) {
                        /** @var NodeDataModelInterface $inputNode */
                        if($inputNode = $nodes[ $connectionDataModel->getInputNodeIdentifier() ] ?? NULL) {

                            $findSocket = function($name, $sockets, NodeDataModelInterface $node, ComponentInterface $component) use ($gateways) {
                                /** @var SocketComponentInterface $socket */
                                $gateway = false;
                                foreach($sockets as $socket) {
                                    if($socket->getName() == $name)
                                        return $socket;
                                }
                                // Internal gateway resolution
                                if($component instanceof SceneGatewayComponent) {
                                    return $component->getDynamicSocket($name, $node, $gateways);
                                }

                                return NULL;
                            };

                            $component = $components[ $inputNode->getComponentName() ];
                            $go = $gi = NULL;

                            $inputSocketComponent = $findSocket( $connectionDataModel->getInputSocketName(), $component->getInputSockets(), $inputNode, $component);
                            if(!$inputSocketComponent) {
                                $e = new InvalidSocketReferenceException("Input socket connection %s of node %s is not declared", InvalidSocketReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getInputSocketName(), $component->getName());
                                $e->setProperty($connectionDataModel);
                                throw $e;
                            }

                            /** @var NodeDataModelInterface $outputNode */
                            if($outputNode = $nodes[ $connectionDataModel->getOutputNodeIdentifier() ] ?? NULL) {
                                if($inputNode === $outputNode) {
                                    $e = new InvalidNodeRecursionReferenceException("Connection on same node is not allowed", InvalidNodeRecursionReferenceException::CODE_INVALID_PLACEMENT);
                                    $e->setProperty($outputNode);
                                    throw $e;
                                }

                                $component = $components[ $outputNode->getComponentName() ];
                                $outputSocketComponent = $findSocket( $connectionDataModel->getOutputSocketName(), $component->getOutputSockets(), $outputNode, $component);
                                if(!$outputSocketComponent) {
                                    $e = new InvalidSocketReferenceException("Output socket connection %s of node %s is not declared", InvalidSocketReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getOutputSocketName(), $component->getName());
                                    $e->setProperty($connectionDataModel);
                                    throw $e;
                                }

                                $inputType = $this->getComponentModel()->getSocketType( $inputSocketComponent->getSocketType() );
                                $outputType = $this->getComponentModel()->getSocketType( $outputSocketComponent->getSocketType() );

                                if(!$inputType->accepts($outputType)) {
                                    $e = new InvalidSocketTypesReferenceException("Input %s (%s) does not allow values of type %s", InvalidSocketTypesReferenceException::CODE_INVALID_PLACEMENT, NULL, $inputSocketComponent->getName(), $inputType->getName(), $outputType->getName());
                                    $e->setInputSocketType($inputType);
                                    $e->setOutputSocketType($outputType);
                                    $e->setProperty($inputNode);
                                    throw $e;
                                }

                                if($gi||$go) {
                                    $gw = "-";
                                    if($go)
                                        $gw = '+';

                                    $connections[] = [
                                        $gi?:$inputNode,
                                        $inputSocketComponent,
                                        $go?:$outputNode,
                                        $outputSocketComponent,
                                        'gw' => $gw
                                    ];
                                } else {
                                    $connections[] = [
                                        $inputNode,
                                        $inputSocketComponent,
                                        $outputNode,
                                        $outputSocketComponent
                                    ];
                                }
                            } else {
                                $e = new InvalidNodeReferenceException("Connection output node %s does not exist", InvalidNodeReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getOutputNodeIdentifier());
                                $e->setProperty($connectionDataModel);
                                throw $e;
                            }

                        } else {
                            $e = new InvalidNodeReferenceException("Connection input node %s does not exist", InvalidNodeReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getInputNodeIdentifier());
                            $e->setProperty($connectionDataModel);
                            throw $e;
                        }
                    }
                }

                $result->addAttribute(self::RESULT_ATTRIBUTE_CONNECTIONS, $connections);
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }
}