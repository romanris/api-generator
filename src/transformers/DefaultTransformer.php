<?php
namespace rjapi\transformers;

use League\Fractal\TransformerAbstract;
use rjapi\blocks\DefaultInterface;
use rjapi\blocks\DirsInterface;
use rjapi\blocks\PhpEntitiesInterface;
use rjapi\exception\ModelException;
use rjapi\extension\BaseFormRequest;
use rjapi\extension\BaseModel;

class DefaultTransformer extends TransformerAbstract
{
    const INCLUDE_PREFIX = 'include';

    private $middleWare = null;

    public function __construct(BaseFormRequest $middleWare)
    {
        $this->middleWare = $middleWare;
        $this->setAvailableIncludes($middleWare->relations());
    }

    public function transform(BaseModel $object)
    {
        $props = get_object_vars($this->middleWare);
        $arr = [];
        try {
            foreach ($props as $prop => $value) {
                $arr[$prop] = $object->$prop;
            }
        } catch (ModelException $e) {
            $e->getTraceAsString();
        }

        return $arr;
    }

    public function __call($name, $arguments)
    {
        // getting entity relation name, ex.: includeAuthor - author
        $entityName = str_replace(self::INCLUDE_PREFIX, '', $name);

        $middlewareEntity = DirsInterface::MODULES_DIR . PhpEntitiesInterface::BACKSLASH . Config::getModuleName() .
            PhpEntitiesInterface::BACKSLASH . DirsInterface::HTTP_DIR .
            PhpEntitiesInterface::BACKSLASH .
            DirsInterface::MIDDLEWARE_DIR . PhpEntitiesInterface::BACKSLASH .
            $entityName .
            DefaultInterface::MIDDLEWARE_POSTFIX;
        $middleWare = new $middlewareEntity();
        $entityNameLow = strtolower($entityName);
        // getting object, ex.: Book
        $obj = $arguments[0];
        $entity = $obj->$entityNameLow;
        return $this->item($entity, new DefaultTransformer($middleWare), $entityNameLow);
    }
}