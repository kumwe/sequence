<?php

declare(strict_types=1);

namespace Kumwe\Sequence\Tests\Case;

use InvalidArgumentException;
use Kumwe\Sequence\Exception\NumberSequenceUnavailable;
use Kumwe\Sequence\Tests\TestCase;
use LogicException;
use ReflectionClass;
use RuntimeException;

/**
 * Pins the identity of the one refusal an allocation can answer with.
 *
 * @since  0.1.0
 */
final class NumberSequenceUnavailableTest extends TestCase
{
    /**
     * The refusal is a final runtime exception with one fixed, deterministic message.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheRefusalIsAFinalRuntimeExceptionWithAFixedMessage(): void
    {
        $refusal = new NumberSequenceUnavailable();

        $this->assertTrue($refusal instanceof RuntimeException, 'A refusal is a runtime condition.');
        $this->assertFalse($refusal instanceof InvalidArgumentException, 'It is never an argument error.');
        $this->assertTrue((new ReflectionClass($refusal))->isFinal(), 'One canonical type, not a hierarchy.');
        $this->assertSame(
            'The number sequence counter is temporarily unavailable; replay the allocation.',
            $refusal->getMessage(),
            'The message is fixed so retry policy and logs see one identity.',
        );
        $this->assertSame(0, $refusal->getCode(), 'No code is smuggled through.');
        $this->assertSame(null, $refusal->getPrevious(), 'Detected directly: nothing chained.');
    }

    /**
     * The failure an adapter translated stays reachable as the chained previous exception.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheTranslatedFailureStaysReachableAsThePrevious(): void
    {
        $driver = new LogicException('deadlock detected');
        $refusal = new NumberSequenceUnavailable($driver);

        $this->assertSame($driver, $refusal->getPrevious(), 'The original failure is kept for the log.');
        $this->assertSame(
            'The number sequence counter is temporarily unavailable; replay the allocation.',
            $refusal->getMessage(),
            'Chaining does not change the message.',
        );
    }
}
