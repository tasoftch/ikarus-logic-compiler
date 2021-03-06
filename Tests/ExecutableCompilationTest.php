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
use Ikarus\Logic\Compiler\Executable\ExecutableCompiler;
use Ikarus\Logic\Compiler\Executable\ExposedSocketsCompiler;
use Ikarus\Logic\Compiler\Executable\FullExecutableCompiler;
use Ikarus\Logic\Compiler\StandardCompiler;
use Ikarus\Logic\Component\SceneGatewayComponent;
use Ikarus\Logic\Model\Component\ExecutableNodeComponent;
use Ikarus\Logic\Model\Component\NodeComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedInputComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedOutputComponent;
use Ikarus\Logic\Model\Component\Socket\InputComponent;
use Ikarus\Logic\Model\Component\Socket\OutputComponent;
use Ikarus\Logic\Model\Data\Loader\PHPArrayLoader;
use Ikarus\Logic\Model\Data\Scene\AttributedSceneDataModelInterface;
use Ikarus\Logic\Model\DataModel;
use Ikarus\Logic\Model\Package\BasicTypesPackage;
use Ikarus\Logic\Model\Package\ExposedSocketsPackage;
use Ikarus\Logic\Model\PriorityComponentModel;
use PHPUnit\Framework\TestCase;

class ExecutableCompilationTest extends TestCase
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

        $compiler = new SocketComponentMappingCompiler($cModel);

        $result = new CompilerResult();
        $compiler->compile($model, $result);

        $compiler = new ExposedSocketsCompiler($cModel);
        $compiler->compile($model, $result);

        $this->assertSame([
            'i' => [
                "EXP" => [
                    'input2' => [
                        'node1'
                    ]
                ]
            ],
            'o' => [
                "EXP" => [
                    'output1' => [
                        'node1'
                    ]
                ]
            ]
        ], $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS ));
    }

    public function testExecutableCompiler() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new BasicTypesPackage() );

        $cModel->addComponent(new ExecutableNodeComponent("math", [
            new InputComponent("leftOperand", "Number"),
            new InputComponent("rightOperand", "Number"),
            new OutputComponent("result", "Number")
        ]));

        $cModel->addComponent( new ExecutableNodeComponent("userInput", [
            // If you have a node obtaining values, it will provide them via outputs to other nodes inputs.
            new ExposedOutputComponent("enteredNumber", "Number")
        ]) );

        $cModel->addComponent( new ExecutableNodeComponent("displayDialog", [
            new InputComponent("message", "String"),
            new OutputComponent("clickedButton", "Number")
        ]) );

        $cModel->addComponent( new ExecutableNodeComponent("askForPermission", [
            new ExposedInputComponent("clickedButton", "Number")
        ]) );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'askUser1' => [
                            PHPArrayLoader::NAME_KEY => 'userInput',
                            PHPArrayLoader::DATA_KEY => [
                                'info' => 'Please enter the first operand'
                            ]
                        ],
                        'askUser2' => [
                            PHPArrayLoader::NAME_KEY => 'userInput',
                            PHPArrayLoader::DATA_KEY => [
                                'info' => 'Please enter the second operand'
                            ]
                        ],
                        'myMath' => [
                            PHPArrayLoader::NAME_KEY => 'math',
                            PHPArrayLoader::DATA_KEY => [
                                'operation' => '+'
                            ]
                        ],
                        'showUser' => [
                            PHPArrayLoader::NAME_KEY => 'displayDialog'
                        ],
                        'outputAnswer' => [
                            PHPArrayLoader::NAME_KEY => 'askForPermission',
                        ]
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'outputAnswer',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'clickedButton',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'showUser',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'clickedButton',
                        ],
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'showUser',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'message',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'myMath',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'result',
                        ],
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'myMath',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'leftOperand',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'askUser1',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'enteredNumber',
                        ],
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'myMath',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'rightOperand',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'askUser2',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'enteredNumber',
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

        $compiler = new FullExecutableCompiler($cModel);
        $compiler->compile($model, $result);

        $this->assertEquals(["askForPermission"], array_keys( $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS )["i"] ));
        $this->assertEquals(['userInput'], array_keys( $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS )["o"] ));


        $this->assertEquals([
            "outputAnswer:clickedButton",
            "showUser:message",
            "myMath:leftOperand",
            "myMath:rightOperand"
        ], array_keys( $result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE )["i2o"] ));
    }

    /**
     * @expectedException \Ikarus\Logic\Compiler\Exception\DuplicateSocketReferenceException
     * @expectedExceptionCode 105
     */
    public function testDuplicateInputReferenceError() {
        $cModel = new PriorityComponentModel();
        $cModel->addPackage( new BasicTypesPackage() );

        $cModel->addComponent( new ExecutableNodeComponent("math", [
            new InputComponent("leftOperand", "Number"),
            new InputComponent("rightOperand", "Number"),
            new OutputComponent("result", "Number")
        ]) );

        $cModel->addComponent( new ExecutableNodeComponent("userInput", [
            // If you have a node obtaining values, it will provide them via outputs to other nodes inputs.
            new ExposedOutputComponent("enteredNumber", "Number")
        ]) );

        $cModel->addComponent( new ExecutableNodeComponent("displayDialog", [
            new InputComponent("message", "String"),
            new OutputComponent("clickedButton", "Number")
        ]) );

        $cModel->addComponent( new ExecutableNodeComponent("askForPermission", [
            new ExposedInputComponent("clickedButton", "Number")
        ]) );

        $loader = new PHPArrayLoader([
            PHPArrayLoader::SCENES_KEY => [
                'myScene' => [
                    PHPArrayLoader::NODES_KEY => [
                        'askUser1' => [
                            PHPArrayLoader::NAME_KEY => 'userInput',
                            PHPArrayLoader::DATA_KEY => [
                                'info' => 'Please enter the first operand'
                            ]
                        ],
                        'askUser2' => [
                            PHPArrayLoader::NAME_KEY => 'userInput',
                            PHPArrayLoader::DATA_KEY => [
                                'info' => 'Please enter the second operand'
                            ]
                        ],
                        'myMath' => [
                            PHPArrayLoader::NAME_KEY => 'math',
                            PHPArrayLoader::DATA_KEY => [
                                'operation' => '+'
                            ]
                        ],
                        'showUser' => [
                            PHPArrayLoader::NAME_KEY => 'displayDialog'
                        ],
                        'outputAnswer' => [
                            PHPArrayLoader::NAME_KEY => 'askForPermission',
                        ]
                    ],
                    PHPArrayLoader::CONNECTIONS_KEY => [
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'outputAnswer',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'clickedButton',
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'showUser',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'clickedButton',
                        ],
                        [
                            PHPArrayLoader::CONNECTION_INPUT_NODE_KEY => 'outputAnswer',
                            PHPArrayLoader::CONNECTION_INPUT_KEY => 'clickedButton',        // Duplicate connection on the same input socket is not allowed
                            PHPArrayLoader::CONNECTION_OUTPUT_NODE_KEY => 'showUser',
                            PHPArrayLoader::CONNECTION_OUTPUT_KEY => 'clickedButton',
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

        $compiler = new FullExecutableCompiler($cModel);
        $compiler->compile($model, $result);
    }

    public function testGatewayExposedCompilation() {
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
            ->connect("exp_output", 'input', 'exp_input', 'output')

            ->pair('linked', 'node', [
                'myInput' => 'exp_input.output',
                'myOutput' => 'exp_output.input'
            ])
        ;


        $compiler = new StandardCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        $this->assertEquals([
            'i' => [
                'IKARUS.OUT.ANY' => [
                    'input' => [
                        'out',
                        'exp_output'
                    ]
                ]
            ],
            'o' => [
                'IKARUS.IN.ANY' => [
                    'output' => [
                        'in',
                        'exp_input'
                    ]
                ]
            ]
        ], $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS ));
    }


    public function testGatewayExposedSilentCompilation() {
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

            ->addScene("linked", [ AttributedSceneDataModelInterface::ATTR_HIDDEN => 1 ])
            ->addNode("exp_input", 'IKARUS.IN.ANY', 'linked')
            ->addNode("exp_output", 'IKARUS.OUT.ANY', 'linked')

            ->connect('node', 'myInput', 'in', 'output')
            ->connect('out', 'input', 'node', 'myOutput')
            ->connect("exp_output", 'input', 'exp_input', 'output')

            ->pair('linked', 'node', [
                'myInput' => 'exp_input.output',
                'myOutput' => 'exp_output.input'
            ])
        ;


        $compiler = new StandardCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        $this->assertEquals([
            'i' => [
                'IKARUS.OUT.ANY' => [
                    'input' => [
                        'out'
                    ]
                ]
            ],
            'o' => [
                'IKARUS.IN.ANY' => [
                    'output' => [
                        'in'
                    ]
                ]
            ]
        ], $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS ));
    }

    public function testGatewayCompilation() {
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

            ->addScene("linked", [ AttributedSceneDataModelInterface::ATTR_HIDDEN => 1 ])
            ->addNode("exp_input", 'IKARUS.IN.ANY', 'linked')
            ->addNode("exp_output", 'IKARUS.OUT.ANY', 'linked')

            ->connect('node', 'myInput', 'in', 'output')
            ->connect('out', 'input', 'node', 'myOutput')
            ->connect("exp_output", 'input', 'exp_input', 'output')

            ->pair('linked', 'node', [
                'myInput' => 'exp_input.output',
                'myOutput' => 'exp_output.input'
            ])
        ;


        $compiler = new StandardCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        print_r($result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE )['i2o']);

        $this->assertEquals([
            'exp_input:output' => [
                [
                    'dn' => 'in',
                    'dk' => 'output',
                    'gw' => '-'
                ]
            ],
            'out:input' => [
                [
                    'dn' => 'exp_output',
                    'dk' => 'input',
                    'gw' => '+'
                ]
            ],
            'exp_output:input' => [
                [
                    'dn' => 'exp_input',
                    'dk' => 'output'
                ]
            ]
        ], $result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE )['i2o']);
    }

    public function testSignalGatewayCompilation() {
        $cModel = (new PriorityComponentModel())
            ->addPackage(new BasicTypesPackage())
            ->addComponent(new SceneGatewayComponent())
            ->addPackage(new ExposedSocketsPackage('Signal'))
        ;

        $dModel = (new DataModel())
            ->addScene("myScene")
            ->addNode("node", 'IKARUS.GATEWAY', 'myScene')
            ->addNode("out", "IKARUS.OUT.SIGNAL", 'myScene')
            ->addNode('in', 'IKARUS.IN.SIGNAL', 'myScene')

            ->addScene("linked", [ AttributedSceneDataModelInterface::ATTR_HIDDEN => 1 ])
            ->addNode("exp_input", 'IKARUS.IN.SIGNAL', 'linked')
            ->addNode("exp_output", 'IKARUS.OUT.SIGNAL', 'linked')

            ->connect('node', 'myInput', 'in', 'output')
            ->connect('out', 'input', 'node', 'myOutput')
            ->connect("exp_output", 'input', 'exp_input', 'output')

            ->pair('linked', 'node', [
                'myInput' => 'exp_input.output',
                'myOutput' => 'exp_output.input'
            ])
        ;


        $compiler = new StandardCompiler($cModel);

        $result = new CompilerResult();

        $compiler->compile($dModel, $result);

        $this->assertEquals([
            'in:output' => [
                [
                    'dn' => 'exp_input',
                    'dk' => 'output',
                    'gw' => '+'
                ]
            ],
            'exp_output:input' => [
                [
                    'dn' => 'out',
                    'dk' => 'input',
                    'gw' => '-'
                ]
            ],
            'exp_input:output' => [
                [
                    'dn' => 'exp_output',
                    'dk' => 'input'
                ]
            ]
        ], $result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE )['o2i']);
    }
}
