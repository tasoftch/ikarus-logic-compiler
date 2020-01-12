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
 * PHPDataModelSerializerTest.php
 * ikarus-logic-compiler
 *
 * Created on 2020-01-12 12:11 by thomas
 */

namespace Ikarus\Logic\Compiler\Test;

use Ikarus\Logic\Compiler\Serializer\PHPDataModelSerializer;
use Ikarus\Logic\Model\Data\Scene\SceneDataModel;
use Ikarus\Logic\Model\DataModel;
use PHPUnit\Framework\TestCase;

class PHPDataModelSerializerTest extends TestCase
{


    public function testEmptyModel() {
        $dm = new DataModel();

        $sl = new PHPDataModelSerializer(false);
        $string = $sl->serialize( $dm );
        /** @var DataModel $result */
        $result = eval($string);
        $this->assertInstanceOf(DataModel::class, $result);

        $this->assertCount(0, $result->getSceneDataModels());
    }

    public function testOneSceneOnlyModel() {
        $dm = (new DataModel())
            ->addScene("myScene");

        $sl = new PHPDataModelSerializer(false);
        $string = $sl->serialize( $dm );
        /** @var DataModel $result */
        $result = eval($string);
        $this->assertInstanceOf(DataModel::class, $result);

        $this->assertCount(1, $result->getSceneDataModels());
        /** @var SceneDataModel $scene */
        $scene = $result->getSceneDataModels()["myScene"];

        $this->assertEquals("myScene", $scene->getIdentifier());
    }

    public function testOneSceneOnlyWithAttributesModel() {
        $dm = (new DataModel())
            ->addScene("myScene", ["label" => 'Hehe']);

        $sl = new PHPDataModelSerializer(false);
        $string = $sl->serialize( $dm );
        /** @var DataModel $result */
        $result = eval($string);
        $this->assertEquals($dm, $result);
    }

    public function testSceneWithNodes() {
        $dm = (new DataModel())
            ->addScene("myScene", ["label" => 'Hehe'])
            ->addNode("node1", "test", "myScene", ["label" => 'Test Node'])
            ->addNode("node2", "other", "myScene")
            ->addNode("node3", "t_3", "myScene", [1, 2, [3, 2, 1], 4])
        ;

        $sl = new PHPDataModelSerializer(false);
        $string = $sl->serialize( $dm );
        /** @var DataModel $result */
        $result = eval($string);
        $this->assertEquals($dm, $result);
    }

    public function testSceneWithNodesAndConnections() {
        $dm = (new DataModel())
            ->addScene("myScene", ["label" => 'Hehe'])
            ->addNode("node1", "test", "myScene", ["label" => 'Test Node'])
            ->addNode("node2", "other", "myScene")
            ->addNode("node3", "t_3", "myScene", [1, 2, [3, 2, 1], 4])

            ->connect("node2", "input", "node3", 'output')
            ->connect("node1", "input", "node2", 'output')
            ->connect("node3", "input", "node1", 'output')
        ;

        $sl = new PHPDataModelSerializer(false);
        $string = $sl->serialize( $dm );
        /** @var DataModel $result */
        $result = eval($string);
        $this->assertEquals($dm, $result);
    }
}
