<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Exception\SchemaException;

/**
 * Provides access to compiled ORM schema.
 *
 * @todo: improve?
 */
final class Schema implements SchemaInterface
{
    /** @var array */
    private $schema = [];

    /**
     * @param array $schema
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @inheritdoc
     */
    public function define(string $class, int $property)
    {
        if (!isset($this->schema[$class])) {
            throw new SchemaException("Undefined schema `{$class}`, not found.");
        }

        if (!array_key_exists($property, $this->schema[$class])) {
            return null;
        }

        return $this->schema[$class][$property];
    }

    public function getClass(string $alias): ?string
    {
        foreach ($this->schema as $class => $schema) {
            if ($schema[self::ALIAS] == $alias) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function defineRelation(string $class, string $relation): array
    {
        $relations = $this->define($class, self::RELATIONS);

        if (!isset($relations[$relation])) {
            throw new SchemaException("Undefined relation `{$class}`.`{$relation}`.");
        }

        return $relations[$relation];
    }
}