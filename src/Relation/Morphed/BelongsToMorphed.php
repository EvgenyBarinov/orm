<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BelongsTo;

class BelongsToMorphed extends BelongsTo
{
    /** @var string */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param string       $name
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $node): array
    {
        if (is_null($innerKey = $this->fetchKey($node, $this->innerKey))) {
            return [null, null];
        }

        /** @var string $target */
        $target = $this->fetchKey($node, $this->morphKey);
        if (is_null($target)) {
            return [null, null];
        }

        $r = $this->orm->promise($target, [$this->outerKey => $innerKey]);

        return [$r, $r];
    }

    /**
     * @inheritdoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        $wrappedStore = parent::queue($store, $entity, $node, $related, $original);

        if (is_null($related)) {
            if ($this->fetchKey($node, $this->morphKey) !== null) {
                $store->register($this->morphKey, null, true);
                $node->register($this->morphKey, null, true);
            }
        } else {
            $relState = $this->getNode($related);
            if ($this->fetchKey($node, $this->morphKey) != $relState->getRole()) {
                $store->register($this->morphKey, $relState->getRole(), true);
                $node->register($this->morphKey, $relState->getRole(), true);
            }
        }

        return $wrappedStore;
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @param Node $related
     *
     * @throws RelationException
     */
    protected function assertValid(Node $related)
    {
        // no need to validate morphed relation yet
    }
}