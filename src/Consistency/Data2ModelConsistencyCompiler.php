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
use Ikarus\Logic\Compiler\Exception\InvalidNodeReferenceException;
use Ikarus\Logic\Compiler\Exception\InvalidSocketReferenceException;
use Ikarus\Logic\Model\Component\NodeComponentInterface;
use Ikarus\Logic\Model\Component\Socket\SocketComponentInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Exception\ComponentNotFoundException;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;

/**
 * The Data2Model consistency compiler checks, if all data symbols are linkable with the corresponding component model.
 *
 * @package Ikarus\Logic\Compiler
 */
class Data2ModelConsistencyCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_COMPONENTS = 'RESULT_ATTRIBUTE_COMPONENTS';
    const RESULT_ATTRIBUTE_CONNECTIONS = 'RESULT_ATTRIBUTE_CONNECTIONS';

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        try {
            $this->bindComponentModel($dataModel, $result);
            $this->bindConnections($dataModel, $result);
        } catch (InconsistentDataModelException $exception) {
            $exception->setModel($dataModel);
            $result->setSuccess(false);
            throw $exception;
        }
    }

    private function bindComponentModel(DataModelInterface $dataModel, CompilerResult $result) {
        /** @var NodeComponentInterface[] $components */
        $components = [];

        foreach($dataModel->getSceneDataModels() as $sceneDataModel) {
            foreach($dataModel->getNodesInScene($sceneDataModel) as $nodeDataModel) {
                try {
                    if(!($components[$nodeDataModel->getComponentName()] ?? NULL)) {
                        $component = $this->getComponentModel()->getComponent( $nodeDataModel->getComponentName() );
                        $components[ $nodeDataModel->getComponentName() ] = $component;
                    }
                } catch (ComponentNotFoundException $exception) {
                    $e = new InvalidComponentReferenceException("Component %s does not exist", InvalidComponentReferenceException::CODE_SYMBOL_NOT_FOUND, $exception);
                    $e->setProperty($nodeDataModel);
                    throw $e;
                }
            }
        }

        $result->addAttribute(self::RESULT_ATTRIBUTE_COMPONENTS, $components);
    }

    private function bindConnections(DataModelInterface $dataModel, CompilerResult $result) {
        /** @var NodeComponentInterface[] $components */
        $components = $result->getAttribute( self::RESULT_ATTRIBUTE_COMPONENTS );

        $connections = [];

        foreach($dataModel->getSceneDataModels() as $sceneDataModel) {
            foreach($dataModel->getConnectionsInScene($sceneDataModel) as $connectionDataModel) {
                if($inputNode = $nodes[ $connectionDataModel->getInputNodeIdentifier() ] ?? NULL) {

                    $findSocket = function($name, $sockets) {
                        /** @var SocketComponentInterface $socket */
                        foreach($sockets as $socket) {
                            if($socket->getName() == $name)
                                return $socket;
                        }
                        return NULL;
                    };

                    $component = $components[ $inputNode->getComponentName() ];
                    $inputSocketComponent = $findSocket( $connectionDataModel->getInputSocketName(), $component->getInputSockets() );
                    if(!$inputSocketComponent) {
                        $e = new InvalidSocketReferenceException("Input socket connection %s of node %s is not declared", InvalidSocketReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getInputSocketName(), $component->getName());
                        $e->setProperty($connectionDataModel);
                        throw $e;
                    }


                    if($outputNode = $nodes[ $connectionDataModel->getOutputNodeIdentifier() ] ?? NULL) {

                        $component = $components[ $outputNode->getComponentName() ];
                        $outputSocketComponent = $findSocket( $connectionDataModel->getOutputSocketName(), $component->getOutputSockets() );
                        if(!$outputSocketComponent) {
                            $e = new InvalidSocketReferenceException("Output socket connection %s of node %s is not declared", InvalidSocketReferenceException::CODE_SYMBOL_NOT_FOUND, NULL, $connectionDataModel->getOutputSocketName(), $component->getName());
                            $e->setProperty($connectionDataModel);
                            throw $e;
                        }

                        $connections[] = [
                            $connectionDataModel,
                            $inputNode,
                            $inputSocketComponent,
                            $outputNode,
                            $outputSocketComponent
                        ];
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
    }
}