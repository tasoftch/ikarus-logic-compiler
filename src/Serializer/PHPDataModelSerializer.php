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

namespace Ikarus\Logic\Compiler\Serializer;


use Ikarus\Logic\Model\Data\AttributedDataModelInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Data\Node\NodeDataModelInterface;
use Ikarus\Logic\Model\Data\Scene\SceneDataModelInterface;

class PHPDataModelSerializer implements SerializerInterface
{
    private $asFile = true;

    /**
     * PHPDataModelSerializer constructor.
     * @param bool $asFile
     */
    public function __construct(bool $asFile = true)
    {
        $this->asFile = $asFile;
    }


    public function serialize($dataModel): string
    {
        if($dataModel instanceof DataModelInterface) {
            if($this->asFile)
                $string = "<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the \"Software\"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
 
use Ikarus\Logic\Model\DataModel;
 
return (new DataModel())";
            else
                $string = "
use Ikarus\Logic\Model\DataModel;
return (new DataModel())";

            $export = function($value) use (&$export) {
                if(is_string($value))
                    return sprintf("'%s'", str_replace("'", "\\'", $value));

                if(is_bool($value))
                    return $value ? 'TRUE' : 'FALSE';

                if(is_numeric($value))
                    return $value;

                if(is_iterable($value)) {
                    $exp = "[";
                    foreach($value as $key => $val) {
                        $exp .= $export($key) . "=>" . $export($val) . ",";
                    }
                    return rtrim($exp, ',') . "]";
                }
                return 'NULL';
            };

            /** @var SceneDataModelInterface $sceneDataModel */
            foreach($dataModel->getSceneDataModels() as $sceneDataModel) {
                if($sceneDataModel instanceof AttributedDataModelInterface) {
                    $string .= sprintf("\n\t->addScene(%s, %s)", $export($sceneDataModel->getIdentifier()), $export( $sceneDataModel->getAttributes()));
                } else {
                    $string .= sprintf("\n\t->addScene(%s)", $export($sceneDataModel->getIdentifier()));
                }

                /** @var NodeDataModelInterface $node */
                foreach($dataModel->getNodesInScene( $sceneDataModel ) as $node) {
                    if($node instanceof AttributedDataModelInterface)
                        $string .= sprintf("\n\t->addNode(%s, %s, %s, %s)",
                            $export($node->getIdentifier()),
                            $export($node->getComponentName()),
                            $export($sceneDataModel->getIdentifier()),
                            $export($node->getAttributes())
                        );
                    else
                        $string .= sprintf("\n\t->addNode(%s, %s, %s)",
                            $export($node->getIdentifier()),
                            $export($node->getComponentName()),
                            $export($sceneDataModel->getIdentifier())
                        );
                }

                foreach($dataModel->getConnectionsInScene($sceneDataModel) as $connectionDataModel) {
                    $string .= sprintf("\n\t->connect(%s, %s, %s, %s)",
                        $export($connectionDataModel->getInputNodeIdentifier()),
                        $export($connectionDataModel->getInputSocketName()),
                        $export($connectionDataModel->getOutputNodeIdentifier()),
                        $export($connectionDataModel->getOutputSocketName())
                    );
                }
            }

            return $string . ";";
        }
        return "";
    }

}