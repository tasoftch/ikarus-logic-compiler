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

/**
 * ExposedSocketsTest.php
 * ikarus-logic-compiler
 *
 * Created on 2019-12-22 20:29 by thomas
 */

namespace Ikarus\Logic\Compiler\Test;

use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Consistency\FullConsistencyCompiler;
use Ikarus\Logic\Compiler\Consistency\SocketComponentMappingCompiler;
use Ikarus\Logic\Compiler\Executable\ExposedSocketsCompiler;
use Ikarus\Logic\Model\Component\NodeComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedInputComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedOutputComponent;
use Ikarus\Logic\Model\Component\Socket\InputComponent;
use Ikarus\Logic\Model\Component\StaticNodeComponent;
use Ikarus\Logic\Model\Data\Loader\PHPArrayLoader;
use Ikarus\Logic\Model\PriorityComponentModel;
use PHPUnit\Framework\TestCase;

class ExposedSocketsTest extends TestCase
{
    public function testExposedSockets() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $cModel->addComponent( new NodeComponent("EXP", [
            new InputComponent("input1"),
            new ExposedInputComponent("input2"),
            new ExposedOutputComponent("output1")
        ]));

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'EXP'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $node1 = $model->getNodesInScene("myScene")["node1"];


        $compiler = new SocketComponentMappingCompiler($cModel);

        $result = new CompilerResult();
        $compiler->compile($model, $result);

        $compiler = new ExposedSocketsCompiler($cModel);
        $compiler->compile($model, $result);

        $this->assertSame([
            "EXP" => [
                'input2' => [
                    'node1' => $node1
                ],
                'output1' => [
                    'node1' => $node1
                ]
            ]
        ], $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS ));
    }
}
