<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Context\AcceptorInterface;
use Spiral\ORM\Context\ForwarderInterface;
use Spiral\ORM\Traits\RelationTrait;

/**
 * Point (metadata) carries meta information about entitey state, changes forwards data to other points thought
 * inner states.
 */
final class Point implements ForwarderInterface, AcceptorInterface
{
    use RelationTrait;

    // Different entity states in a pool
    public const PROMISED         = 0;
    public const NEW              = 1;
    public const LOADED           = 2;
    public const SCHEDULED_INSERT = 3;
    public const SCHEDULED_UPDATE = 4;
    public const SCHEDULED_DELETE = 5;

    /** @var string */
    private $role;

    /** @var int */
    private $status;

    /** @var array */
    private $data;

    /** @var null|State */
    private $state;

    /**
     * @param int    $status
     * @param array  $data
     * @param string $role
     */
    public function __construct(int $status, array $data, string $role)
    {
        $this->status = $status;
        $this->data = $data;
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Current point state (set of changes).
     *
     * @return State
     */
    public function getState(): State
    {
        if (empty($this->state)) {
            $this->state = new State($this->status, $this->data);
        }

        return $this->state;
    }

    /**
     * Set new state value.
     *
     * @param int $state
     */
    public function setStatus(int $state): void
    {
        $this->getState()->setStatus($state);
    }

    /**
     * Get current state.
     *
     * @return int
     */
    public function getStatus(): int
    {
        if (!is_null($this->state)) {
            return $this->state->getStatus();
        }

        return $this->status;
    }

    /**
     * Set new state data (will trigger state handlers).
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->getState()->setData($data);
    }

    /**
     * Get current state data.
     *
     * @return array
     */
    public function getData(): array
    {
        if (!is_null($this->state)) {
            return $this->state->getData();
        }

        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function pull(
        string $key,
        AcceptorInterface $acceptor,
        string $target,
        bool $trigger = false,
        int $stream = self::DATA
    ) {
        $this->getState()->pull($key, $acceptor, $target, $trigger, $stream);
    }

    /**
     * @inheritdoc
     */
    public function push(string $key, $value, bool $update = false, int $stream = self::DATA)
    {
        $this->getState()->push($key, $value, $update, $stream);
    }

    /**
     * Sync the point state and return data diff.
     *
     * @return array
     */
    public function syncState(): array
    {
        if (is_null($this->state)) {
            return [];
        }

        $changes = array_diff($this->state->getData(), $this->data);

        foreach ($this->state->getRelations() as $name => $relation) {
            $this->setRelation($name, $relation);
        }

        $this->data = $this->state->getData();
        $this->status = self::LOADED;
        $this->state = null;

        return $changes;
    }

    /**
     * Reset point state and flush all the changes.
     */
    public function resetState()
    {
        $this->state = null;
    }

    /**
     * Reset state.
     */
    public function __destruct()
    {
        $this->data = [];
        $this->state = null;
        $this->relations = [];
    }
}