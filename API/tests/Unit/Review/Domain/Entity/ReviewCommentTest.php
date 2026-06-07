<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Domain\Entity;

use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Exception\InvalidReviewCommentException;
use PHPUnit\Framework\TestCase;

final class ReviewCommentTest extends TestCase
{
    public function test_should_accept_a_comment_with_at_least_50_characters(): void
    {
        $text = str_repeat('a', 50);

        $comment = new ReviewComment($text);

        self::assertSame($text, $comment->toString());
    }

    public function test_should_throw_when_comment_is_null(): void
    {
        $this->expectException(InvalidReviewCommentException::class);
        $this->expectExceptionMessage('Review comment is required.');

        new ReviewComment(null);
    }

    public function test_should_throw_when_comment_is_too_short(): void
    {
        $this->expectException(InvalidReviewCommentException::class);
        $this->expectExceptionMessage('Review comment must be at least 50 characters long, got 10.');

        new ReviewComment(str_repeat('a', 10));
    }

    public function test_should_count_trimmed_length(): void
    {
        $this->expectException(InvalidReviewCommentException::class);
        $this->expectExceptionMessage('Review comment must be at least 50 characters long, got 3.');

        new ReviewComment('   abc   ');
    }
}
