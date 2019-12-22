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
use Ikarus\Logic\Compiler\Consistency\NodeComponentMappingCompiler;
use Ikarus\Logic\Compiler\Consistency\SocketComponentMappingCompiler;
use Ikarus\Logic\Model\Component\NodeComponentInterface;
use Ikarus\Logic\Model\Component\Socket\ExposedSocketComponentInterface;
use Ikarus\Logic\Model\Component\Socket\InputSocketComponentInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Data\Node\NodeDataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;

class ExposedSocketsCompiler extends AbstractCompiler
{
    const RESULT_ATTRIBUTE_EXPOSED_SOCKETS = 'RESULT_ATTRIBUTE_EXPOSED_SOCKETS';

    protected function getSocketComponentCompiler(): SocketComponentMappingCompiler {
        return new SocketComponentMappingCompiler($this->getComponentModel());
    }

    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                /** @var NodeComponentInterface[] $components */
                $sockets = $result->getAttribute( SocketComponentMappingCompiler::RESULT_ATTRIBUTE_SOCKETS );
                if(NULL === $sockets) {
                    $this->getSocketComponentCompiler()->compile($dataModel, $result);
                    $sockets = $result->getAttribute( SocketComponentMappingCompiler::RESULT_ATTRIBUTE_SOCKETS );
                    if(!$result->isSuccess())
                        return;
                }

                $nodes = $result->getAttribute(NodeComponentMappingCompiler::RESULT_ATTRIBUTE_NODES);
                $exposedSockets = [];

                $findNodes = function($component, $sname, &$list) use ($nodes) {
                    /** @var NodeDataModelInterface $node */
                    foreach($nodes as $node) {
                        if($node->getComponentName() == $component)
                            $list[$component][$sname][$node->getIdentifier()] = $node;
                    }
                };


                /**
                 * @var string $sid
                 * @var InputSocketComponentInterface $socket
                 */
                foreach($sockets["inputs"] as $sid => $socket) {
                    if($socket instanceof ExposedSocketComponentInterface) {
                        list($compName, $sid) = explode(":", $sid);
                        $findNodes($compName, $sid, $exposedSockets);
                    }
                }

                foreach($sockets["outputs"] as $sid => $socket) {
                    if($socket instanceof ExposedSocketComponentInterface) {
                        list($compName, $sid) = explode(":", $sid);
                        $findNodes($compName, $sid, $exposedSockets);
                    }
                }

                $result->addAttribute(static::RESULT_ATTRIBUTE_EXPOSED_SOCKETS, $exposedSockets);
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }

}