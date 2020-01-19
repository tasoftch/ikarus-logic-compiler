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
 * Node2ComponentCompilerTest.php
 * ikarus-logic-compiler
 *
 * Created on 2019-12-21 14:27 by thomas
 */

namespace Ikarus\Logic\Compiler\Test;

use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Consistency\ConnectionConsistencyCompiler;
use Ikarus\Logic\Compiler\Consistency\FullConsistencyCompiler;
use Ikarus\Logic\Compiler\Consistency\GatewayConsistencyCompiler;
use Ikarus\Logic\Compiler\Consistency\SocketComponentMappingCompiler;
use Ikarus\Logic\Component\SceneGatewayComponent;
use Ikarus\Logic\Model\Component\Socket\InputComponent;
use Ikarus\Logic\Model\Component\Socket\OutputComponent;
use Ikarus\Logic\Model\Component\NodeComponent;
use Ikarus\Logic\Model\Data\Loader\PHPArrayLoader;
use Ikarus\Logic\Model\DataModel;
use Ikarus\Logic\Model\Package\BasicTypesPackage;
use Ikarus\Logic\Model\Package\ExposedSocketsPackage;
use Ikarus\Logic\Model\PriorityComponentModel;
use PHPUnit\Framework\TestCase;

class ConsistencyCompilerTest extends TestCase
{
    public function testSimplePackage() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
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

        $compiler = new FullConsistencyCompiler($cModel);

        $result = new CompilerResult();
        $compiler->compile($model, $result);

        $this->assertTrue($result->isSuccess());

        $this->assertEquals([
            "Any", "Boolean", "String", "Number"
        ], array_keys($result->getAttribute(SocketComponentMappingCompiler::RESULT_ATTRIBUTE_TYPES)));
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidComponentReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidNodeComponentReference() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'nonexistentComponent'
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

        $compiler = new FullConsistencyCompiler($cModel);

        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidComponentReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidSocketTypeReference() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $cModel->addComponent(new NodeComponent("MyNode", [
            new InputComponent("the-input", "Noneisting Type")
        ]));


        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'MyNode'
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

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Model\Exception\InvalidReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidInputNodeConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => '#node!',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input2',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output1'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Model\Exception\InvalidReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidOutputNodeConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input2',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => '#node!',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output1'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidSocketReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidInputNodeSocketKeyConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => '#input!',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output1'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidSocketReferenceException
     * @expectedExceptionCode 99
     */
    public function testInvalidOutputNodeSocketKeyConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input2',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => '#output!'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidNodeRecursionReferenceException
     * @expectedExceptionCode 102
     */
    public function testInput2OutputOfSameNodeReference() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $cModel->addComponent( new NodeComponent("recNode", [
            new InputComponent("input"),
            new OutputComponent("output")
        ]) );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'recNode'
                        ]
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1', // Same node
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node1', // Same node
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    public function testCorrectConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new SimplePackage() );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'simpleTest'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'otherTest'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input2',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output2'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);

        $inputSocket = $cModel->getComponent("simpleTest")->getInputSockets()["input2"];
        $outputSocket = $cModel->getComponent("otherTest")->getOutputSockets()["output2"];

        $inputNode = $model->getNodesInScene('myScene')["node1"];
        $outputNode = $model->getNodesInScene("myScene")["node2"];

        $this->assertSame([[
            $inputNode,
            $inputSocket,
            $outputNode,
            $outputSocket
        ]], $result->getAttribute( ConnectionConsistencyCompiler::RESULT_ATTRIBUTE_CONNECTIONS ));
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\InvalidSocketTypesReferenceException
     * @expectedExceptionCode 102
     */
    public function testInvaidTypesConnection() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new BasicTypesPackage() );

        $cModel->addComponent( new NodeComponent("NBR_NODE", [
            new InputComponent("input", "Number")
        ]) );
        $cModel->addComponent( new NodeComponent("STR_NODE", [
            new OutputComponent("output", "String")
        ]) );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'node1' => [
                            PHPArrayLoader::NAME_KEY => 'NBR_NODE'
                        ],
                        'node2' => [
                            PHPArrayLoader::NAME_KEY => 'STR_NODE'
                        ],
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'node1',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'input',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'node2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'output'
                        ]
                    ]
                ]
            ]
        ]);
        $loader->useIndicesAsIdentifiers = true;

        $model = $loader->getModel();

        $compiler = new FullConsistencyCompiler($cModel);
        $result = new CompilerResult();
        $compiler->compile($model, $result);
    }

    /**
     * @expectedException \Ikarus\Logic\Model\Exception\InconsistentComponentModelException
     * @expectedExceptionCode 77
     */
    public function testWrongComponentGatewaysDataModel() {
        $cModel = (new PriorityComponentModel())
            ->addPackage(new BasicTypesPackage())
            ->addComponent(new NodeComponent("IKARUS.GATEWAY", []))
        ;

        $dModel = (new DataModel())
            ->addScene("myScene")
            ->addNode("node", 'IKARUS.GATEWAY', 'myScene')
            ->pair('myScene', 'node', [])
        ;

        $compiler = new GatewayConsistencyCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        print_r($result);
    }

    public function testEmptyGatewaysDataModel() {
        $cModel = (new PriorityComponentModel())
            ->addPackage(new BasicTypesPackage())
            ->addComponent(new SceneGatewayComponent())
        ;

        $dModel = (new DataModel())
            ->addScene("myScene")
            ->addNode("node", 'IKARUS.GATEWAY', 'myScene')
            ->pair('myScene', 'node', [])
        ;

        $compiler = new GatewayConsistencyCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        $this->assertEmpty($result->getAttribute( GatewayConsistencyCompiler::RESULT_ATTRIBUTE_GATEWAYS ));
    }

    public function testSimpleGateway() {
        $cModel = (new PriorityComponentModel())
            ->addPackage(new BasicTypesPackage())
            ->addComponent(new SceneGatewayComponent())
            ->addPackage(new ExposedSocketsPackage('Any'))
        ;

        $dModel = (new DataModel())
            ->addScene("myScene")
            ->addNode("node", 'IKARUS.GATEWAY', 'myScene')
            ->addNode("out", "IKARUS.OUT.ANY", 'myScene')
            ->addNode('in', 'IKARUS.IN.ANY', 'myScene')

            ->addScene("linked")
            ->addNode("exp_input", 'IKARUS.IN.ANY', 'linked')
            ->addNode("exp_output", 'IKARUS.OUT.ANY', 'linked')

            ->connect('node', 'myInput', 'in', 'output')
            ->connect('out', 'input', 'node', 'myOutput')

            ->pair('linked', 'node', [
                'myInput' => 'exp_input.output',
                'myOutput' => 'exp_output.input'
            ])
        ;


        $compiler = new FullConsistencyCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        $nodeIN = $dModel->getNodesInScene('myScene')["in"];
        $nodeOUT = $dModel->getNodesInScene('myScene')["out"];
        $exp_input = $dModel->getNodesInScene('linked')["exp_input"];
        $exp_output = $dModel->getNodesInScene('linked')["exp_output"];

        $in_any = $cModel->getComponent( 'IKARUS.IN.ANY' )->getOutputSockets()["output"];
        $out_any = $cModel->getComponent( 'IKARUS.OUT.ANY' )->getInputSockets()["input"];

        $this->assertEquals([
            [
                $exp_input,
                $in_any,
                $nodeIN,
                $in_any,
                'gw' => '-'
            ],
            [
                $nodeOUT,
                $out_any,
                $exp_output,
                $out_any,
                'gw' => '+'
            ]
        ], $result->getAttribute( ConnectionConsistencyCompiler::RESULT_ATTRIBUTE_CONNECTIONS ));
    }
}
