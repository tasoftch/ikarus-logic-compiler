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
use Ikarus\Logic\Component\SceneGatewayComponent;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentComponentModelException;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;
use Ikarus\Logic\Model\Exception\InvalidReferenceException;

class GatewayConsistencyCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_GATEWAYS = 'gateways';

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                $gateways = [];

                foreach($dataModel->getSceneDataModels() as $sceneDataModel) {
                    if($gws = $dataModel->getGatewaysToScene( $sceneDataModel )) {
                        foreach($gws as $gw) {
                            $component = $this->getComponentModel()->getComponent( $gw->getSourceNode()->getComponentName() );
                            if(!$component instanceof SceneGatewayComponent) {
                                $e = new InconsistentComponentModelException("Component %s must be instance of %s", InconsistentComponentModelException::CODE_INVALID_INSTANCE, NULL, $component->getName(), SceneGatewayComponent::class);
                                $e->setProperty($component);
                                $e->setModel($this->getComponentModel());
                                throw $e;
                            }

                            $map = $gw->getSocketMap();
                            foreach($map as $from => $to) {
                                list($nodeID, $socketName) = explode(".", $to);
                                $node = $dataModel->getNodesInScene( $gw->getDestinationScene() )[ $nodeID ] ?? NULL;
                                if(!$node) {
                                    $e = new InvalidReferenceException("Node $nodeID not found", InvalidReferenceException::CODE_SYMBOL_NOT_FOUND);
                                    $e->setProperty($map);
                                    throw $e;
                                }

                                $component = $this->getComponentModel()->getComponent( $node->getComponentName() );
                                $socket = NULL;
                                if($s = $component->getInputSockets()[$socketName] ?? NULL)
                                    $socket = $s;
                                elseif($s = $component->getOutputSockets()[$socketName] ?? NULL)
                                    $socket = $s;
                                else {
                                    $e = new InvalidReferenceException("Socket $socketName of node $nodeID not found", InvalidReferenceException::CODE_SYMBOL_NOT_FOUND);
                                    $e->setProperty($map);
                                    throw $e;
                                }

                                $gateways[ $gw->getSourceNode()->getIdentifier() ][$from] = [$socket, $node];
                            }
                        }
                    }
                }

                $result->addAttribute(static::RESULT_ATTRIBUTE_GATEWAYS, $gateways);
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }
}