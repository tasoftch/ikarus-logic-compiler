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
 * StoreExecutableTest.php
 * ikarus-logic-compiler
 *
 * Created on 2019-12-23 18:26 by thomas
 */

namespace Ikarus\Logic\Compiler\Test;

use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Consistency\FullConsistencyCompiler;
use Ikarus\Logic\Compiler\Executable\FullExecutableCompiler;
use Ikarus\Logic\Compiler\Storage\InMemoryStorageCompiler;
use Ikarus\Logic\Compiler\Storage\PHPFileStorageCompiler;
use Ikarus\Logic\Model\Component\ExecutableNodeComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedInputComponent;
use Ikarus\Logic\Model\Component\Socket\ExposedOutputComponent;
use Ikarus\Logic\Model\Component\Socket\InputComponent;
use Ikarus\Logic\Model\Component\Socket\OutputComponent;
use Ikarus\Logic\Model\Data\Loader\PHPArrayLoader;
use Ikarus\Logic\Model\Package\BasicTypesPackage;
use Ikarus\Logic\Model\PriorityComponentModel;
use PHPUnit\Framework\TestCase;

class StoreExecutableTest extends TestCase
{
    private function prepareResult(&$cModel, &$model): CompilerResult {
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

        return $result;
    }

    public function testMemoryStorage() {
        $result = $this->prepareResult($cModel, $model);

        $storage = new InMemoryStorageCompiler($cModel);
        $storage->setMemory($memory);
        $storage->compile($model, $result);

        $this->assertEquals(["x", "X"], array_keys($memory));
    }

    public function testFileStorage() {
        $result = $this->prepareResult($cModel, $model);

        if(file_exists("test.storage.php"))
            unlink("test.storage.php");

        $storage = new PHPFileStorageCompiler($cModel);
        $storage->setFilename("test.storage.php");
        $storage->compile($model, $result);

        $this->assertFileExists("test.storage.php");

        $data = require "test.storage.php";
        $this->assertEquals([
            'i' => [
                'askForPermission' => [
                    'clickedButton' => [
                        'outputAnswer'
                    ]
                ]
            ],
            'o' => [
                'userInput' => [
                    'enteredNumber' => [
                        'askUser1',
                        'askUser2'
                    ]
                ]
            ]
        ], $data["x"]);
    }
}
